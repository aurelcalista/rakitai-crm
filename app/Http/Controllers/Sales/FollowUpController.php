<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\FollowUp;
use App\Models\Prospek;
use App\Models\ProspekTimeline;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FollowUpController extends Controller
{
    /**
     * List prospects that need follow-up, categorized by timing.
     * Scoped to the logged-in Sales user's prospects.
     */
    public function index(Request $request): View
    {
        $user  = auth()->user();
        $today = now()->toDateString();

        $allProspects = Prospek::with(['cs', 'sales', 'owner', 'latestFollowUp.user'])
            ->where(function ($q) use ($user) {
                $q->where('sales_id', $user->id)
                  ->orWhere('owner_id', $user->id)
                  ->orWhere(function ($sub) {
                      $sub->where(function ($s) {
                          $s->whereHas('sales', function ($sq) {
                              $sq->whereIn('role', ['Sales', 'sales']);
                          })->orWhereHas('owner', function ($oq) {
                              $oq->whereIn('role', ['Sales', 'sales']);
                          });
                      });
                  });
            })
            ->withCount('followUps')
            ->orderBy('updated_at', 'desc')
            ->get();

        $needFollowUp = [];
        $others       = [];
        $scheduled    = [];
        $done         = [];

        // Legacy / internal categories for backward compatibility
        $uncontacted  = [];
        $todayList    = [];
        $overdue      = [];
        $unscheduled  = [];
        $upcoming     = [];

        foreach ($allProspects as $p) {
            $latestFU = $p->latestFollowUp ?? ($p->relationLoaded('followUps') ? $p->followUps->first() : null);
            $row      = $this->formatProspekRow($p, $latestFU, $today);

            $isDone   = in_array(strtoupper((string)$p->status), ['LUNAS', 'DINGIN']);
            $isOwn    = $row['is_own'];
            $isOther  = $row['is_other'];
            $timing   = $row['timing_category'];

            if ($isDone) {
                if ($isOwn) {
                    $done[] = $row;
                }
            } else {
                // Active prospects
                if ($isOwn) {
                    if ($timing === 'upcoming') {
                        $scheduled[] = $row;
                    } else {
                        // overdue, today, uncontacted, unscheduled
                        $needFollowUp[] = $row;
                    }
                }

                if ($isOther) {
                    // ALL active prospects belonging to other Sales that are authorized
                    $others[] = $row;
                }

                // Internal/backward compatibility
                if ($timing === 'uncontacted') {
                    $uncontacted[] = $row;
                } elseif ($timing === 'today') {
                    $todayList[] = $row;
                } elseif ($timing === 'overdue') {
                    $overdue[] = $row;
                } elseif ($timing === 'unscheduled') {
                    $unscheduled[] = $row;
                } elseif ($timing === 'upcoming') {
                    $upcoming[] = $row;
                }
            }
        }

        $this->sortNeedFollowUp($needFollowUp);
        $this->sortOthers($others);
        $this->sortScheduled($scheduled);
        $this->sortDone($done);

        $prospects = [
            'need_follow_up'     => $needFollowUp,
            'others'             => $others,
            'scheduled'          => $scheduled,
            'done'               => $done,

            // Aliases
            'bantuan_sales_lain' => $others,
            'terjadwal'          => $scheduled,
            'selesai'            => $done,
            'perlu_follow_up'    => $needFollowUp,

            // Legacy keys
            'uncontacted'        => $uncontacted,
            'today'              => $todayList,
            'overdue'            => $overdue,
            'unscheduled'        => $unscheduled,
            'upcoming'           => $upcoming,
        ];

        $metodeOptions = FollowUp::METODE_OPTIONS;
        $statuses      = Prospek::getActiveStages();

        return view('follow-up.index', compact('prospects', 'metodeOptions', 'statuses'));
    }

    /**
     * Store a new follow-up record.
     *
     * Authorization rules (backend enforced):
     * - User must be authenticated Sales
     * - Prospect must have sales_id = auth()->id() (active handler)
     * - If prospect's handler is CS only, Sales cannot follow-up
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'prospek_id'    => 'required|exists:prospeks,id',
            'metode'        => 'required|in:WhatsApp,Telepon,Meeting,Email',
            'hasil'         => 'required|string|max:255',
            'catatan'       => 'required|string|max:2000',
            'status'        => 'required|string|max:255',
            'next_follow_up'=> 'nullable|date',
        ]);

        $prospek = Prospek::findOrFail($validated['prospek_id']);

        // Backend authorization: must be active handler (Sales or CS)
        \Illuminate\Support\Facades\Gate::authorize('followUp', $prospek);

        $oldStatus = $prospek->status;
        $newStatus = $validated['status'];

        $actorId = auth()->id();
        if (!$actorId) {
            abort(401, 'Silakan login terlebih dahulu.');
        }

        // Save follow-up record - executor is authenticated user, sales_id and owner_id are untouched
        FollowUp::create([
            'prospek_id'    => $prospek->id,
            'user_id'       => $actorId,
            'metode'        => $validated['metode'],
            'tanggal'       => now(),
            'hasil'         => $validated['hasil'],
            'catatan'       => $validated['catatan'],
            'next_follow_up'=> $validated['next_follow_up'] ?? null,
        ]);

        $prospek->increment('active_follow_up_count');
        $prospek->increment('follow_up_count');

        // Update prospect status if changed
        if (strtoupper(trim((string)$oldStatus)) !== strtoupper(trim((string)$newStatus))) {
            \Illuminate\Support\Facades\Gate::authorize('updateStatus', $prospek);

            // Prevent Sales from setting status directly to LUNAS (requires CS / closing payment)
            if (strtoupper($newStatus) === 'LUNAS' && strtolower(auth()->user()->role ?? '') === 'sales') {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['status' => 'Status LUNAS hanya dapat diproses oleh CS setelah pelunasan pembayaran. Sales dapat memilih status CLOSING.']);
            }

            $stagesMap = Prospek::getDynamicStagesMap();
            $prospek->update([
                'status'       => $newStatus,
                'stage_number' => $stagesMap[$newStatus] ?? $prospek->stage_number,
            ]);

            ProspekTimeline::create([
                'prospek_id'   => $prospek->id,
                'user_id'      => $actorId,
                'title'        => 'Follow Up & Update Status',
                'notes'        => 'Follow up via ' . $validated['metode'] . '. Status berubah dari ' . $oldStatus . ' menjadi ' . $newStatus . '. Hasil: ' . $validated['hasil'] . '. Catatan: ' . $validated['catatan'],
                'status_before' => $oldStatus,
                'status_after'  => $newStatus,
                'time'          => now(),
            ]);
        } else {
            // Log follow-up even without status change
            ProspekTimeline::create([
                'prospek_id'   => $prospek->id,
                'user_id'      => $actorId,
                'title'        => 'Follow Up via ' . $validated['metode'],
                'notes'        => 'Hasil: ' . $validated['hasil'] . '. Catatan: ' . $validated['catatan'],
                'status_before' => $oldStatus,
                'status_after'  => $prospek->status,
                'time'          => now(),
            ]);
        }

        $prospek->touch();

        return redirect()->back()
            ->with('success', 'Follow-up berhasil disimpan!');
    }

    // ─── Private Helpers ─────────────────────────────────────────────────────

    private function formatProspekRow(\App\Models\Prospek $p, ?FollowUp $latestFU, string $today): array
    {
        $hasHistory       = ($p->follow_ups_count > 0) || ($latestFU !== null);
        $nextFollowUpDate = $latestFU && $latestFU->next_follow_up
            ? Carbon::parse($latestFU->next_follow_up)->toDateString()
            : null;
        $nextFollowUpRaw  = $latestFU && $latestFU->next_follow_up
            ? Carbon::parse($latestFU->next_follow_up)->toIso8601String()
            : null;
        $isDone           = in_array(strtoupper((string)$p->status), ['LUNAS', 'DINGIN']);

        // Determine timing category & priority
        if ($isDone) {
            $timingCategory     = 'done';
            $priorityLabel      = strtoupper((string)$p->status);
            $priorityBadgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
            $needFuPriority     = null;
            $othersPriority     = null;
        } elseif ($nextFollowUpDate !== null) {
            if ($nextFollowUpDate < $today) {
                $timingCategory     = 'overdue';
                $priorityLabel      = 'Terlambat';
                $priorityBadgeClass = 'bg-rose-50 text-rose-700 border-rose-200';
                $needFuPriority     = 1;
                $othersPriority     = 1;
            } elseif ($nextFollowUpDate === $today) {
                $timingCategory     = 'today';
                $priorityLabel      = 'Jadwal Hari Ini';
                $priorityBadgeClass = 'bg-amber-50 text-amber-700 border-amber-200';
                $needFuPriority     = 2;
                $othersPriority     = 2;
            } else {
                $timingCategory     = 'upcoming';
                $priorityLabel      = 'Mendatang';
                $priorityBadgeClass = 'bg-blue-50 text-blue-700 border-blue-200';
                $needFuPriority     = null; // strictly excluded from need_follow_up!
                $othersPriority     = 5;    // priority 5 in others
            }
        } else {
            if (!$hasHistory) {
                $timingCategory     = 'uncontacted';
                $priorityLabel      = 'Belum Dihubungi';
                $priorityBadgeClass = 'bg-sky-50 text-sky-700 border-sky-200';
                $needFuPriority     = 3;
                $othersPriority     = 3;
            } else {
                $timingCategory     = 'unscheduled';
                $priorityLabel      = 'Belum Dijadwalkan';
                $priorityBadgeClass = 'bg-purple-50 text-purple-700 border-purple-200';
                $needFuPriority     = 4;
                $othersPriority     = 4;
            }
        }

        $authId        = auth()->id() ?? 0;
        $authRole      = strtolower(auth()->user()->role ?? '');

        if ($authRole === 'cs') {
            $isOwn = (bool) ($authId && ($p->cs_id == $authId || (is_null($p->cs_id) && in_array($p->status, ['FORMULIR', 'BERKAS', 'CLOSING', 'LUNAS']))));
            $isOther = (bool) ($authId && !$isOwn && !is_null($p->cs_id) && $p->cs_id != $authId);
        } else {
            $isOwn         = (bool) ($authId && ($p->sales_id == $authId || $p->owner_id == $authId));
            $salesRole     = strtolower(optional($p->sales)->role ?? '');
            $ownerRole     = strtolower(optional($p->owner)->role ?? '');
            $hasSalesOwner = ($salesRole === 'sales') || ($ownerRole === 'sales') || (!is_null($p->sales_id));
            $isOther       = (bool) ($authId && !$isOwn && $hasSalesOwner);
        }

        return [
            'id'                  => $p->id,
            'name'                => $p->name,
            'sales_id'            => $p->sales_id,
            'owner_id'            => $p->owner_id,
            'cs_id'               => $p->cs_id,
            'type'                => $p->type,
            'pic'                 => $p->pic ?? '-',
            'whatsapp'            => $p->whatsapp ?? '-',
            'status'              => $p->status,
            'active_takeover'     => $p->activeHandlerLabel(),
            'last_activity'       => $p->updated_at ? $p->updated_at->diffForHumans() : '-',
            'last_contact'        => $latestFU && $latestFU->tanggal
                ? Carbon::parse($latestFU->tanggal)->format('d M Y, H:i')
                : '-',
            'next_follow_up'      => $latestFU && $latestFU->next_follow_up
                ? Carbon::parse($latestFU->next_follow_up)->format('d M Y, H:i')
                : '-',
            'next_follow_up_date' => $nextFollowUpDate,
            'next_follow_up_raw'  => $nextFollowUpRaw,
            'updated_at_raw'      => $p->updated_at ? $p->updated_at->toIso8601String() : null,
            'created_at_raw'      => $p->created_at ? $p->created_at->toIso8601String() : null,
            'timing_category'     => $timingCategory,
            'priority_label'      => $priorityLabel,
            'priority_badge_class'=> $priorityBadgeClass,
            'need_fu_priority'    => $needFuPriority,
            'others_priority'     => $othersPriority,
            'metode_terakhir'     => $latestFU ? ($latestFU->metode ?? '-') : '-',
            'hasil_terakhir'      => $latestFU ? ($latestFU->hasil ?? '-') : '-',
            'notes'               => $latestFU && $latestFU->catatan ? $latestFU->catatan : ($p->notes ?? '-'),
            'sales_name'          => $p->sales ? $p->sales->name : ($p->owner ? $p->owner->name : '-'),
            'cs_name'             => $p->cs ? $p->cs->name : ($p->owner && strtolower($p->owner->role ?? '') === 'cs' ? $p->owner->name : '-'),
            'takeover_cs'         => $p->cs ? $p->cs->name : ($p->owner && strtolower($p->owner->role ?? '') === 'cs' ? $p->owner->name : null),
            'owner'               => $p->owner ? $p->owner->name : 'Sistem',
            'latest_follow_up_user' => $latestFU && $latestFU->user ? $latestFU->user->name : null,
            'latest_follow_up_user_id' => $latestFU ? $latestFU->user_id : null,
            'is_assisted_by_other' => (bool) ($latestFU && $latestFU->user_id && $latestFU->user_id != $p->sales_id && $latestFU->user_id != $p->owner_id && $latestFU->user_id != $p->cs_id),
            'is_own'              => $isOwn,
            'is_other'            => $isOther,
            'follow_up_count'     => $p->follow_ups_count ?? ($p->relationLoaded('followUps') ? $p->followUps->count() : 0),
            'has_follow_up_history' => $hasHistory,
            'can_update_status'   => (bool) (auth()->user() && auth()->user()->can('updateStatus', $p)),
        ];
    }

    /**
     * Sort "Perlu Follow-Up" array:
     * 1. Terlambat (earliest overdue date first, ascending)
     * 2. Jadwal hari ini (earliest time first, ascending)
     * 3. Belum pernah dihubungi
     * 4. Sudah pernah dihubungi tetapi belum memiliki jadwal berikutnya
     */
    private function sortNeedFollowUp(array &$list): void
    {
        usort($list, function ($a, $b) {
            $prioA = $a['need_fu_priority'] ?? 99;
            $prioB = $b['need_fu_priority'] ?? 99;

            if ($prioA !== $prioB) {
                return $prioA <=> $prioB;
            }

            // Within Terlambat (1) or Hari Ini (2):
            if ($prioA === 1 || $prioA === 2) {
                $timeA = !empty($a['next_follow_up_raw']) ? strtotime($a['next_follow_up_raw']) : (!empty($a['next_follow_up_date']) ? strtotime($a['next_follow_up_date']) : 0);
                $timeB = !empty($b['next_follow_up_raw']) ? strtotime($b['next_follow_up_raw']) : (!empty($b['next_follow_up_date']) ? strtotime($b['next_follow_up_date']) : 0);
                if ($timeA !== $timeB) {
                    return $timeA <=> $timeB;
                }
            }

            if ($prioA === 3) {
                $cA = !empty($a['created_at_raw']) ? strtotime($a['created_at_raw']) : 0;
                $cB = !empty($b['created_at_raw']) ? strtotime($b['created_at_raw']) : 0;
                if ($cA !== $cB) {
                    return $cA <=> $cB;
                }
            }

            $upA = !empty($a['updated_at_raw']) ? strtotime($a['updated_at_raw']) : 0;
            $upB = !empty($b['updated_at_raw']) ? strtotime($b['updated_at_raw']) : 0;
            return $upB <=> $upA;
        });
    }

    /**
     * Sort "Bantuan Sales Lain" array:
     * 1. Terlambat: jadwal follow-up sudah lewat (earliest overdue first)
     * 2. Jadwal hari ini (ascending)
     * 3. Belum pernah dihubungi
     * 4. Belum memiliki jadwal berikutnya setelah pernah di-follow-up
     * 5. Jadwal mendatang, dengan jadwal paling dekat berada paling atas (ascending)
     */
    private function sortOthers(array &$list): void
    {
        usort($list, function ($a, $b) {
            $prioA = $a['others_priority'] ?? 99;
            $prioB = $b['others_priority'] ?? 99;

            if ($prioA !== $prioB) {
                return $prioA <=> $prioB;
            }

            // Within Terlambat (1), Hari Ini (2), and Mendatang (5):
            // Use actual DB date & time: closest/earliest first (ascending)
            if ($prioA === 1 || $prioA === 2 || $prioA === 5) {
                $timeA = !empty($a['next_follow_up_raw']) ? strtotime($a['next_follow_up_raw']) : (!empty($a['next_follow_up_date']) ? strtotime($a['next_follow_up_date']) : 0);
                $timeB = !empty($b['next_follow_up_raw']) ? strtotime($b['next_follow_up_raw']) : (!empty($b['next_follow_up_date']) ? strtotime($b['next_follow_up_date']) : 0);
                if ($timeA !== $timeB) {
                    return $timeA <=> $timeB;
                }
            }

            if ($prioA === 3) {
                $cA = !empty($a['created_at_raw']) ? strtotime($a['created_at_raw']) : 0;
                $cB = !empty($b['created_at_raw']) ? strtotime($b['created_at_raw']) : 0;
                if ($cA !== $cB) {
                    return $cA <=> $cB;
                }
            }

            $upA = !empty($a['updated_at_raw']) ? strtotime($a['updated_at_raw']) : 0;
            $upB = !empty($b['updated_at_raw']) ? strtotime($b['updated_at_raw']) : 0;
            return $upB <=> $upA;
        });
    }

    /**
     * Sort "Terjadwal" array:
     * Jadwal mendatang terdekat berada paling atas (ascending next_follow_up)
     */
    private function sortScheduled(array &$list): void
    {
        usort($list, function ($a, $b) {
            $timeA = !empty($a['next_follow_up_raw']) ? strtotime($a['next_follow_up_raw']) : (!empty($a['next_follow_up_date']) ? strtotime($a['next_follow_up_date']) : PHP_INT_MAX);
            $timeB = !empty($b['next_follow_up_raw']) ? strtotime($b['next_follow_up_raw']) : (!empty($b['next_follow_up_date']) ? strtotime($b['next_follow_up_date']) : PHP_INT_MAX);
            if ($timeA !== $timeB) {
                return $timeA <=> $timeB;
            }

            $upA = !empty($a['updated_at_raw']) ? strtotime($a['updated_at_raw']) : 0;
            $upB = !empty($b['updated_at_raw']) ? strtotime($b['updated_at_raw']) : 0;
            return $upB <=> $upA;
        });
    }

    /**
     * Sort "Selesai" array:
     * Latest updated first (descending updated_at)
     */
    private function sortDone(array &$list): void
    {
        usort($list, function ($a, $b) {
            $upA = !empty($a['updated_at_raw']) ? strtotime($a['updated_at_raw']) : 0;
            $upB = !empty($b['updated_at_raw']) ? strtotime($b['updated_at_raw']) : 0;
            return $upB <=> $upA;
        });
    }
}

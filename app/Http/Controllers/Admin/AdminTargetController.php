<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Target;
use App\Models\User;
use App\Models\Kunjungan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminTargetController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        // 1. Scoped Targets list based on user authorization
        $targetsQuery = Target::with(['sales', 'spv', 'wilayah', 'allocator'])->latest();

        if (strtolower($user->role) === 'hm') {
            $hmWilayahIds = $user->activeWilayahIds();
            $hmMemberIds = $user->hmMemberIds();

            $targetsQuery->where(function($q) use ($hmWilayahIds, $hmMemberIds) {
                $q->whereIn('wilayah_id', $hmWilayahIds)
                  ->orWhereIn('sales_id', $hmMemberIds)
                  ->orWhereIn('spv_id', $hmMemberIds);
            });
        } elseif (strtolower($user->role) === 'spv') {
            $spvTeamIds = $user->teamMemberIds();
            $spvTeamIds[] = $user->id;

            $targetsQuery->where(function($q) use ($user, $spvTeamIds) {
                $q->where('spv_id', $user->id)
                  ->orWhereIn('sales_id', $spvTeamIds);
            });
        }

        $targets = $targetsQuery->get()->map(function ($t) {
            $targetUser = $t->sales ?? $t->spv;
            $salesName = $targetUser ? $targetUser->name : ($t->spv ? $t->spv->name : '-');
            $userRole = $targetUser ? $targetUser->role : ($t->target_type === 'Wilayah' || $t->spv_id ? 'SPV' : '-');
            
            $words = array_values(array_filter(explode(' ', trim($salesName))));
            $avatar = '';
            if (!empty($words) && $words[0] !== '-') {
                $avatar = strtoupper(substr($words[0] ?? '', 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
            }
            if (!$avatar || $avatar === '-') $avatar = 'NA';

            $salesId = $t->sales_id ?: $t->spv_id;

            $realisasi_kunjungan = $salesId ? Kunjungan::where('sales_id', $salesId)
                ->whereBetween('tanggal', [$t->tanggal_mulai, $t->tanggal_selesai])
                ->count() : 0;

            // Realisasi Kontak
            $realisasi_kontak = \App\Models\Prospek::where(function($q) use ($salesId, $targetUser) {
                if ($targetUser && $targetUser->role === 'CS') {
                    $q->where('cs_id', $salesId);
                } else {
                    $q->where('sales_id', $salesId);
                }
            })->whereBetween('created_at', [$t->tanggal_mulai . ' 00:00:00', $t->tanggal_selesai . ' 23:59:59'])->count();

            // Realisasi Menghubungi (khusus CS)
            $realisasi_menghubungi = 0;
            if ($targetUser && $targetUser->role === 'CS') {
                $realisasi_menghubungi = \App\Models\FollowUp::where('user_id', $salesId)
                    ->whereBetween('tanggal', [$t->tanggal_mulai, $t->tanggal_selesai])
                    ->distinct('prospek_id')
                    ->count('prospek_id');
            }

            // Realisasi Follow-up
            $realisasi_followup = $salesId ? \App\Models\FollowUp::where('user_id', $salesId)
                ->whereBetween('tanggal', [$t->tanggal_mulai, $t->tanggal_selesai])
                ->count() : 0;

            // Realisasi Lunas & Formulir
            if ($userRole === 'SPV' || $t->target_type === 'Wilayah') {
                $spvUser = $targetUser ?? $t->spv;
                $teamIds = $spvUser ? $spvUser->teamMemberIds() : [];
                if ($spvUser) $teamIds[] = $spvUser->id;

                $realisasi_lunas = \App\Models\Prospek::whereIn('sales_id', $teamIds)
                    ->where('status', 'LUNAS')
                    ->whereBetween('updated_at', [$t->tanggal_mulai . ' 00:00:00', $t->tanggal_selesai . ' 23:59:59'])
                    ->count();
                $realisasi_formulir = \App\Models\Prospek::where(function($q) use ($teamIds) {
                        $q->whereIn('sales_id', $teamIds)->orWhereIn('cs_id', $teamIds);
                    })
                    ->whereIn('status', ['FORMULIR', 'BERKAS', 'LUNAS'])
                    ->whereBetween('updated_at', [$t->tanggal_mulai . ' 00:00:00', $t->tanggal_selesai . ' 23:59:59'])
                    ->count();
            } else {
                $realisasi_lunas = \App\Models\Prospek::where('sales_id', $salesId)
                    ->where('status', 'LUNAS')
                    ->whereBetween('updated_at', [$t->tanggal_mulai . ' 00:00:00', $t->tanggal_selesai . ' 23:59:59'])
                    ->count();
                $realisasi_formulir = \App\Models\Prospek::where(function($q) use ($salesId) {
                        $q->where('sales_id', $salesId)->orWhere('cs_id', $salesId);
                    })
                    ->whereIn('status', ['FORMULIR', 'BERKAS', 'LUNAS'])
                    ->whereBetween('updated_at', [$t->tanggal_mulai . ' 00:00:00', $t->tanggal_selesai . ' 23:59:59'])
                    ->count();
            }

                $kekurangan_lunas = max(0, ($t->target_lunas ?? 0) - $realisasi_lunas);
                $kekurangan_formulir = max(0, ($t->target_formulir ?? 0) - $realisasi_formulir);
                $kekurangan_kontak = max(0, $t->target_kontak - $realisasi_kontak);
                $kekurangan_menghubungi = max(0, ($t->target_menghubungi ?? 0) - $realisasi_menghubungi);
                $kekurangan_followup = max(0, $t->target_followup - $realisasi_followup);
                $kekurangan_kunjungan = max(0, $t->target_kunjungan - $realisasi_kunjungan);

                // Target Besok (Carry-Over akumulasi kekurangan sesuai aturan PRD)
                if ($t->tipe_periode === 'Harian') {
                    $target_besok_kontak = $t->target_kontak + $kekurangan_kontak;
                    $target_besok_followup = $t->target_followup + $kekurangan_followup;
                } else {
                    $endDate = \Carbon\Carbon::parse($t->tanggal_selesai)->endOfDay();
                    $sisaHari = max(1, \Carbon\Carbon::now()->diffInDays($endDate, false) + 1);
                    $target_besok_kontak = (int)ceil($kekurangan_kontak / $sisaHari);
                    $target_besok_followup = (int)ceil($kekurangan_followup / $sisaHari);
                }

                return [
                    'id' => $t->id,
                    'sales' => $salesName,
                    'role' => $userRole,
                    'avatar' => $avatar,
                    'allocated_by' => $t->allocator?->name ?? 'Head of Marketing',
                    'tahun_akademik' => $t->tahun_akademik ?? '2027/2028',
                    'periode' => \Carbon\Carbon::parse($t->tanggal_mulai)->translatedFormat('F Y'),
                    'periode_type' => $t->tipe_periode,
                    'tanggal_mulai' => \Carbon\Carbon::parse($t->tanggal_mulai)->format('d M Y'),
                    'tanggal_selesai' => \Carbon\Carbon::parse($t->tanggal_selesai)->format('d M Y'),
                    'target_lunas' => $t->target_lunas ?? 0,
                    'target_formulir' => $t->target_formulir ?? 0,
                    'target_kontak' => $t->target_kontak,
                    'target_menghubungi' => $t->target_menghubungi ?? 0,
                    'target_followup' => $t->target_followup,
                    'target_kunjungan' => $t->target_kunjungan,
                    'wilayah_nama' => $t->wilayah?->nama ?? ($targetUser?->wilayah?->nama ?? '-'),
                    'spv_nama' => $t->spv?->name ?? ($targetUser?->supervisor?->name ?? $salesName),
                    'wilayah_id' => $t->wilayah_id,
                    'spv_id' => $t->spv_id,
                    
                    'realisasi_lunas' => $realisasi_lunas,
                    'realisasi_formulir' => $realisasi_formulir,
                    'realisasi_kontak' => $realisasi_kontak,

                    'realisasi_menghubungi' => $realisasi_menghubungi,
                    'realisasi_followup' => $realisasi_followup,
                    'realisasi_kunjungan' => $realisasi_kunjungan,
                    
                    'kekurangan_lunas' => $kekurangan_lunas,
                    'kekurangan_formulir' => $kekurangan_formulir,
                    'kekurangan_kontak' => $kekurangan_kontak,
                    'kekurangan_menghubungi' => $kekurangan_menghubungi,
                    'kekurangan_followup' => $kekurangan_followup,
                    'kekurangan_kunjungan' => $kekurangan_kunjungan,

                    'akum_kontak' => $kekurangan_kontak,
                    'akum_menghubungi' => $kekurangan_menghubungi,
                    'akum_followup' => $kekurangan_followup,
                    'target_besok_kontak' => $target_besok_kontak,
                    'target_besok_followup' => $target_besok_followup,
                    
                    'status' => $t->status,
                    'sales_id' => $t->sales_id,
                    'tipe_periode' => $t->tipe_periode,
                    'raw_tanggal_mulai' => $t->tanggal_mulai,
                    'raw_tanggal_selesai' => $t->tanggal_selesai,
                ];
            });

        // 2. Build structured tree for cascading dropdowns (Wilayah -> SPV -> Area -> Sales/CS)
        if (strtolower($user->role) === 'admin') {
            $kotas = \App\Models\Wilayah::where(function($q) {
                $q->whereNull('parent_id')->orWhere('level', 'Kota/Kabupaten');
            })->where('status', 'Aktif')->orderBy('nama')->get();
        } else {
            $accessibleWilayahIds = $user->activeWilayahIds();
            $kotas = \App\Models\Wilayah::whereIn('id', $accessibleWilayahIds)
                ->where('status', 'Aktif')
                ->get()
                ->map(function($w) {
                    return $w->parent_id ? \App\Models\Wilayah::find($w->parent_id) : $w;
                })
                ->filter()
                ->unique('id')
                ->values();

            if ($kotas->isEmpty() && $user->wilayah_id) {
                $w = \App\Models\Wilayah::find($user->wilayah_id);
                if ($w) {
                    $kota = $w->parent_id ? \App\Models\Wilayah::find($w->parent_id) : $w;
                    if ($kota) $kotas = collect([$kota]);
                }
            }

            if ($kotas->isEmpty()) {
                $kotas = \App\Models\Wilayah::where(function($q) {
                    $q->whereNull('parent_id')->orWhere('level', 'Kota/Kabupaten');
                })->where('status', 'Aktif')->orderBy('nama')->get();
            }
        }

        $wilayahTree = $kotas->map(function ($kota) {
            $spvs = User::where('role', 'SPV')
                ->where('status', 'Aktif')
                ->where(function ($q) use ($kota) {
                    $q->where('wilayah_id', $kota->id)
                      ->orWhereHas('activeWilayahes', function ($wq) use ($kota) {
                          $wq->where('wilayah_id', $kota->id);
                      });
                })
                ->orderBy('name')
                ->get()
                ->map(function ($spv) {
                    return [
                        'id'   => $spv->id,
                        'name' => $spv->name,
                        'role' => $spv->role,
                        'kode' => $spv->kode ?? '-',
                    ];
                });

            $spvIds = $spvs->pluck('id')->toArray();

            $kotaSalesList = User::whereIn('role', ['Sales', 'CS'])
                ->where('status', 'Aktif')
                ->where(function ($q) use ($kota, $spvIds) {
                    $q->where('wilayah_id', $kota->id)
                      ->orWhereHas('activeWilayahes', function ($wq) use ($kota) {
                          $wq->where('wilayah_id', $kota->id);
                      });

                    if (!empty($spvIds)) {
                        $q->orWhereIn('supervisor_id', $spvIds);
                    }
                })
                ->orderBy('role')
                ->orderBy('name')
                ->get()
                ->map(function ($s) {
                    return [
                        'id'         => $s->id,
                        'name'       => $s->name,
                        'role'       => $s->role,
                        'kode'       => $s->kode ?? '-',
                        'wilayah_id' => $s->wilayah_id,
                    ];
                });

            return [
                'id'         => $kota->id,
                'nama'       => $kota->nama,
                'spvs'       => $spvs->toArray(),
                'sales_list' => $kotaSalesList->toArray(),
            ];
        })->values()->toArray();

        $salesList = User::whereIn('role', ['HM', 'SPV', 'Sales', 'CS'])->orderBy('role')->orderBy('name')->get();

        return view('admin.target.index', compact('targets', 'wilayahTree', 'salesList'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'wilayah_id'         => 'required|exists:wilayahs,id',
            'spv_id'             => 'nullable|exists:users,id',
            'sales_id'           => 'required|exists:users,id',
            'tipe_periode'       => 'required|in:Harian,Mingguan,Bulanan,Tahunan',
            'tanggal_mulai'      => 'required|date',
            'tanggal_selesai'    => 'required|date|after_or_equal:tanggal_mulai',
            'target_lunas'       => 'nullable|integer|min:0',
            'target_formulir'    => 'nullable|integer|min:0',
            'target_pemberkasan' => 'nullable|integer|min:0',
            'target_kontak'      => 'required|integer|min:0',
            'target_menghubungi' => 'nullable|integer|min:0',
            'target_followup'    => 'required|integer|min:0',
            'target_kunjungan'   => 'required|integer|min:0',
            'status'             => 'required|in:Aktif,Selesai,Nonaktif',
            'tahun_akademik'     => 'nullable|string|max:20',
        ]);

        $user = auth()->user();
        $targetWilayah = \App\Models\Wilayah::findOrFail($request->wilayah_id);

        // 1. Strict Backend Security Boundary Check: Logged-in user Wilayah Scope
        if (strtolower($user->role) !== 'admin') {
            $hmWilayahIds = $user->activeWilayahIds();
            $inScope = $user->isWithinWilayahScope($targetWilayah->id)
                || in_array($targetWilayah->id, $hmWilayahIds)
                || ($targetWilayah->parent_id && in_array($targetWilayah->parent_id, $hmWilayahIds));

            if (!$inScope) {
                abort(403, 'Anda tidak memiliki hak akses untuk mengelola target pada wilayah ini.');
            }
        }

        // 2. Resolve & validate SPV assignment from Wilayah Saya
        $targetUser = User::findOrFail($request->sales_id);
        $spvId = $request->spv_id;

        if (strtolower($targetUser->role) === 'spv' || strtolower($targetUser->role) === 'hm') {
            $spvId = $targetUser->id;
            $validated['target_type'] = 'Wilayah';
        } else {
            $validated['target_type'] = 'Individual';
            if (!$spvId) {
                $spvId = $targetUser->supervisor_id;
            }
        }

        // Check if selected SPV is assigned to target Wilayah by HM in Wilayah Saya
        if ($spvId) {
            $spvUser = User::find($spvId);
            if ($spvUser) {
                $spvInWilayah = $spvUser->wilayah_id == $targetWilayah->id
                    || $spvUser->isWithinWilayahScope($targetWilayah->id)
                    || $targetWilayah->isDescendantOf($spvUser->wilayah_id)
                    || in_array($targetWilayah->id, $spvUser->activeWilayahIds());

                if (!$spvInWilayah) {
                    abort(403, 'SPV yang dipilih tidak ditugaskan pada Wilayah yang ditentukan.');
                }
            }
        }

        // 3. Check if target user (Sales/CS) belongs to target Wilayah scope or SPV
        if (strtolower($targetUser->role) !== 'spv' && strtolower($user->role) !== 'admin') {
            $userInWilayah = $targetUser->isWithinWilayahScope($targetWilayah->id)
                || $targetWilayah->isDescendantOf($targetUser->wilayah_id)
                || ($spvId && $targetUser->supervisor_id == $spvId)
                || in_array($targetUser->id, $user->hmMemberIds())
                || in_array($targetWilayah->id, $targetUser->activeWilayahIds());

            if (!$userInWilayah) {
                abort(403, 'Penerima target tidak berada dalam cakupan Wilayah / SPV yang ditentukan.');
            }
        }


        // 4. Validate Target Cascading Constraints
        $ta = $validated['tahun_akademik'] ?? '2027/2028';
        $newTargetLunas = (int)($validated['target_lunas'] ?? 0);

        if (strtolower($targetUser->role) === 'sales' || strtolower($targetUser->role) === 'cs') {
            if ($spvId) {
                $spvTarget = Target::where('sales_id', $spvId)
                    ->where('status', 'Aktif')
                    ->where(function($q) use ($ta) {
                        $q->where('tahun_akademik', $ta)->orWhereNull('tahun_akademik');
                    })
                    ->latest()
                    ->first();

                if ($spvTarget && $spvTarget->target_lunas > 0) {
                    $otherSalesAllocated = Target::where('spv_id', $spvId)
                        ->where('target_type', 'Individual')
                        ->where('status', 'Aktif')
                        ->where(function($q) use ($ta) {
                            $q->where('tahun_akademik', $ta)->orWhereNull('tahun_akademik');
                        })
                        ->sum('target_lunas');

                    if (($otherSalesAllocated + $newTargetLunas) > $spvTarget->target_lunas) {
                        return redirect()->back()
                            ->withInput()
                            ->with('error', "Total alokasi target Sales/CS melebihi Target SPV (" . $spvTarget->target_lunas . ").");
                    }
                }
            }
        } elseif (strtolower($targetUser->role) === 'spv') {
            // Check SPV allocation against HM's Global Target
            $hmUsers = User::where('role', 'HM')->where('status', 'Aktif')->get();
            $hmTargetTotal = 0;
            foreach ($hmUsers as $hm) {
                $hmTarget = Target::where('sales_id', $hm->id)
                    ->where('status', 'Aktif')
                    ->where(function($q) use ($ta) {
                        $q->where('tahun_akademik', $ta)->orWhereNull('tahun_akademik');
                    })
                    ->latest()
                    ->first();
                if ($hmTarget) {
                    $hmTargetTotal += $hmTarget->target_lunas;
                }
            }

            if ($hmTargetTotal > 0) {
                $otherSpvAllocated = Target::whereHas('sales', function($q) {
                        $q->where('role', 'SPV');
                    })
                    ->where('status', 'Aktif')
                    ->where(function($q) use ($ta) {
                        $q->where('tahun_akademik', $ta)->orWhereNull('tahun_akademik');
                    })
                    ->sum('target_lunas');

                if (($otherSpvAllocated + $newTargetLunas) > $hmTargetTotal) {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', "Total alokasi target SPV melebihi Target Global HM (" . $hmTargetTotal . ").");
                }
            }
        }

        $validated['spv_id'] = $spvId;
        $validated['wilayah_id'] = $targetWilayah->id;
        $validated['allocated_by'] = $user->id;
        $validated['target_lunas'] = (int)($validated['target_lunas'] ?? 0);
        $validated['target_formulir'] = (int)($validated['target_formulir'] ?? 0);
        $validated['target_pemberkasan'] = (int)($validated['target_pemberkasan'] ?? 0);
        if (empty($validated['tahun_akademik'])) {
            $validated['tahun_akademik'] = '2027/2028';
        }

        $target = Target::create($validated);

        $allocator = auth()->user();
        $tipe = $target->tipe_periode ?? 'Bulanan';
        $lunas = $target->target_lunas ?? 0;
        $kontak = $target->target_kontak ?? 0;

        if ($targetUser) {
            $isSpv = $targetUser->role === 'SPV';
            $targetUser->notify(new \App\Notifications\TargetNotification(
                title: $isSpv ? 'Target Baru dari Head of Marketing' : 'Target Baru Ditugaskan',
                message: $isSpv
                    ? "Head of Marketing telah menetapkan target {$tipe}: {$lunas} Maba Lunas, {$target->target_formulir} Formulir, dan {$kontak} Kontak Baru. Segera distribusikan ke tim Sales & CS Anda."
                    : "Target {$tipe} telah ditetapkan untuk Anda: {$lunas} Maba Lunas, {$target->target_formulir} Formulir, dan {$kontak} Kontak Baru.",
                type: 'info',
                link: route($isSpv ? 'spv.performa.index' : 'performa.index'),
                icon: '',
                extraData: ['target_id' => $target->id, 'event_type' => 'target_assigned']
            ));
        }

        if ($allocator && $targetUser && $allocator->id !== $targetUser->id) {
            $allocator->notify(new \App\Notifications\TargetNotification(
                title: 'Target Berhasil Diberikan',
                message: "Target {$tipe} berhasil diberikan kepada {$targetUser->name} ({$targetUser->role}) sejumlah {$lunas} Maba Lunas.",
                type: 'success',
                link: route('admin.target.index'),
                icon: '',
                extraData: ['target_id' => $target->id, 'event_type' => 'target_given']
            ));
        }

        $roleName = $targetUser ? $targetUser->role : 'User';
        return redirect()->back()->with('success', "Target untuk {$roleName} '{$targetUser->name}' di Wilayah '{$targetWilayah->nama}' berhasil ditambahkan!");
    }

    public function update(Request $request, Target $target)
    {
        \Illuminate\Support\Facades\Gate::authorize('update', $target);

        $validated = $request->validate([
            'sales_id'           => 'required|exists:users,id',
            'wilayah_id'         => 'nullable|exists:wilayahs,id',
            'spv_id'             => 'nullable|exists:users,id',
            'tipe_periode'       => 'required|in:Harian,Mingguan,Bulanan,Tahunan',
            'tanggal_mulai'      => 'required|date',
            'tanggal_selesai'    => 'required|date|after_or_equal:tanggal_mulai',
            'target_lunas'       => 'nullable|integer|min:0',
            'target_formulir'    => 'nullable|integer|min:0',
            'target_kontak'      => 'required|integer|min:0',
            'target_menghubungi' => 'nullable|integer|min:0',
            'target_followup'    => 'required|integer|min:0',
            'target_kunjungan'   => 'required|integer|min:0',
            'status'             => 'required|in:Aktif,Selesai,Nonaktif',
            'tahun_akademik'     => 'nullable|string|max:20',
        ]);

        $user = auth()->user();

        if ($request->filled('wilayah_id')) {
            $targetWilayah = \App\Models\Wilayah::findOrFail($request->wilayah_id);
            if (strtolower($user->role) !== 'admin') {
                if (!$user->isWithinWilayahScope($targetWilayah->id) && !in_array($targetWilayah->id, $user->activeWilayahIds())) {
                    abort(403, 'Anda tidak memiliki hak akses untuk mengelola target pada wilayah ini.');
                }
            }
            $validated['wilayah_id'] = $targetWilayah->id;
        }

        $targetUser = User::findOrFail($request->sales_id);
        if ($targetUser->role === 'SPV') {
            $validated['spv_id'] = $targetUser->id;
            $validated['target_type'] = 'Wilayah';
        }

        $validated['allocated_by'] = $user->id;
        $target->update($validated);

        $targetUser = User::find($target->sales_id);
        if ($targetUser) {
            $isSpv = $targetUser->role === 'SPV';
            $targetUser->notify(new \App\Notifications\TargetNotification(
                title: 'Pembaruan Target',
                message: "Target {$target->tipe_periode} Anda telah diperbarui menjadi {$target->target_lunas} Maba Lunas dan {$target->target_kontak} Kontak Baru.",
                type: 'info',
                link: route($isSpv ? 'spv.performa.index' : 'performa.index'),
                icon: '',
                extraData: ['target_id' => $target->id, 'event_type' => 'target_updated']
            ));
        }

        return redirect()->back()->with('success', 'Target berhasil diperbarui dan notifikasi telah dikirim!');
    }


    public function lock(Request $request, Target $target)
    {
        \Illuminate\Support\Facades\Gate::authorize('lock', $target);

        $target->lock(auth()->user());

        return redirect()->back()->with('success', 'Target berhasil dikunci (locked).');
    }

    public function unlock(Request $request, Target $target)
    {
        \Illuminate\Support\Facades\Gate::authorize('unlock', $target);

        $target->unlock();

        return redirect()->back()->with('success', 'Target berhasil dibuka kuncinya (unlocked).');
    }

    public function destroy(Target $target)
    {
        \Illuminate\Support\Facades\Gate::authorize('delete', $target);

        $target->delete();
        return redirect()->back()->with('success', 'Target berhasil dihapus!');
    }
}

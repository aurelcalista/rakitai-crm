<?php

namespace App\Services;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class EventAssignmentService
{
    /**
     * Validate if a Sales user can be assigned to an event on a specific date and time.
     * Rules:
     * - No overlapping events.
     * - Minimum 2-hour gap between events.
     *
     * @param int $salesId
     * @param string $tanggal (Y-m-d)
     * @param string $waktuMulai (H:i)
     * @param string $waktuSelesai (H:i)
     * @param int|null $excludeEventId (to ignore the current event when editing)
     * @throws ValidationException
     */
    public function validateSalesSchedule(int $salesId, string $tanggal, string $waktuMulai, string $waktuSelesai, ?int $excludeEventId = null)
    {
        $newStart = Carbon::parse($tanggal . ' ' . $waktuMulai);
        $newEnd = Carbon::parse($tanggal . ' ' . $waktuSelesai);

        if ($newEnd->lte($newStart)) {
            throw ValidationException::withMessages([
                'waktu_selesai' => 'Waktu selesai harus setelah waktu mulai.'
            ]);
        }

        // Get all events assigned to or created by this user on the same date
        $existingEvents = Event::where(function($query) use ($salesId) {
                $query->where('eo_id', $salesId)
                      ->orWhereHas('sales', function ($q) use ($salesId) {
                          $q->where('users.id', $salesId);
                      });
            })
            ->whereDate('tanggal_mulai', $tanggal)
            ->when($excludeEventId, function ($q) use ($excludeEventId) {
                $q->where('id', '!=', $excludeEventId);
            })
            ->get();

        foreach ($existingEvents as $existing) {
            $eDate = $existing->tanggal ? Carbon::parse($existing->tanggal)->format('Y-m-d') : Carbon::parse($existing->tanggal_mulai)->format('Y-m-d');
            $eStartStr = $existing->tanggal_mulai ?: ($eDate . ' ' . $existing->waktu_mulai);
            $eEndStr = $existing->tanggal_selesai ?: ($eDate . ' ' . $existing->waktu_selesai);

            $existingStart = Carbon::parse($eStartStr);
            $existingEnd = Carbon::parse($eEndStr);

            // Check overlap
            if ($newStart->lt($existingEnd) && $newEnd->gt($existingStart)) {
                throw ValidationException::withMessages([
                    'waktu_mulai' => "Jadwal bentrok dengan event/schedule '{$existing->name}' ({$existingStart->format('H:i')} - {$existingEnd->format('H:i')})."
                ]);
            }

            // Check minimum 1-hour gap (60 minutes)
            if ($newStart->gte($existingEnd)) {
                $gap = $existingEnd->diffInMinutes($newStart);
                if ($gap < 60) {
                    throw ValidationException::withMessages([
                        'waktu_mulai' => "Jeda kurang dari 1 jam dengan event/schedule sebelumnya '{$existing->name}' (selesai {$existingEnd->format('H:i')}). Minimal jeda 1 jam."
                    ]);
                }
            } elseif ($newEnd->lte($existingStart)) {
                $gap = $newEnd->diffInMinutes($existingStart);
                if ($gap < 60) {
                    throw ValidationException::withMessages([
                        'waktu_mulai' => "Jeda kurang dari 1 jam dengan event/schedule berikutnya '{$existing->name}' (mulai {$existingStart->format('H:i')}). Minimal jeda 1 jam."
                    ]);
                }
            }
        }
    }
}

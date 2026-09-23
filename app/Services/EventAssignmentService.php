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

        // Get all events assigned to this sales on the same date
        $existingEvents = Event::whereHas('sales', function ($q) use ($salesId) {
                $q->where('users.id', $salesId);
            })
            ->whereDate('tanggal_mulai', $tanggal)
            ->when($excludeEventId, function ($q) use ($excludeEventId) {
                $q->where('id', '!=', $excludeEventId);
            })
            ->get();

        foreach ($existingEvents as $existing) {
            $existingStart = Carbon::parse($existing->tanggal_mulai);
            $existingEnd = Carbon::parse($existing->tanggal_selesai);

            // Check overlap
            if ($newStart->lt($existingEnd) && $newEnd->gt($existingStart)) {
                throw ValidationException::withMessages([
                    'sales_id' => "Jadwal bentrok dengan event '{$existing->name}' ({$existingStart->format('H:i')} - {$existingEnd->format('H:i')})."
                ]);
            }

            // Check 2-hour gap
            if ($newStart->gte($existingEnd)) {
                $gap = $newStart->diffInMinutes($existingEnd);
                if ($gap < 120) {
                    throw ValidationException::withMessages([
                        'sales_id' => "Jeda kurang dari 2 jam dengan event sebelumnya '{$existing->name}' (selesai {$existingEnd->format('H:i')})."
                    ]);
                }
            } elseif ($newEnd->lte($existingStart)) {
                $gap = $newEnd->diffInMinutes($existingStart);
                if ($gap < 120) {
                    throw ValidationException::withMessages([
                        'sales_id' => "Jeda kurang dari 2 jam dengan event berikutnya '{$existing->name}' (mulai {$existingStart->format('H:i')})."
                    ]);
                }
            }
        }
    }
}

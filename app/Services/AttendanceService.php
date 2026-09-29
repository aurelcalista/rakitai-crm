<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceLocation;
use App\Models\User;
use App\Notifications\CrmActivityNotification;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    /**
     * Check if user already checked in today.
     */
    public function hasCheckedInToday(int $userId): bool
    {
        return Attendance::where('user_id', $userId)
            ->whereDate('check_in_at', Carbon::today('Asia/Jakarta'))
            ->where('status', 'present')
            ->exists();
    }

    /**
     * Process attendance check-in.
     */
    public function checkIn(User $user, int $locationId, float $userLat, float $userLng, UploadedFile $photoFile, ?string $notes = null): Attendance
    {
        // 1. Role validation
        $allowedRoles = ['sales', 'spv', 'eo'];
        if (!in_array(strtolower($user->role), $allowedRoles)) {
            throw ValidationException::withMessages([
                'role' => 'Role Anda (' . $user->role . ') tidak memiliki akses untuk melakukan absensi.',
            ]);
        }

        // 2. Prevent duplicate check-in today
        if ($this->hasCheckedInToday($user->id)) {
            throw ValidationException::withMessages([
                'duplicate' => 'Anda sudah melakukan absensi hari ini.',
            ]);
        }

        // 3. Location validation
        $location = AttendanceLocation::active()->find($locationId);
        if (!$location) {
            throw ValidationException::withMessages([
                'attendance_location_id' => 'Lokasi absensi tidak ditemukan atau sudah tidak aktif.',
            ]);
        }

        // 4. Validate coordinates range
        if ($userLat < -90 || $userLat > 90 || $userLng < -180 || $userLng > 180) {
            throw ValidationException::withMessages([
                'coordinates' => 'Koordinat GPS user tidak valid.',
            ]);
        }

        // 5. Server calculates distance using Haversine
        $distance = GeoLocationService::calculateDistance(
            (float) $userLat,
            (float) $userLng,
            (float) $location->latitude,
            (float) $location->longitude
        );

        // 6. Radius check
        if ($distance > $location->radius) {
            throw ValidationException::withMessages([
                'radius' => 'Anda berada di luar area absensi. Jarak Anda: ' . number_format($distance, 2) . ' meter dari ' . $location->name . ' (Maksimal: ' . $location->radius . ' meter).',
            ]);
        }

        // 7. Process, resize and compress selfie photo into private storage
        $savedPath = $this->processAndStoreSelfie($photoFile);

        // 8. Create attendance record with 6 decimal precision
        $attendance = Attendance::create([
            'user_id'                => $user->id,
            'attendance_location_id' => $location->id,
            'check_in_at'            => Carbon::now('Asia/Jakarta'),
            'latitude'               => round($userLat, 6),
            'longitude'              => round($userLng, 6),
            'distance'               => round($distance, 2),
            'selfie_path'            => $savedPath,
            'status'                 => 'present',
            'notes'                  => $notes,
        ]);

        // 9. Send database notification
        try {
            $user->notify(new CrmActivityNotification(
                title: 'Absensi Berhasil',
                message: 'Absensi berhasil di ' . $location->name . ' (Jarak: ' . number_format($distance, 2) . 'm) pada ' . Carbon::now()->format('H:i') . ' WIB.',
                type: 'success',
                link: route('attendance.history'),
                icon: '📍',
                senderName: 'Sistem Absensi',
                senderRole: 'System',
                action: 'attendance_checkin'
            ));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed sending attendance notification: ' . $e->getMessage());
        }

        return $attendance;
    }

    /**
     * Process, resize, compress, and store photo in private storage.
     * Target max dimension: 1280px, target size: ~500KB - 1MB.
     */
    public function processAndStoreSelfie(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mime = $file->getMimeType();

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mime, $allowedMimes)) {
            throw ValidationException::withMessages([
                'photo' => 'Format file foto harus berupa JPG, PNG, atau WebP.',
            ]);
        }

        $sourcePath = $file->getRealPath();
        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($sourcePath),
            'image/png'  => @imagecreatefrompng($sourcePath),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : null,
            default      => null,
        };

        $uniqueName = 'attendances/' . date('Y/m') . '/' . Str::uuid()->toString() . '.jpg';

        if ($image) {
            // Resize if largest side exceeds 1280px
            $origWidth = imagesx($image);
            $origHeight = imagesy($image);
            $maxDim = 1280;

            if ($origWidth > $maxDim || $origHeight > $maxDim) {
                if ($origWidth >= $origHeight) {
                    $newWidth = $maxDim;
                    $newHeight = (int) round(($origHeight / $origWidth) * $maxDim);
                } else {
                    $newHeight = $maxDim;
                    $newWidth = (int) round(($origWidth / $origHeight) * $maxDim);
                }

                $resized = imagescale($image, $newWidth, $newHeight);
                if ($resized) {
                    imagedestroy($image);
                    $image = $resized;
                }
            }

            // Capture output to buffer with 82% quality (producing ~400KB - 800KB)
            ob_start();
            imagejpeg($image, null, 82);
            $binaryData = ob_get_clean();
            imagedestroy($image);

            Storage::disk('local')->put($uniqueName, $binaryData);
        } else {
            // Fallback if GD creation failed
            $uniqueName = 'attendances/' . date('Y/m') . '/' . Str::uuid()->toString() . '.' . $extension;
            Storage::disk('local')->putFileAs(dirname($uniqueName), $file, basename($uniqueName));
        }

        return $uniqueName;
    }

    /**
     * Calculate working days (Monday - Friday) between two dates.
     */
    public function calculateWorkDays(Carbon $startDate, Carbon $endDate): int
    {
        $days = 0;
        $curr = $startDate->copy()->startOfDay();
        $end = $endDate->copy()->startOfDay();
        while ($curr->lte($end)) {
            if ($curr->isWeekday()) {
                $days++;
            }
            $curr->addDay();
        }
        return max(1, $days);
    }

    /**
     * Get monthly attendance statistics and percentage for a user.
     */
    public function getUserAttendanceStats(int $userId, ?int $month = null, ?int $year = null): array
    {
        $month = $month ?: Carbon::now('Asia/Jakarta')->month;
        $year = $year ?: Carbon::now('Asia/Jakarta')->year;

        $startOfMonth = Carbon::create($year, $month, 1, 0, 0, 0, 'Asia/Jakarta');
        $isCurrentMonth = ($year == Carbon::now('Asia/Jakarta')->year && $month == Carbon::now('Asia/Jakarta')->month);
        $endOfPeriod = $isCurrentMonth ? Carbon::now('Asia/Jakarta') : $startOfMonth->copy()->endOfMonth();

        $workDays = $this->calculateWorkDays($startOfMonth, $endOfPeriod);
        $totalWorkDaysInMonth = $this->calculateWorkDays($startOfMonth, $startOfMonth->copy()->endOfMonth());

        $presentDays = Attendance::where('user_id', $userId)
            ->where('status', 'present')
            ->whereMonth('check_in_at', $month)
            ->whereYear('check_in_at', $year)
            ->count();

        $rejectedCount = Attendance::where('user_id', $userId)
            ->where('status', 'rejected')
            ->whereMonth('check_in_at', $month)
            ->whereYear('check_in_at', $year)
            ->count();

        $percentage = min(100, round(($presentDays / $workDays) * 100, 1));
        $validRate = ($presentDays + $rejectedCount) > 0 ? round(($presentDays / ($presentDays + $rejectedCount)) * 100, 1) : 0;

        return [
            'present_days'            => $presentDays,
            'rejected_count'          => $rejectedCount,
            'work_days'               => $workDays,
            'total_work_days_in_month'=> $totalWorkDaysInMonth,
            'percentage'              => $percentage,
            'valid_rate'              => $validRate,
            'month_name'              => $startOfMonth->translatedFormat('F Y'),
        ];
    }
}

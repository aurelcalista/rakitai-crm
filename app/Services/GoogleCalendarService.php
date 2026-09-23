<?php

namespace App\Services;

use App\Models\Event;
use App\Models\User;
use App\Notifications\EventNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class GoogleCalendarService
{
    /**
     * Check if a user has an active/valid Google OAuth connection.
     */
    public function isUserConnected(User $user): bool
    {
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');

        if (empty($clientId) || empty($clientSecret)) {
            return false;
        }

        return !empty($user->google_access_token) || !empty($user->google_refresh_token);
    }

    /**
     * Build standard payload for Google Calendar API.
     */
    public function buildEventPayload(Event $event): array
    {
        $start = Carbon::parse($event->tanggal_mulai ?? ($event->tanggal . ' ' . $event->waktu_mulai));
        $end = Carbon::parse($event->tanggal_selesai ?? ($event->tanggal . ' ' . ($event->waktu_selesai ?? $start->copy()->addHour()->format('H:i:s'))));
        $eoName = $event->eo ? $event->eo->name : 'EO Marketing CIC';

        return [
            'summary'     => $event->nama,
            'location'    => $event->lokasi ?? 'Kantor / Lokasi Event',
            'description' => ($event->deskripsi ?? 'Tugas Event Marketing') . "\n\nEO Penanggung Jawab: " . $eoName,
            'start'       => [
                'dateTime' => $start->toIso8601String(),
                'timeZone' => config('app.timezone', 'Asia/Jakarta'),
            ],
            'end'         => [
                'dateTime' => $end->toIso8601String(),
                'timeZone' => config('app.timezone', 'Asia/Jakarta'),
            ],
            'organizer'   => [
                'displayName' => $eoName,
            ],
        ];
    }

    /**
     * Idempotently synchronize an event to a Sales user's Google Calendar.
     * If user is connected to Google OAuth, calls Google API (create or update).
     * If not connected or credentials absent, falls back cleanly to internal calendar & notification.
     */
    public function syncSalesEvent(Event $event, User $salesUser): array
    {
        // Fetch pivot record for google_event_id tracking
        $pivot = DB::table('event_sales')
            ->where('event_id', $event->id)
            ->where('sales_id', $salesUser->id)
            ->first();

        $existingGoogleEventId = $pivot?->google_event_id;
        $payload = $this->buildEventPayload($event);

        if (!$this->isUserConnected($salesUser)) {
            // Fallback internal: Ensure internal notification is dispatched
            Notification::send([$salesUser], new EventNotification($event, 'assigned_sales'));

            return [
                'status'           => 'fallback_internal',
                'synced'           => false,
                'sales_id'         => $salesUser->id,
                'event_id'         => $event->id,
                'google_event_id'  => $existingGoogleEventId,
                'message'          => 'Google OAuth not connected for user. Saved to internal calendar fallback.',
                'payload'          => $payload,
            ];
        }

        // Handle Google OAuth API Request (create or update)
        try {
            $accessToken = $this->ensureValidAccessToken($salesUser);

            if ($existingGoogleEventId) {
                // Update existing Google Calendar Event (Idempotent update)
                $response = Http::withToken($accessToken)
                    ->put("https://www.googleapis.com/calendar/v3/calendars/primary/events/{$existingGoogleEventId}", $payload);
                
                if ($response->successful()) {
                    return [
                        'status'          => 'updated',
                        'synced'          => true,
                        'sales_id'        => $salesUser->id,
                        'event_id'        => $event->id,
                        'google_event_id' => $existingGoogleEventId,
                        'payload'         => $payload,
                    ];
                }
            }

            // Create new Google Calendar Event
            $response = Http::withToken($accessToken)
                ->post('https://www.googleapis.com/calendar/v3/calendars/primary/events', $payload);

            if ($response->successful()) {
                $googleEventId = $response->json('id');

                // Save google_event_id to pivot table safely
                try {
                    $updated = DB::table('event_sales')
                        ->where('event_id', $event->id)
                        ->where('sales_id', $salesUser->id)
                        ->update(['google_event_id' => $googleEventId]);

                    if (!$updated) {
                        $this->deleteGoogleEventQuietly($accessToken, $googleEventId);
                        Log::error("Google Calendar Sync DB save affected 0 rows for sales_id {$salesUser->id}. Cleaned up created Google event {$googleEventId}.");

                        Notification::send([$salesUser], new EventNotification($event, 'assigned_sales'));
                        return [
                            'status'          => 'fallback_internal',
                            'synced'          => false,
                            'sales_id'        => $salesUser->id,
                            'event_id'        => $event->id,
                            'google_event_id' => null,
                            'message'         => 'DB update affected 0 rows. Cleaned up created Google event.',
                            'payload'         => $payload,
                        ];
                    }
                } catch (\Throwable $dbException) {
                    $this->deleteGoogleEventQuietly($accessToken, $googleEventId);
                    Log::error("Google Calendar Sync DB exception for sales_id {$salesUser->id}: {$dbException->getMessage()}. Cleaned up created Google event {$googleEventId}.");

                    Notification::send([$salesUser], new EventNotification($event, 'assigned_sales'));
                    return [
                        'status'          => 'fallback_internal',
                        'synced'          => false,
                        'sales_id'        => $salesUser->id,
                        'event_id'        => $event->id,
                        'google_event_id' => null,
                        'message'         => 'DB update exception. Cleaned up created Google event.',
                        'payload'         => $payload,
                    ];
                }

                return [
                    'status'          => 'created',
                    'synced'          => true,
                    'sales_id'        => $salesUser->id,
                    'event_id'        => $event->id,
                    'google_event_id' => $googleEventId,
                    'payload'         => $payload,
                ];
            }

            Log::warning("Google Calendar Sync failed for sales_id {$salesUser->id}: " . $response->body());
        } catch (\Throwable $e) {
            Log::error("Google Calendar Sync Exception for sales_id {$salesUser->id}: " . $e->getMessage());
        }

        // Fallback if API call failed
        Notification::send([$salesUser], new EventNotification($event, 'assigned_sales'));

        return [
            'status'          => 'fallback_internal',
            'synced'          => false,
            'sales_id'        => $salesUser->id,
            'event_id'        => $event->id,
            'google_event_id' => $existingGoogleEventId,
            'message'         => 'Google API call failed. Fallback to internal calendar.',
            'payload'         => $payload,
        ];
    }

    /**
     * Cancel/delete Google Calendar event when Sales is removed from event.
     */
    public function removeSalesEvent(Event $event, User $salesUser): array
    {
        $pivot = DB::table('event_sales')
            ->where('event_id', $event->id)
            ->where('sales_id', $salesUser->id)
            ->first();

        $googleEventId = $pivot?->google_event_id;

        if ($googleEventId && $this->isUserConnected($salesUser)) {
            try {
                $accessToken = $this->ensureValidAccessToken($salesUser);
                Http::withToken($accessToken)
                    ->delete("https://www.googleapis.com/calendar/v3/calendars/primary/events/{$googleEventId}");
            } catch (\Throwable $e) {
                Log::warning("Google Calendar Delete Exception: " . $e->getMessage());
            }
        }

        DB::table('event_sales')
            ->where('event_id', $event->id)
            ->where('sales_id', $salesUser->id)
            ->delete();

        return [
            'status'   => 'removed',
            'sales_id' => $salesUser->id,
            'event_id' => $event->id,
        ];
    }

    /**
     * Synchronize event for all currently assigned Sales.
     */
    public function syncAllAssignedSales(Event $event): array
    {
        $results = [];
        $salesList = $event->sales()->get();

        foreach ($salesList as $salesUser) {
            $results[] = $this->syncSalesEvent($event, $salesUser);
        }

        return $results;
    }

    /**
     * Refresh OAuth token if expired.
     */
    protected function ensureValidAccessToken(User $user): ?string
    {
        if ($user->google_token_expires_at && Carbon::parse($user->google_token_expires_at)->isPast()) {
            if ($user->google_refresh_token) {
                $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                    'client_id'     => config('services.google.client_id'),
                    'client_secret' => config('services.google.client_secret'),
                    'refresh_token' => $user->google_refresh_token,
                    'grant_type'    => 'refresh_token',
                ]);

                if ($response->successful()) {
                    $newToken = $response->json('access_token');
                    $expiresIn = $response->json('expires_in', 3600);

                    $user->update([
                        'google_access_token'     => $newToken,
                        'google_token_expires_at' => now()->addSeconds($expiresIn),
                    ]);

                    return $newToken;
                }
            }
        }

        return $user->google_access_token;
    }

    /**
     * Helper to attempt quiet compensation deletion of a created Google event if DB saving fails.
     */
    protected function deleteGoogleEventQuietly(string $accessToken, string $googleEventId): void
    {
        try {
            Http::withToken($accessToken)
                ->delete("https://www.googleapis.com/calendar/v3/calendars/primary/events/{$googleEventId}");
        } catch (\Throwable $e) {
            Log::warning("Failed to delete compensation Google event {$googleEventId}: " . $e->getMessage());
        }
    }

    /**
     * Generate Google OAuth redirect URL.
     */
    public function getAuthUrl(User $user): string
    {
        $clientId = config('services.google.client_id');
        $redirectUri = urlencode(config('services.google.redirect'));
        $scopes = urlencode('https://www.googleapis.com/auth/calendar.events');

        return "https://accounts.google.com/o/oauth2/v2/auth?response_type=code&client_id={$clientId}&redirect_uri={$redirectUri}&scope={$scopes}&access_type=offline&prompt=consent&state={$user->id}";
    }
}

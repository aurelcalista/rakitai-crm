<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Prospek;
use App\Policies\ProspekPolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register Policies
        Gate::policy(Prospek::class, ProspekPolicy::class);

        \Illuminate\Support\Facades\View::composer('*', function ($view) {
            if (auth()->check()) {
                $user = auth()->user();
                $role = strtolower($user->role);

                // Scope prospek count by role
                if ($role === 'sales') {
                    $prospekCount = \App\Models\Prospek::where('sales_id', $user->id)->count();
                } elseif ($role === 'cs') {
                    $prospekCount = \App\Models\Prospek::where('cs_id', $user->id)->count();
                } else {
                    $prospekCount = \App\Models\Prospek::count(); // HM/SPV/Admin: all
                }

                // Follow up hari ini dan belum selesai
                $followUpQuery = \App\Models\FollowUp::whereDate('next_follow_up', now()->toDateString())
                    ->whereHas('prospek', function ($q) {
                        $q->whereNotIn('status', ['Closing', 'Lost']);
                    });

                // Scope follow-up by role
                if ($role === 'sales') {
                    $followUpQuery->where('user_id', $user->id);
                } elseif ($role === 'cs') {
                    $followUpQuery->where('user_id', $user->id);
                }

                $followUpHariIni = $followUpQuery->count();

                $unreadNotifications = $user->unreadNotifications;
                $rawNotifications = $user->notifications()->limit(20)->get();
                $notifications = $rawNotifications->map(function ($notif) {
                    return [
                        'id'      => $notif->id,
                        'title'   => $notif->data['title'] ?? 'Notifikasi Baru',
                        'message' => $notif->data['message'] ?? '',
                        'time'    => $notif->created_at->diffForHumans(),
                        'type'    => $notif->data['type'] ?? 'info',
                        'read'    => $notif->read_at !== null,
                        'link'    => $notif->data['link'] ?? '#',
                    ];
                });

                $view->with([
                    'globalProspekCount'          => $prospekCount,
                    'globalFollowUpTodayCount'    => $followUpHariIni,
                    'globalUnreadNotifications'   => $unreadNotifications,
                    'globalNotifications'         => $notifications,
                ]);
            }
        });
    }
}

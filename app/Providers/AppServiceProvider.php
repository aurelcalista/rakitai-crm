<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        \Illuminate\Support\Facades\View::composer('*', function ($view) {
            if (auth()->check()) {
                $user = auth()->user();
                $prospekCount = \App\Models\Prospek::count(); // HM scope: all
                
                // Follow up hari ini dan belum selesai
                $followUpHariIni = \App\Models\FollowUp::whereDate('next_follow_up', now()->toDateString())
                    ->whereHas('prospek', function ($q) {
                        $q->whereNotIn('status', ['Closing', 'Lost']);
                    })->count();
                
                $unreadNotifications = $user->unreadNotifications;

                $view->with([
                    'globalProspekCount' => $prospekCount,
                    'globalFollowUpTodayCount' => $followUpHariIni,
                    'globalUnreadNotifications' => $unreadNotifications
                ]);
            }
        });
    }
}

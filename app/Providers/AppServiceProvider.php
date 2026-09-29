<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Prospek;
use App\Models\Kunjungan;
use App\Models\Event;
use App\Models\Target;
use App\Models\Attendance;
use App\Models\AttendanceLocation;
use App\Models\Invoice;
use App\Models\Payment;
use App\Policies\ProspekPolicy;
use App\Policies\KunjunganPolicy;
use App\Policies\EventPolicy;
use App\Policies\TargetPolicy;
use App\Policies\AttendancePolicy;
use App\Policies\AttendanceLocationPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\PaymentPolicy;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\SimulationPaymentGateway;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGatewayInterface::class, SimulationPaymentGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Pagination\Paginator::useTailwind();

        // Register Policies
        Gate::policy(Prospek::class, ProspekPolicy::class);
        Gate::policy(Kunjungan::class, KunjunganPolicy::class);
        Gate::policy(Event::class, EventPolicy::class);
        Gate::policy(Target::class, TargetPolicy::class);
        Gate::policy(Attendance::class, AttendancePolicy::class);
        Gate::policy(AttendanceLocation::class, AttendanceLocationPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);

        \Illuminate\Support\Facades\View::composer('*', function ($view) {
            if (auth()->check()) {
                $user = auth()->user();
                $role = strtolower($user->role);

                $prospekCount = 0;
                $followUpHariIni = 0;

                if ($role !== 'eo') {
                    // Scope prospek count by role
                    if ($role === 'sales') {
                        $prospekCount = \App\Models\Prospek::where('sales_id', $user->id)->count();
                    } elseif ($role === 'cs') {
                        $prospekCount = \App\Models\Prospek::where('cs_id', $user->id)->count();
                    } elseif ($role === 'spv') {
                        $teamIds = $user->teamMemberIds();
                        $prospekCount = \App\Models\Prospek::where(function($q) use ($teamIds) {
                            $q->whereIn('sales_id', $teamIds)
                              ->orWhereIn('owner_id', $teamIds)
                              ->orWhereIn('cs_id', $teamIds);
                        })->count();
                    } elseif ($role === 'hm' && $user->wilayah_id) {
                        $hmIds = $user->hmMemberIds();
                        $prospekCount = \App\Models\Prospek::where(function($q) use ($user, $hmIds) {
                            $q->where('wilayah_id', $user->wilayah_id)
                              ->orWhereIn('sales_id', $hmIds)
                              ->orWhereIn('owner_id', $hmIds);
                        })->count();
                    } else {
                        $prospekCount = \App\Models\Prospek::count(); // Admin: all
                    }

                    // Follow up hari ini dan belum selesai
                    $followUpQuery = \App\Models\FollowUp::whereDate('next_follow_up', now()->toDateString())
                        ->whereHas('prospek', function ($q) {
                            $q->whereNotIn('status', ['LUNAS', 'NO RESPON', 'DINGIN']);
                        });

                    // Scope follow-up by role
                    if ($role === 'sales') {
                        $followUpQuery->where('user_id', $user->id);
                    } elseif ($role === 'cs') {
                        $followUpQuery->whereHas('prospek', function($q) use ($user) {
                            $q->where('cs_id', $user->id);
                        });
                    } elseif ($role === 'spv') {
                        $teamIds = $user->teamMemberIds();
                        $followUpQuery->where(function($q) use ($teamIds) {
                            $q->whereIn('user_id', $teamIds)
                              ->orWhereHas('prospek', fn($pq) => $pq->whereIn('sales_id', $teamIds));
                        });
                    }

                    $followUpHariIni = $followUpQuery->count();
                }


                $rawNotifications = $user->notifications()
                    ->where(function ($q) {
                        $q->whereNull('data->title')
                          ->orWhere(function ($sub) {
                              $sub->where('data->title', 'not like', '%Profil%')
                                  ->where('data->title', 'not like', '%Profile%');
                          });
                    })
                    ->limit(20)
                    ->get();

                $unreadNotifications = $user->unreadNotifications
                    ->filter(function ($n) {
                        $title = $n->data['title'] ?? '';
                        return !str_contains(strtolower($title), 'profil') && !str_contains(strtolower($title), 'profile');
                    });
                $notifications = $rawNotifications->map(function ($notif) {
                    return [
                        'id'      => $notif->id,
                        'title'   => $notif->data['title'] ?? 'Notifikasi Baru',
                        'message' => $notif->data['message'] ?? '',
                        'time'    => $notif->created_at->diffForHumans(),
                        'type'    => $notif->data['type'] ?? 'info',
                        'read'    => $notif->read_at !== null,
                        'link'    => $notif->data['link'] ?? $notif->data['url'] ?? '#',
                        'icon'    => $notif->data['icon'] ?? '🔔',
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

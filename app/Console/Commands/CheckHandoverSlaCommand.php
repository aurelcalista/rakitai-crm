<?php

namespace App\Console\Commands;

use App\Models\Prospek;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use App\Notifications\EventNotification; // using existing generic notification class

class CheckHandoverSlaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crm:check-handover-sla';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check SLA > 2 hours for CS Handover and alert SPV';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get prospeks that are handed over but active follow up is still 0 
        // (meaning CS has not done any follow up yet).
        $breachedProspeks = Prospek::whereNotNull('cs_id')
            ->whereNotNull('handover_at')
            ->where('handover_at', '<=', now()->subHours(2))
            ->where('active_follow_up_count', 0)
            ->whereIn('status', ['FORMULIR', 'BERKAS', 'LUNAS']) // Handover statuses
            ->get();

        $count = 0;
        foreach ($breachedProspeks as $prospek) {
            $cs = User::find($prospek->cs_id);
            if ($cs) {
                // Find SPV of this CS
                // Either by supervisor_id or wilayah's SPV
                $spv = null;
                if ($cs->supervisor_id) {
                    $spv = User::find($cs->supervisor_id);
                } else if ($cs->wilayah_id) {
                    $spv = User::where('role', 'SPV')->where('wilayah_id', $cs->wilayah_id)->first();
                }

                if ($spv) {
                    // We simulate sending notification by using standard database notification or timeline
                    \App\Models\ProspekTimeline::create([
                        'prospek_id' => $prospek->id,
                        'user_id' => $spv->id,
                        'title' => 'SLA Handover Breached',
                        'notes' => 'CS ' . $cs->name . ' belum merespons / welcome message kepada prospek ini lebih dari 2 jam sejak handover.',
                        'status_before' => $prospek->status,
                        'status_after' => $prospek->status,
                        'time' => now(),
                    ]);
                    $count++;
                }
            }
        }

        $this->info("Handover SLA checked. $count alerts sent.");
    }
}

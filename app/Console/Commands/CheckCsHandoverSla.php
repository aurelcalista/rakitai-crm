<?php

namespace App\Console\Commands;

use App\Models\FollowUp;
use App\Models\Prospek;
use App\Models\User;
use App\Notifications\CsHandoverOverdueNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckCsHandoverSla extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crm:check-cs-handover-sla {--hours=2 : Batas toleransi jam SLA}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Periksa prospek berstatus FORMULIR yang melewati batas SLA 2 jam tanpa respons CS dan kirim alarm ke SPV';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hours = (int)$this->option('hours');
        $threshold = Carbon::now()->subHours($hours);

        $this->info("Memeriksa prospek FORMULIR dengan handover_at <= {$threshold->toDateTimeString()}...");

        // Cari prospek dengan status FORMULIR yang handover_at-nya sudah lewat dari batas waktu
        $overdueProspects = Prospek::with(['sales.supervisor', 'cs', 'wilayah'])
            ->where(function ($q) {
                $q->where('status', 'FORMULIR')
                  ->orWhere('status', '05 FORMULIR');
            })
            ->whereNotNull('handover_at')
            ->where('handover_at', '<=', $threshold)
            ->get();

        $notifiedCount = 0;

        foreach ($overdueProspects as $prospek) {
            // Cek apakah CS sudah melakukan follow up sejak handover_at
            $hasCsFollowUp = FollowUp::where('prospek_id', $prospek->id)
                ->where('created_at', '>=', $prospek->handover_at)
                ->whereHas('user', function ($q) {
                    $q->where('role', 'CS');
                })
                ->exists();

            if ($hasCsFollowUp) {
                continue; // CS sudah merespons, aman
            }

            // Tentukan SPV yang bertanggung jawab atas lead ini
            $spv = $prospek->sales?->supervisor;
            if (!$spv && $prospek->wilayah_id) {
                $spv = User::where('role', 'SPV')->where('wilayah_id', $prospek->wilayah_id)->first();
            }
            if (!$spv) {
                // Fallback ke SPV mana saja jika belum ada mapping spesifik
                $spv = User::where('role', 'SPV')->first();
            }

            if (!$spv) {
                $this->warn("Tidak ditemukan SPV untuk prospek #{$prospek->id} ({$prospek->name}).");
                continue;
            }

            // Cek pencegahan duplikasi: jangan kirim lagi jika sudah dikirim dalam 6 jam terakhir
            $alreadyNotified = $spv->notifications()
                ->where('type', CsHandoverOverdueNotification::class)
                ->where('created_at', '>=', Carbon::now()->subHours(6))
                ->where('data->prospek_id', $prospek->id)
                ->exists();

            if (!$alreadyNotified) {
                $hoursElapsed = (int) Carbon::parse($prospek->handover_at)->diffInHours(Carbon::now());
                $spv->notify(new CsHandoverOverdueNotification($prospek, max($hours, $hoursElapsed)));
                $notifiedCount++;
                $this->info("Alarm dikirim ke SPV {$spv->name} untuk prospek #{$prospek->id} ({$prospek->name}).");
            }
        }

        $this->info("Pemeriksaan selesai. Total alarm dikirim: {$notifiedCount}.");

        return self::SUCCESS;
    }
}

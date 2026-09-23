<?php

namespace App\Console\Commands;

use App\Models\Prospek;
use App\Models\Target;
use App\Models\User;
use App\Notifications\DailyTargetDeficitNotification;
use App\Services\SpvPerformanceService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckDailyTargetSpv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crm:check-daily-target-spv {--force : Paksa kirim notifikasi meski sudah pernah dikirim hari ini}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Evaluasi target harian Sales di akhir jam kerja dan kirim notifikasi defisit ke Supervisor (SPV)';

    /**
     * Execute the console command.
     */
    public function handle(SpvPerformanceService $spvService): int
    {
        $today = Carbon::today();
        $todayFormatted = $today->translatedFormat('d F Y');
        $force = (bool)$this->option('force');

        $this->info("Memulai evaluasi target harian Sales untuk tanggal {$todayFormatted}...");

        $spvs = User::where('role', 'SPV')->where('status', 'Aktif')->get();
        $totalSpvNotified = 0;

        foreach ($spvs as $spv) {
            // Hindari duplikasi jika sudah dikirim hari ini (kecuali dipaksa dengan --force)
            if (!$force) {
                $alreadySentToday = $spv->notifications()
                    ->where('type', DailyTargetDeficitNotification::class)
                    ->whereDate('created_at', $today)
                    ->exists();

                if ($alreadySentToday) {
                    $this->line("SPV {$spv->name} sudah menerima evaluasi hari ini. Lewati.");
                    continue;
                }
            }

            // Ambil seluruh Sales di bawah SPV ini
            $teamSales = User::whereIn('id', $spv->teamMemberIds())
                ->where('role', 'Sales')
                ->where('status', 'Aktif')
                ->get();

            if ($teamSales->isEmpty()) {
                continue;
            }

            $deficits = [];

            foreach ($teamSales as $sales) {
                // Cari target aktif
                $target = Target::where('sales_id', $sales->id)
                    ->where('status', 'Aktif')
                    ->latest()
                    ->first();

                // Target base kontak harian (default 25 jika belum diatur spesifik)
                $targetKontakHarian = 25;
                if ($target && $target->target_kontak > 0) {
                    if ($target->tipe_periode === 'Harian') {
                        $targetKontakHarian = (int)$target->target_kontak;
                    } elseif ($target->tipe_periode === 'Mingguan') {
                        $targetKontakHarian = (int)ceil($target->target_kontak / 5);
                    } else { // Bulanan
                        $targetKontakHarian = (int)ceil($target->target_kontak / 22);
                    }
                }

                // Realisasi kontak hari ini oleh Sales
                $realisasiHariIni = Prospek::where('sales_id', $sales->id)
                    ->whereDate('created_at', $today)
                    ->count();

                if ($realisasiHariIni < $targetKontakHarian) {
                    $kekurangan = $targetKontakHarian - $realisasiHariIni;
                    $deficits[] = [
                        'sales_id'   => $sales->id,
                        'sales_name' => $sales->name,
                        'target'     => $targetKontakHarian,
                        'realisasi'  => $realisasiHariIni,
                        'defisit'    => $kekurangan,
                    ];
                }
            }

            if (!empty($deficits)) {
                $spv->notify(new DailyTargetDeficitNotification($deficits, $todayFormatted));
                $totalSpvNotified++;
                $this->info("Notifikasi evaluasi defisit dikirim ke SPV: {$spv->name} (" . count($deficits) . " Sales defisit).");
            } else {
                $this->line("SPV {$spv->name}: Seluruh anggota tim Sales mencapai target kontak hari ini!");
            }
        }

        $this->info("Evaluasi target harian selesai. Total SPV dinotifikasi: {$totalSpvNotified}.");

        return self::SUCCESS;
    }
}

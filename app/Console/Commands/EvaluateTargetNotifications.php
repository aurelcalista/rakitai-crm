<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\TargetAchievementService;
use Illuminate\Console\Command;

class EvaluateTargetNotifications extends Command
{
    protected $signature = 'crm:evaluate-targets {--user= : ID user tertentu}';
    protected $description = 'Evaluasi target Sales, CS, dan SPV untuk notifikasi target tuntas (100%) dan peringatan target belum tuntas';

    public function handle(TargetAchievementService $service): int
    {
        $userId = $this->option('user');
        $user = $userId ? User::find($userId) : null;

        $this->info("Menjalankan evaluasi target dan notifikasi...");
        $result = $service->checkAndNotifyTargetStatus($user);

        $this->info("Selesai. Notifikasi terkirim: {$result['tuntas']} Target Tuntas, {$result['belum_tuntas']} Peringatan Belum Tuntas.");
        return Command::SUCCESS;
    }
}

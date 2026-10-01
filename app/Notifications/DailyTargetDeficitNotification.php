<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DailyTargetDeficitNotification extends Notification
{
    use Queueable;

    public function __construct(
        public array $deficits, // array of ['sales_name', 'target', 'realisasi', 'defisit']
        public string $tanggal
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $count = count($this->deficits);
        $names = collect($this->deficits)->pluck('sales_name')->take(3)->implode(', ');
        if ($count > 3) {
            $names .= ' +' . ($count - 3) . ' lainnya';
        }

        return [
            'title'       => 'Evaluasi Target Harian Tim (Akhir Jam Kerja)',
            'message'     => "Sebanyak {$count} personil Sales ({$names}) belum memenuhi target harian kontak pada {$this->tanggal}. Defisit dapat dikunci untuk penugasan besok.",
            'type'        => 'danger',
            'icon'        => '',
            'link'        => route('spv.performa.index'),
            'deficits'    => $this->deficits,
            'tanggal'     => $this->tanggal,
        ];
    }
}

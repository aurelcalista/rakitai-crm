<?php

namespace App\Notifications;

use App\Models\Prospek;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CsHandoverOverdueNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Prospek $prospek,
        public int $hoursOverdue = 2
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $salesName = $this->prospek->sales ? $this->prospek->sales->name : 'Sales';
        $csName = $this->prospek->cs ? $this->prospek->cs->name : 'Belum ditentukan';

        return [
            'title'       => '⚠️ Alarm SLA: Serah Terima CS > 2 Jam',
            'message'     => "Prospek '{$this->prospek->name}' (dari {$salesName}) telah mencapai status FORMULIR > {$this->hoursOverdue} jam lalu, namun belum direspons oleh CS ({$csName}).",
            'type'        => 'warning',
            'icon'        => '⏰',
            'link'        => route('spv.prospek.show', $this->prospek->id),
            'prospek_id'  => $this->prospek->id,
            'handover_at' => $this->prospek->handover_at?->toIso8601String(),
        ];
    }
}

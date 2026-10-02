<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EventNotification extends Notification
{
    use Queueable;

    public $event;
    public $action;

    /**
     * Create a new notification instance.
     */
    public function __construct(Event $event, string $action)
    {
        $this->event = $event;
        $this->action = $action;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $message = '';
        $icon = '';

        switch ($this->action) {
            case 'assigned_spv':
                $message = "Anda ditugaskan ke event '{$this->event->name}' oleh EO.";
                break;
            case 'assigned_sales':
                $message = "Anda ditugaskan ke event '{$this->event->name}' sebagai Sales.";
                break;
            case 'assignment_removed':
                $message = "Penugasan Anda pada event '{$this->event->name}' telah dicabut.";
                break;
            case 'updated':
                $message = "Detail event '{$this->event->name}' mengalami perubahan.";
                break;
            case 'cancelled':
                $message = "Event '{$this->event->name}' telah dibatalkan.";
                break;
        }

        return [
            'title' => 'Notifikasi Event',
            'message' => $message,
            'icon' => $icon,
            'url' => '/calendar',
            'event_id' => $this->event->id
        ];
    }
}

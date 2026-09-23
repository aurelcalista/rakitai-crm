<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CrmActivityNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public string $type = 'info',       // 'info' | 'success' | 'warning' | 'danger'
        public string $link = '#',
        public string $icon = '🔔',
        public ?string $senderName = null,
        public ?string $senderRole = null,
        public ?string $action = null,
        public array $extraData = []
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return array_merge([
            'title'       => $this->title,
            'message'     => $this->message,
            'type'        => $this->type,
            'link'        => $this->link,
            'icon'        => $this->icon,
            'sender_name' => $this->senderName,
            'sender_role' => $this->senderRole,
            'action'      => $this->action,
        ], $this->extraData);
    }
}

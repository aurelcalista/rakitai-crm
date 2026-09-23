<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TargetNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public string $type = 'info',
        public string $link = '#',
        public string $icon = '🎯',
        public array $extraData = []
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return array_merge([
            'title'   => $this->title,
            'message' => $this->message,
            'type'    => $this->type,
            'link'    => $this->link,
            'icon'    => $this->icon,
        ], $this->extraData);
    }
}

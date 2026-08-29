<?php

namespace App\Notifications;

use App\Models\Evaluation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EvaluationAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Evaluation $evaluation) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'evaluation_assigned',
            'message' => 'Tiene una nueva evaluación pendiente.',
            'evaluation_id' => $this->evaluation->id,
            'due_at' => $this->evaluation->due_at?->toISOString(),
        ];
    }
}

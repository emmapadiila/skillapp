<?php

namespace App\Notifications;

use App\Models\ImprovementPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReevaluationDueNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly ImprovementPlan $plan) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'reevaluation_due',
            'message' => 'Es momento de programar su reevaluación.',
            'improvement_plan_id' => $this->plan->id,
            'reevaluation_date' => $this->plan->reevaluation_date->toDateString(),
        ];
    }
}

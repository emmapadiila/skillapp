<?php

namespace App\Notifications;

use App\Models\ImprovementPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ImprovementPlanAssignedNotification extends Notification
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
            'type' => 'improvement_plan_assigned',
            'message' => 'Tiene un nuevo plan de mejoramiento.',
            'improvement_plan_id' => $this->plan->id,
            'reevaluation_date' => $this->plan->reevaluation_date?->toDateString(),
        ];
    }
}

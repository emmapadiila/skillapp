<?php

use App\Enums\EvaluationStatus;
use App\Enums\ImprovementPlanStatus;
use App\Models\Evaluation;
use App\Models\ImprovementPlan;
use App\Notifications\ReevaluationDueNotification;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function (): void {
    Evaluation::query()
        ->whereIn('status', [EvaluationStatus::Pending, EvaluationStatus::InProgress])
        ->where('due_at', '<', now())
        ->update(['status' => EvaluationStatus::Expired, 'updated_at' => now()]);
})->name('expire-evaluations')->hourly()->withoutOverlapping();

Schedule::call(function (): void {
    ImprovementPlan::query()
        ->with('user')
        ->whereDate('reevaluation_date', today())
        ->whereNotIn('status', [ImprovementPlanStatus::Cancelled])
        ->eachById(function (ImprovementPlan $plan): void {
            $plan->user->notify(new ReevaluationDueNotification($plan));
        });
})->name('send-reevaluation-reminders')
    ->timezone(config()->string('softskills.schedule.timezone'))
    ->dailyAt(config()->string('softskills.schedule.reevaluation_notification_time'))
    ->withoutOverlapping();

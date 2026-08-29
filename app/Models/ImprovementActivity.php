<?php

namespace App\Models;

use App\Enums\ImprovementActivityStatus;
use Database\Factories\ImprovementActivityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImprovementActivity extends Model
{
    /** @use HasFactory<ImprovementActivityFactory> */
    use HasFactory;

    protected $fillable = [
        'improvement_plan_id', 'title', 'description', 'duration_minutes',
        'status', 'display_order', 'completed_at',
    ];

    protected function casts(): array
    {
        return ['status' => ImprovementActivityStatus::class, 'completed_at' => 'datetime'];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(ImprovementPlan::class, 'improvement_plan_id');
    }
}

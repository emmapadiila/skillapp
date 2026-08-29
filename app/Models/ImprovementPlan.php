<?php

namespace App\Models;

use App\Enums\ImprovementPlanStatus;
use Database\Factories\ImprovementPlanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImprovementPlan extends Model
{
    /** @use HasFactory<ImprovementPlanFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id', 'user_id', 'skill_id', 'created_by', 'initial_level',
        'target_level', 'progress', 'status', 'start_date', 'due_date',
        'reevaluation_date', 'ai_recommendation',
    ];

    protected function casts(): array
    {
        return [
            'progress' => 'decimal:2', 'status' => ImprovementPlanStatus::class,
            'start_date' => 'date', 'due_date' => 'date', 'reevaluation_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ImprovementActivity::class)->orderBy('display_order');
    }

    public function resources(): HasMany
    {
        return $this->hasMany(RecommendedResource::class);
    }
}

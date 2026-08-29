<?php

namespace App\Models;

use Database\Factories\EvaluationResultFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvaluationResult extends Model
{
    /** @use HasFactory<EvaluationResultFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Los resultados completados son inmutables.'));
        static::deleting(fn () => throw new \LogicException('Los resultados completados son inmutables.'));
    }

    protected $fillable = [
        'evaluation_id', 'total_score', 'level', 'ai_analysis', 'strengths',
        'opportunities', 'model_confidence', 'calculated_at', 'immutable_at',
    ];

    protected function casts(): array
    {
        return [
            'total_score' => 'decimal:2', 'strengths' => 'array',
            'opportunities' => 'array', 'model_confidence' => 'decimal:4',
            'calculated_at' => 'datetime', 'immutable_at' => 'datetime',
        ];
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function skillResults(): HasMany
    {
        return $this->hasMany(SkillResult::class);
    }

    public function categoryResults(): HasMany
    {
        return $this->hasMany(CategoryResult::class);
    }
}

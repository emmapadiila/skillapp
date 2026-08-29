<?php

namespace App\Models;

use App\Enums\EvaluationStatus;
use App\Enums\EvaluationType;
use Database\Factories\EvaluationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Evaluation extends Model
{
    /** @use HasFactory<EvaluationFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id', 'user_id', 'assigned_by', 'parent_evaluation_id', 'type',
        'status', 'question_count', 'assigned_at', 'starts_at', 'due_at',
        'completed_at', 'settings',
    ];

    protected function casts(): array
    {
        return [
            'type' => EvaluationType::class,
            'status' => EvaluationStatus::class,
            'question_count' => 'integer',
            'assigned_at' => 'datetime',
            'starts_at' => 'datetime',
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
            'settings' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(EvaluationQuestion::class)->orderBy('display_order');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(EvaluationResponse::class);
    }

    public function result(): HasOne
    {
        return $this->hasOne(EvaluationResult::class);
    }
}

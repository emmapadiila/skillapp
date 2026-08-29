<?php

namespace App\Models;

use Database\Factories\EvaluationResponseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationResponse extends Model
{
    /** @use HasFactory<EvaluationResponseFactory> */
    use HasFactory;

    protected $fillable = [
        'evaluation_id', 'evaluation_question_id', 'user_id', 'question_option_id',
        'likert_value', 'score', 'answered_at',
    ];

    protected function casts(): array
    {
        return ['likert_value' => 'integer', 'score' => 'decimal:2', 'answered_at' => 'datetime'];
    }

    public function evaluationQuestion(): BelongsTo
    {
        return $this->belongsTo(EvaluationQuestion::class);
    }
}

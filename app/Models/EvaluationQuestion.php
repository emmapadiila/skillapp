<?php

namespace App\Models;

use Database\Factories\EvaluationQuestionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationQuestion extends Model
{
    /** @use HasFactory<EvaluationQuestionFactory> */
    use HasFactory;

    protected $fillable = ['evaluation_id', 'question_id', 'display_order', 'adaptive_metadata'];

    protected function casts(): array
    {
        return ['display_order' => 'integer', 'adaptive_metadata' => 'array'];
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}

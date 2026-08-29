<?php

namespace App\Models;

use Database\Factories\SkillResultFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkillResult extends Model
{
    /** @use HasFactory<SkillResultFactory> */
    use HasFactory;

    protected $fillable = [
        'evaluation_result_id', 'skill_id', 'score', 'level', 'strengths', 'opportunities',
    ];

    protected function casts(): array
    {
        return ['score' => 'decimal:2', 'strengths' => 'array', 'opportunities' => 'array'];
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }
}

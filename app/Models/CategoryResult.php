<?php

namespace App\Models;

use Database\Factories\CategoryResultFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryResult extends Model
{
    /** @use HasFactory<CategoryResultFactory> */
    use HasFactory;

    protected $fillable = [
        'evaluation_result_id', 'skill_category_id', 'score', 'level', 'strengths', 'opportunities',
    ];

    protected function casts(): array
    {
        return ['score' => 'decimal:2', 'strengths' => 'array', 'opportunities' => 'array'];
    }
}

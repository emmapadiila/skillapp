<?php

namespace App\Models;

use App\Enums\GapPriority;
use Database\Factories\OrganizationalGapFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrganizationalGap extends Model
{
    /** @use HasFactory<OrganizationalGapFactory> */
    use HasFactory;

    protected $fillable = [
        'organizational_diagnostic_id', 'company_id', 'skill_id', 'organizational_axis_id',
        'area_id', 'priority', 'affected_count', 'score_gap', 'ai_recommendation', 'status',
    ];

    protected function casts(): array
    {
        return ['priority' => GapPriority::class, 'affected_count' => 'integer', 'score_gap' => 'decimal:2'];
    }
}

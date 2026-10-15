<?php

namespace App\Models;

use Database\Factories\AssessmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assessment extends Model
{
    /** @use HasFactory<AssessmentFactory> */
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'description', 'organizational_axis_id', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function axis(): BelongsTo
    {
        return $this->belongsTo(OrganizationalAxis::class, 'organizational_axis_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('display_order');
    }
}

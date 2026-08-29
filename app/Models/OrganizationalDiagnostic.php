<?php

namespace App\Models;

use Database\Factories\OrganizationalDiagnosticFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrganizationalDiagnostic extends Model
{
    /** @use HasFactory<OrganizationalDiagnosticFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id', 'generated_by', 'period_start', 'period_end', 'total_evaluated',
        'snapshot', 'ai_recommendations', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date', 'period_end' => 'date', 'total_evaluated' => 'integer',
            'snapshot' => 'array', 'ai_recommendations' => 'array', 'closed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Los diagnósticos cerrados son inmutables.'));
        static::deleting(fn () => throw new \LogicException('Los diagnósticos cerrados son inmutables.'));
    }

    public function matrixCells(): HasMany
    {
        return $this->hasMany(DiagnosticMatrixCell::class);
    }

    public function gaps(): HasMany
    {
        return $this->hasMany(OrganizationalGap::class);
    }
}

<?php

namespace App\Models;

use Database\Factories\DiagnosticMatrixCellFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiagnosticMatrixCell extends Model
{
    /** @use HasFactory<DiagnosticMatrixCellFactory> */
    use HasFactory;

    protected $fillable = [
        'organizational_diagnostic_id', 'organizational_axis_id', 'skill_category_id',
        'average_score', 'level', 'evaluated_count',
    ];

    protected function casts(): array
    {
        return ['average_score' => 'decimal:2', 'evaluated_count' => 'integer'];
    }
}

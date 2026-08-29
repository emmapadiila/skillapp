<?php

namespace App\Models;

use App\Enums\ResourceType;
use Database\Factories\RecommendedResourceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecommendedResource extends Model
{
    /** @use HasFactory<RecommendedResourceFactory> */
    use HasFactory;

    protected $fillable = [
        'improvement_plan_id', 'improvement_activity_id', 'type', 'title',
        'url', 'description', 'recommended_by_ai',
    ];

    protected function casts(): array
    {
        return ['type' => ResourceType::class, 'recommended_by_ai' => 'boolean'];
    }
}

<?php

namespace App\Models;

use Database\Factories\OrganizationalAxisFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrganizationalAxis extends Model
{
    /** @use HasFactory<OrganizationalAxisFactory> */
    use HasFactory;

    protected $fillable = ['code', 'name', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class ReportType extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'name',
        'description',
        'icon',
        'color',
        'severity_level',
        'is_active',
    ];

    public $translatable = ['name', 'description'];

    protected $casts = [
        'is_active' => 'boolean',
        'severity_level' => 'integer',
        'name' => 'array',
        'description' => 'array',
    ];
}

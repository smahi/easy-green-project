<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Wilaya extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'code',
        'name',
        'is_active',
    ];

    public $translatable = ['name'];

    protected $casts = [
        'is_active' => 'boolean',
        'name' => 'array',
    ];

    public function baladyas(): HasMany
    {
        return $this->hasMany(Baladya::class);
    }
}

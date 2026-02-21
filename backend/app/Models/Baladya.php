<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class Baladya extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'wilaya_id',
        'code',
        'name',
        'is_active',
    ];

    public $translatable = ['name'];

    protected $casts = [
        'is_active' => 'boolean',
        'name' => 'array',
    ];

    public function wilaya(): BelongsTo
    {
        return $this->belongsTo(Wilaya::class);
    }
}

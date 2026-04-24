<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class Report extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'user_id',
        'client_uuid',
        'report_type_id',
        'description',
        'latitude',
        'longitude',
        'status',
        'inspector_feedback',
        'is_synchronized',
        'media_attachments',
    ];

    public $translatable = ['description', 'inspector_feedback'];

    protected $casts = [
        'is_synchronized' => 'boolean',
        'media_attachments' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'description' => 'array',
        'inspector_feedback' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reportType(): BelongsTo
    {
        return $this->belongsTo(ReportType::class);
    }
}

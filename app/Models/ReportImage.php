<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_id',
        'image_path',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SynchronousType extends Model
{
    protected $fillable = [
        'title',
        'production_line_id',
        'qty',
        'printer_ip'
    ];
    public function productionLine(): BelongsTo
    {
        return $this->belongsTo(ProductionLine::class);
    }
}

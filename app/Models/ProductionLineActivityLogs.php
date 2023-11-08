<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionLineActivityLogs extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var string[]
     */
    protected $fillable = [
        'production_line_activity_id',
        'action',
        'user_id'
    ];

    /**
     * @return BelongsTo
     */
    public function ProductionLineActivity(): BelongsTo
    {
        return $this->belongsTo(ProductionLineActivity::class);
    }
}

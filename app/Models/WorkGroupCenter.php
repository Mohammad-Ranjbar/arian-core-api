<?php

namespace App\Models;

use App\Services\Erp\SendErpTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkGroupCenter extends Model
{
    use HasFactory, SoftDeletes,SendErpTrait;

    /**
     * @var string[]
     */
    protected $fillable = [
        'title',
        'production_line_id'
    ];

    /**
     * @return BelongsTo
     */
    public function ProductionLine(): BelongsTo
    {
        return $this->belongsTo(ProductionLine::class);
    }

}

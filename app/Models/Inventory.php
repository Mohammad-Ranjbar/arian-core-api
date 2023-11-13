<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inventory extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var string[]
     */
    protected $fillable = [
        'title',
        'make_code_id',
        'code'
    ];

    /**
     * @return BelongsTo
     */
    public function makeCode(): BelongsTo
    {
        return $this->belongsTo(MakeCode::class);
    }
}

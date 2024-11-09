<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pallet extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable = [
        'pallet_type_id',
        'title',
        'owner'
    ];

    /**
     * @return BelongsTo
     */
    public function palletType(): BelongsTo
    {
        return $this->belongsTo(PalletType::class);
    }
}

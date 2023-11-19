<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Car extends Model
{
    use HasFactory, SoftDeletes;


    /**
     * @var string[]
     */
    protected $fillable = [
        'name', 'national_code', 'phone', 'bank_information', 'plate', 'car', 'transit_company_id',

    ];

    /**
     * @return BelongsTo
     */
    public function transitCompany(): BelongsTo
    {
        return $this->belongsTo(TransitCompany::class);
    }

    /**
     * @return MorphMany
     */
    public function fatePermits(): MorphMany
    {
        return $this->morphMany(GatePermit::class, 'model');
    }

}

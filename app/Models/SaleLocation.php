<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SaleLocation extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable = ['title', 'code','make_code_id'];

    /**
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * @return BelongsTo
     */
    public function makeCode(): BelongsTo
    {
        return $this->belongsTo(MakeCode::class);
    }

    /**
     * @return Attribute
     */
    protected function upperLocation(): Attribute
    {
        return Attribute::make(
            get: fn() => [
                'is_upper_location' => false,
                'upper_id'          => 0,
                'upper_class'       => null
            ],
        );
    }
}

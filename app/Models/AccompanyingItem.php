<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AccompanyingItem extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable = ['title'];


    /**
     * @return BelongsToMany
     */
    public function makeCodes(): BelongsToMany
    {
        return $this->belongsToMany(MakeCode::class);
    }
}

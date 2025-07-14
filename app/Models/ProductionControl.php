<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductionControl extends Model
{
    use HasFactory;

    protected $with = ['makeCodes'];

    protected $fillable = ['title'];

    public function makeCodes(): BelongsToMany
    {
        return $this->belongsToMany(MakeCode::class);
    }

}

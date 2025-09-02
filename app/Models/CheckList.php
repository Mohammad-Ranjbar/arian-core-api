<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CheckList extends Model
{
    use HasFactory;

    protected $fillable = ['title','type'];

    public function elements(): BelongsToMany
    {
        return $this->belongsToMany(Element::class);
    }

    public function carModels(): BelongsToMany
    {
        return $this->belongsToMany(CarModel::class);
    }
}

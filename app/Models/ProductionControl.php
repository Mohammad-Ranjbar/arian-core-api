<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductionControl extends Model
{
    use HasFactory;

    protected $with = ['carModels','file'];

    protected $fillable = ['title', 'file_id'];

    public function carModels(): BelongsToMany
    {
        return $this->belongsToMany(CarModel::class);
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class, 'file_id', 'uuid',);
    }

}

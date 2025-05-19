<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ItemInputChecklist extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'file_id'];

    protected $with = ['carModels', 'file'];

    public function carModels(): BelongsToMany
    {
        return $this->belongsToMany(CarModel::class);
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class, 'file_id', 'uuid',);
    }
}

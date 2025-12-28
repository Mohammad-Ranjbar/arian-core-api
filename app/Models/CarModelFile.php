<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarModelFile extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'file_id'];

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class, 'file_id', 'uuid',);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlternativeVariant extends Model
{
    protected $fillable = ['alternative_id', 'variant_code'];

    public function alternative(): BelongsTo
    {
        return $this->belongsTo(Alternative::class);
    }
}

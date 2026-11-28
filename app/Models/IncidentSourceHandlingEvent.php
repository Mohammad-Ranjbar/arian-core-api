<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentSourceHandlingEvent extends Model
{
    protected $fillable = ['type', 'user_id', 'data'];

    protected $casts = ['data' => 'array'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

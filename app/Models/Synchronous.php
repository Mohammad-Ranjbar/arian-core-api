<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Synchronous extends Model
{

    protected $table = 'synchronous';

    protected $fillable = [
        'synchronous_type_id',
        'counter',
        'capacity'
    ];

    public function synchronousType(): BelongsTo
    {
        return $this->belongsTo(SynchronousType::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GatePermit extends Model
{

    use HasFactory, SoftDeletes;

    /**
     * @var string[]
     */
    protected $fillable = [
        'model_type', 'model_id', 'type', 'status', 'owner_id', 'owner_type','exit_type'
    ];

    /**
     * @return MorphTo
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

}

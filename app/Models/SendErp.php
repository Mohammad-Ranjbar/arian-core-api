<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SendErp extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable = ['on_enter', 'on_exit', 'model_type', 'model_id'];

    /**
     * @return MorphTo
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

}

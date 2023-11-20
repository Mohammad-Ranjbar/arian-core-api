<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;


class Basket extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'user_id',
        'status'
    ];

    public static function booted(): void
    {
        static::addGlobalScope('new', function (Builder $builder) {
            $builder->where('status', 'new');
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function submit():void
    {
        $this->update(['status'=> 'submitted']);
    }
}

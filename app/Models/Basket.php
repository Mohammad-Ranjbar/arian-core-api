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

    /**
     * @var string[]
     */
    protected $fillable = [
        'code',
        'user_id',
        'status',
        'description'
    ];

    /**
     * @return void
     */
    public static function booted(): void
    {
        static::addGlobalScope('new', function (Builder $builder) {
            $builder->where('status', 'new');
        });
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return void
     */
    public function submit():void
    {
        $this->update(['status'=> 'submitted']);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;


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

    protected $casts = ['description' => 'json'];

    /**
     * @return void
     */
    public static function booted(): void
    {
        static::addGlobalScope('new', function (Builder $builder) {
            $builder->where('status', 'new');
            // TODO check it
            $builder->where('user_id', Auth::id());
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

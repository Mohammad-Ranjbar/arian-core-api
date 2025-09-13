<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class GatePermit extends Model
{

    use HasFactory, SoftDeletes;

    /**
     * @var string[]
     */
    protected $fillable = [
        'model_type', 'model_id', 'type', 'location_id', 'owner_id', 'owner_type', 'exit_type', 'item_type', 'source_id'
    ];

    public function BillOfLadingItem(): HasOne
    {
        return $this->hasOne(BillOfLadingItem::class, 'model_id', 'model_id')->where('type','bodies');
    }


    /**
     * @return MorphTo
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'source_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForAuthenticatedUser($query)
    {
        return $query->whereHas('source.users', function ($users) {
            $users->where('users.id', Auth::id());
        })
            ->whereHas('location.users', function ($users) {
                $users->where('users.id', Auth::id());
            });
    }
}

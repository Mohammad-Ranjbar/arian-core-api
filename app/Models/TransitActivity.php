<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class TransitActivity extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var string[]
     */
    protected $fillable = [
        'user_id', 'transit_source_id', 'transit_destination_id', 'bill_of_lading', 'request_no', 'status', 'priority',
        'container', 'cottage', 'weight', 'batch', 'description', 'exit_by', 'transit_driver_id',
    ];

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo
     */
    public function transitSource(): BelongsTo
    {
        return $this->belongsTo(TransitSource::class);
    }

    /**
     * @return BelongsTo
     */
    public function transitDestination(): BelongsTo
    {
        return $this->belongsTo(TransitDestination::class);
    }

    /**
     * @return BelongsTo
     */
    public function transitDriver(): BelongsTo
    {
        return $this->belongsTo(TransitDriver::class);
    }

    /**
     * @param $status
     * @return void
     */
    public function setStatus($status): void
    {
        $this->update(['status' => $status]);
        $this->createStatusLog($status);
    }

    /**
     * @param  string  $status
     * @return void
     */
    public function createStatusLog(string $status): void
    {
        $this->statuses()->create([
            'user_id' => Auth::id(),
            'status'  => $status
        ]);
    }

    /**
     * @return HasMany
     */
    public function statuses(): HasMany
    {
        return $this->hasMany(TransitStatusLog::class);
    }

    /**
     * @param  string  $string
     * @return bool
     */
    public function hasStatus(string $string): bool
    {
        return $this->statuses()->where('status', 'enter')->exists();
    }

    /**
     * @return HasOne
     */
    public function child(): HasOne
    {
        return $this->hasOne(TransitActivity::class, 'exit_by');
    }

    /**
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(TransitActivity::class, 'exit_by');
    }


}

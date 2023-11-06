<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransitActivity extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'transit_source_id',
        'transit_destination_id',
        'bill_of_lading',
        'request_no',
        'status',
        'priority',
        'container',
        'cottage',
        'weight',
        'batch',
        'description',
        'exit_by',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transitSource(): BelongsTo
    {
        return $this->belongsTo(TransitSource::class);
    }

    public function transitDestination(): BelongsTo
    {
        return $this->belongsTo(TransitDestination::class);
    }


}

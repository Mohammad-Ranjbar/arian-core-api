<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Job extends Model
{
    use HasFactory, HasUuids;

    public mixed $data;

    /**
     * @var string[]
     */
    protected $fillable = ['user_id', 'type', 'data', 'result', 'status'];
    /**
     * @var string[]
     */
    protected $casts = [
        'data'   => 'json',
        'result' => 'json',
    ];
    /**
     * @var string
     */
    protected $primaryKey = 'uuid';

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function start(): void
    {
        $this->update([
            'status' => 'started'
        ]);
    }
    public function finish(): void
    {
        $this->update([
            'status' => 'finished'
        ]);
    }
}

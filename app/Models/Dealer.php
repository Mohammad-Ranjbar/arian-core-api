<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Dealer extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'code', 'make_code_id'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function makeCode(): BelongsTo
    {
        return $this->belongsTo(MakeCode::class);
    }
}

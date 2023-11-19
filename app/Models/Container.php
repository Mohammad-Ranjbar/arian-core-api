<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Container extends Model
{
    use HasFactory;

    protected $fillable = ['code','on_shipping'];

    /**
     * @return Attribute
     */
    protected function title(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->attributes['code'],
        );
    }

}

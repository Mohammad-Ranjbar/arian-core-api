<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;

class Permission extends \Spatie\Permission\Models\Permission
{
    /**
     * @var string[]
     */
    protected $fillable = ['name', 'guard_name'];

    /**
     * @return Attribute
     */
    protected function title(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->attributes['name'] ,
        );
    }
}
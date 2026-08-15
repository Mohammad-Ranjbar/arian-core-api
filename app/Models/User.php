<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes;

    /**
     * @var string[]
     */
    protected $fillable = [
        'name',
        'email',
        'mobile',
        'avatar',
        'password',
        'national_code',
        'is_active',
    ];

    /**
     * @var string[]
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    /**
     * @return BelongsToMany
     */
    public function productionLines(): BelongsToMany
    {
        return $this->belongsToMany(ProductionLine::class);
    }


    /**
     * @return BelongsToMany
     */
    public function makeCodes(): BelongsToMany
    {
        return $this->belongsToMany(MakeCode::class);
    }

    /**
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'model_has_roles',
            'model_id',
            'role_id',
        )->withPivotValue(['model_type' => 'App\Models\User']);
    }

    /**
     * @return BelongsToMany
     */
    public function stations(): BelongsToMany
    {
        return $this->belongsToMany(Station::class)->withPivot('data');
    }

    /**
     * @return BelongsToMany
     */
    public function saleLocations(): BelongsToMany
    {
        return $this->belongsToMany(SaleLocation::class);
    }

    /**
     * @return BelongsToMany
     */
    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class);
    }


    /**
     * @return BelongsToMany
     */
    public function saleCompanies(): BelongsToMany
    {
        return $this->belongsToMany(SaleCompany::class);
    }


    public function setting(): BelongsTo
    {
        return $this->belongsTo(Setting::class);
    }

    public function alternativeGroup(): BelongsTo
    {
    return  $this->belongsTo(AlternativeCategory::class);
    }
}

<?php

namespace App\Providers;


use Illuminate\Auth\RequestGuard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{

    /**
     * @return void
     */
    public function register(): void
    {
        //
    }


    /**
     * @return void
     */
    public function boot(): void
    {
        RequestGuard::macro('locations', function () {
            return DB::table('location_user')->where('user_id', Auth::id())->pluck('location_id')->all();
        });

        Builder::macro('whereLike', function ($attributes, string $searchTerm) {
            $this->where(function ($q) use ($attributes, $searchTerm) {
                foreach ($attributes as $attribute) {
                    $q->orWhere($attribute, 'LIKE', "%$searchTerm%");
                }
            });
            return $this;
        });
    }
}

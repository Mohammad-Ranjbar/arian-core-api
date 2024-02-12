<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Builder;

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
        Builder::macro('whereLike', function($attributes, string $searchTerm) {
            $this->where(function($q) use ($attributes,$searchTerm) {
                foreach($attributes as $attribute) {
                    $q->orWhere($attribute, 'LIKE', "%{$searchTerm}%");
                }
            });

            return $this;
        });
    }
}

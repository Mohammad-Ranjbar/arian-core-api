<?php

namespace App\Providers;

use App\Rules\ContainerItemExistsRule;
use Illuminate\Support\Facades\Validator;
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
            Validator::extend('container_item_exists', function ($attribute, $value, $parameters, $validator) {
                return app(ContainerItemExistsRule::class)->passes($attribute, $value,$parameters,$validator);
            });
            return $this;
        });
    }
}

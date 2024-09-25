<?php

namespace App\Providers;


use App\Models\Barcode;
use Illuminate\Auth\RequestGuard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
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
        Validator::extend('CodeUniqueWhenDeletedAtNullRule', function ($attribute, $value, $parameters, $validator) {

            if (in_array($value,['shortage', 'noLabel', 'unreadable'])){
                return true;
            }
            $barcode = Barcode::query()->where($attribute, $value)->first();
            if ($barcode && $barcode->deleted_at == null) {
                return false;
            }
            return true;
        });

        Validator::replacer('CodeUniqueWhenDeletedAtNullRule', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':attribute', ucwords($attribute), 'The '.$attribute.' has been used.');
        });


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

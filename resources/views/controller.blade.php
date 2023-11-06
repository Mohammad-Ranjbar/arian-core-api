{!! '<?php' !!}

namespace App\Http\Controllers;

use App\Http\Resources\{{$name}}Resource;
use App\Repositories\{{$name}}Repository;

class {{$name}}Controller extends CrudController
{
    /**
     * @param  {{$name}}Repository  $repository
     */
    public function __construct({{$name}}Repository $repository)
    {
        $this->repository = $repository;
        $this->applyAuthorizeResource();
        $this->setResource({{$name}}Resource::class);
    }
}

{!! '<?php' !!}

namespace App\Http\Controllers;

use App\Http\Resources\{{$name}}Resource;
use App\Repositories\{{$name}}Repository;

class {{$name}}Controller extends CrudController
{

    public function __construct(public readonly {{$name}}Repository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource({{$name}}Resource::class);
    }
}

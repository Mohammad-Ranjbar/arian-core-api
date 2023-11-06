{!! '<?php' !!}

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\{{$name}};

class {{$name}}Repository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create'    => [

        ], 'update' => [

        ]
    ];

    /**
     * @param  {{$name}}  $model
     */
    public function __construct({{$name}} $model)
    {
        $this->model = $model;
    }

}
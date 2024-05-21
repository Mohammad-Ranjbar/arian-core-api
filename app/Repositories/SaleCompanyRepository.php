<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\SaleCompany;

class SaleCompanyRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [
            'title' => 'required|string|max:255|unique:sale_companies,title',
            'url' => 'required|unique:sale_companies,url',
            'token' => 'nullable'
        ], 'update' => [
            'title' => 'required|string|max:255|unique:sale_companies,title,',
            'url' => 'required|unique:sale_companies,url,',
            'token' => 'nullable'
        ]
    ];

    /**
     * @param SaleCompany $model
     */
    public function __construct(SaleCompany $model)
    {
        $this->model = $model;

        if (request()->route()) {
            $this->rules['update']['title'] .= request()->route()->parameter('sale_company');
            $this->rules['update']['url'] .= request()->route()->parameter('sale_company');
        }
    }

}

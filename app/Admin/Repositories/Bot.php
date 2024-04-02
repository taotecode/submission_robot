<?php

namespace App\Admin\Repositories;

use App\Models\Bot as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class Bot extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;

    public function getSelectOptions(): \Illuminate\Support\Collection
    {
        return $this->model()::query()->pluck('name', 'id');
    }


}

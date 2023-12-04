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
}

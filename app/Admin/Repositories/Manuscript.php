<?php

namespace App\Admin\Repositories;

use App\Models\Manuscript as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class Manuscript extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}

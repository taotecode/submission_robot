<?php

namespace App\Admin\Repositories;

use App\Models\Auditor as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class Auditor extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}

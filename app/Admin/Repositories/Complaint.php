<?php

namespace App\Admin\Repositories;

use App\Models\Complaint as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class Complaint extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}

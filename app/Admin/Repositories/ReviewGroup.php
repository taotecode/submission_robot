<?php

namespace App\Admin\Repositories;

use App\Models\ReviewGroup as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class ReviewGroup extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}

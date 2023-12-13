<?php

namespace App\Admin\Repositories;

use App\Models\SubmissionUser as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class SubmissionUser extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}

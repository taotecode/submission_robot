<?php

namespace App\Admin\Repositories;

use App\Models\Channel as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class Channel extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}

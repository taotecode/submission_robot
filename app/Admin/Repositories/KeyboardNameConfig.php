<?php

namespace App\Admin\Repositories;

use App\Models\KeyboardNameConfig as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class KeyboardNameConfig extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}

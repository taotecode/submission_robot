<?php

namespace App\Admin\Repositories;

use App\Models\BotUser as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class BotUser extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}

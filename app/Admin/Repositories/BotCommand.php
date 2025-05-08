<?php

namespace App\Admin\Repositories;

use App\Models\BotCommand as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class BotCommand extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}

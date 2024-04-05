<?php

namespace App\Admin\Repositories;

use App\Models\BotMessage as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class BotMessage extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}

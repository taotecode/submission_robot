<?php

namespace App\Admin\Repositories;

use App\Models\Bot as Model;
use Dcat\Admin\Repositories\EloquentRepository;
use Illuminate\Support\Facades\Cache;

class Bot extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;

    public function getSelectOptions(): \Illuminate\Support\Collection
    {
        return $this->model()::query()->pluck('name', 'id');
    }

    public function findInfo($id)
    {
        $cacheKey = "bot_with_review_group_{$id}";

        return Cache::remember($cacheKey, now()->addWeek(), function () use ($id) {
            return $this->model()::query()->with('review_group')->find($id);
        });
    }
}

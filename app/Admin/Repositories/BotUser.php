<?php

namespace App\Admin\Repositories;

use App\Models\BotUser as Model;
use Dcat\Admin\Repositories\EloquentRepository;
use Illuminate\Support\Facades\Cache;

class BotUser extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;

    public function findInfo($bot_id,$user_id)
    {
        $cacheKey = "bot_user_{$bot_id}_{$user_id}";

        return Cache::remember($cacheKey, now()->addWeek(), function () use ($bot_id,$user_id) {
            return $this->model()::query()->where(['bot_id' => $bot_id, 'user_id' => $user_id])->first();
        });
    }
}

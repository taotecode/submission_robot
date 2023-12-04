<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class TelegramConfigServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //        $this->loadConfig();
        $this->registerTelegramCommands();
    }

    protected function registerTelegramCommands()
    {

    }

    protected function loadConfig()
    {
        // 获取配置
        $myConfig = Cache::remember('my-config', 86400 * 7, function () {
            return MyConfig::query()->select('foo', 'bar')->first()->toArray();
        });
        // 合并配置
        //$data = array_merge(config('my'),$myConfig);
        // 设置配置
        //config(['my'=>$data]);
        // 设置单个配置
        //app('config')->set('my.key','value');
    }
}

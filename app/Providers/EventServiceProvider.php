<?php

namespace App\Providers;

use App\Models\BotCommand;
use App\Models\BotUser;
use App\Models\Bot;
use App\Observers\BotCommandObserver;
use App\Observers\BotObserver;
use App\Observers\BotUserObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * 应用程序的模型观察者。
     *
     * @var array
     */
    protected $observers = [
        Bot::class => [BotObserver::class],
        BotUser::class => [BotUserObserver::class],
        BotCommand::class=>[BotCommandObserver::class],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents()
    {
        return false;
    }
}

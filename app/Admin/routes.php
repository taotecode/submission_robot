<?php

use Dcat\Admin\Admin;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Admin::routes();

Route::group([
    'prefix' => config('admin.route.prefix'),
    'namespace' => config('admin.route.namespace'),
    'middleware' => config('admin.route.middleware'),
], function (Router $router) {

    $router->get('/', 'HomeController@index');

    $router->post('cache/clear', 'HomeController@clearCache');

    //My Bots
    $router->any('bots/lexiconCheck/{id}', 'MyBotController@lexiconCheck')->name('bots.lexiconCheck');
    $router->resource('bots', 'MyBotController');

    //审核人群
    $router->resource('auditors', 'AuditorController');
    $router->resource('review_groups', 'ReviewGroupController');
    //频道
    $router->resource('channel', 'ChannelController');

    //配置表
    $router->resource('config', 'ConfigController');

    //稿件列表
    $router->resource('manuscript', 'ManuscriptController');

    $router->resource('submission_user', 'SubmissionUserController');

    //机器人用户群
    $router->resource('bot_user', 'BotUserController');
    $router->resource('bot_message', 'BotMessageController');

});

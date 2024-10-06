<?php

namespace App\Http\Controllers\Bots;

use App\Http\Controllers\Controller;
use App\Admin\Repositories\Bot;
use App\Services\CallBackQueryService;
use App\Services\SaveBotUserService;
use App\Services\StartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramOtherException;
use Telegram\Bot\Exceptions\TelegramSDKException;

class HookController extends Controller
{
    use SaveBotUserService;

    public Bot $botRepository;

    public StartService $startService;

    public CallBackQueryService $callBackQueryService;

    public function __construct(
        Bot                  $botRepository,
        StartService         $startService,
        CallBackQueryService $callBackQueryService
    )
    {
        $this->botRepository = $botRepository;
        $this->startService = $startService;
        $this->callBackQueryService = $callBackQueryService;
    }

    /**
     * @throws TelegramSDKException
     */
    public function index($id, Request $request)
    {
        if (config('app.env') === 'local') {
            Log::info('机器人收到请求', $request->all());
        }
        //查询机器人信息
        $botInfo = $this->botRepository->findInfo($id);
        if (!$botInfo) {
            Log::error('机器人数据不存在！', [$id]);

            return false;
        }

        $telegram = new Api($botInfo->token);

        $telegram->addCommands([
            \App\Telegram\Commands\StartCommand::class,
            \App\Telegram\Commands\GetGroupIdCommand::class,
            \App\Telegram\Commands\GetMeIdCommand::class,
            \App\Telegram\Commands\HelpCommand::class,
            \App\Telegram\Commands\BlackCommand::class,
            \App\Telegram\Commands\WhoCommand::class,
            \App\Telegram\Commands\ListCommand::class,
            \App\Telegram\Commands\SearchCommand::class,
        ]);

        // 处理更新数据
        try {
            $updateData = $telegram->commandsHandler(true);
        } catch (TelegramOtherException $e) {
            // 检查错误代码并返回 "ok"
            if ($e->getCode() === 403 && strpos($e->getMessage(), 'bot was blocked by the user') !== false) {
                // 返回 "ok" 给 Telegram
                return 'ok';
            }
            // 处理其他类型的异常（如果需要）
            // 可以选择记录日志或者返回其他信息
        }

        //存入使用机器人的用户
        if ($updateData->objectType() !== 'my_chat_member') {
            $this->save_bot_user($botInfo, $updateData->getChat() ?? null, $updateData->getMessage() ?? null);
        }else{
            return 'ok';
        }


        if ($updateData->hasCommand() && $updateData->objectType() !== 'callback_query') {
            return 'ok';
        }

        //进入投稿服务
        if ($updateData->objectType() === 'callback_query') {//按键相应
            $this->callBackQueryService->index($botInfo, $updateData, $telegram);
        } elseif ($updateData->getChat()->type === 'private') {
            $this->startService->index($botInfo, $updateData, $telegram);
        }

        return 'ok';
    }
}

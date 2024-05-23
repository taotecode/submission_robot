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
use Telegram\Bot\Exceptions\TelegramSDKException;

class HookController extends Controller
{
    use SaveBotUserService;

    public Bot $botRepository;

    public StartService $startService;

    public CallBackQueryService $callBackQueryService;

    public function __construct(
        Bot $botRepository,
        StartService $startService,
        CallBackQueryService $callBackQueryService
    ) {
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
            Log::info('机器人请求', $request->all());
        }
        //查询机器人信息
        $botInfo = $this->botRepository->findInfo($id);
        if (! $botInfo) {
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

        $updateData = $telegram->commandsHandler(true);

        //存入使用机器人的用户
        if ($updateData->objectType() !== 'my_chat_member') {
            $this->save_bot_user($botInfo, $updateData->getChat() ?? null, $updateData->getMessage() ?? null);
        }

        //进入投稿服务
        if (
            $updateData->objectType() === 'message' &&
            ! $updateData->getMessage()->hasCommand() &&
            ! $updateData->getChat()->has('group') &&
            ! $updateData->getChat()->has('supergroup') &&
            ! $updateData->getChat()->has('getChat') &&
            ! in_array($updateData->getChat()->type, ['group', 'supergroup'])
        ) {
            if ($updateData->getChat()->type != 'private') {
                return 'ok';
            }
            $this->startService->index($botInfo, $updateData, $telegram);
        }

        //按键相应
        if ($updateData->objectType() === 'callback_query') {
            $this->callBackQueryService->index($botInfo, $updateData, $telegram);
        }

        return 'ok';
    }
}

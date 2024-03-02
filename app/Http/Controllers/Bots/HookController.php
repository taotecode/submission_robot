<?php

namespace App\Http\Controllers\Bots;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Services\CallBackQueryService;
use App\Services\SubmissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;

class HookController extends Controller
{
    public Bot $botModel;

    public SubmissionService $submissionService;

    public CallBackQueryService $callBackQueryService;

    public function __construct(
        Bot $botModel,
        SubmissionService $submissionService,
        CallBackQueryService $callBackQueryService
    ) {
        $this->botModel = $botModel;
        $this->submissionService = $submissionService;
        $this->callBackQueryService = $callBackQueryService;
    }

    public function index($id, Request $request)
    {
        if (config('app.env') === 'local') {
            Log::info('机器人请求', $request->all());
        }
        //查询机器人信息
        $botInfo = $this->botModel->with('review_group')->find($id);
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
        ]);

        $updateData = $telegram->commandsHandler(true);

        //进入投稿服务
        if (
            $updateData->objectType() === 'message' &&
            ! $updateData->getMessage()->hasCommand() &&
            ! $updateData->getChat()->has('group') &&
            ! $updateData->getChat()->has('supergroup') &&
            ! $updateData->getChat()->has('getChat')
        ) {
            if ($updateData->getChat()->type != 'private') {
                return 'ok';
            }
            $this->submissionService->index($botInfo, $updateData, $telegram);
        }

        //按键相应
        if ($updateData->objectType() === 'callback_query') {
            $this->callBackQueryService->index($botInfo, $updateData, $telegram);
        }

        return 'ok';
    }
}

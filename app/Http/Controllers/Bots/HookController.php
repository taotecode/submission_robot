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
        Log::info('tg info', $request->all());
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

        //记录日志
        $text = $updateData->getMessage()->text ?? '';
        $entities = $updateData->getMessage()->entities ?? '';
        $caption = $updateData->getMessage()->caption ?? '';
        Log::info('message_type:',[
            'objectType'=>$updateData->objectType(),
            'text'=>$text,
            'entities'=>$entities,
            'caption'=>$caption,
        ]);

        $message = $updateData->getMessage();

        if (!empty($text)){
            $entities = $message->entities ?? [];
            foreach ($entities as $entity) {
                $offset = $entity->offset();
                $length = $entity->length();
                $type = $entity->type();
                // 提取出这个实体对应的文本
                $entityText = substr($text, $offset, $length);
                switch ($type) {
                    case 'url':
                        // 将 URL 包装在 Markdown 的链接语法中
                        $text = str_replace($entityText, "[{$entityText}]({$entityText})", $text);
                        break;
                    case 'text_mention':
                        // 处理 @username
                        $text = str_replace($entityText, "[{$entityText}](tg://user?id={$entity->user()->id()})", $text);
                        break;
                    case 'hashtag':
                        // 处理 #tag
                        $text = str_replace($entityText, "\\{$entityText}", $text);
                        break;
                    // 其他你想要处理的类型...
                }
            }
            Log::info('预处理后的文本：',[$text]);
        }

        //进入投稿服务
        if ($updateData->objectType() === 'message' && ! $updateData->getMessage()->hasCommand()) {
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

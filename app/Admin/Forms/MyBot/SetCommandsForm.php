<?php

namespace App\Admin\Forms\MyBot;

use App\Enums\Commands;
use App\Models\Bot;
use App\Models\BotCommand;
use App\Services\BaseService;
use Dcat\Admin\Contracts\LazyRenderable;
use Dcat\Admin\Traits\LazyWidget;
use Dcat\Admin\Widgets\Form;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class SetCommandsForm extends Form implements LazyRenderable
{
    use LazyWidget; // 使用异步加载功能

    /**
     * Handle the form request.
     *
     * @param array $input
     * @return mixed
     */
    public function handle(array $input)
    {
        $botId = $this->payload['id'] ?? null;
        if (!$botId) {
            return $this->response()->error('机器人ID不能为空');
        }

        try {
            // 获取机器人信息
            $bot = Bot::find($botId);
            if (!$bot) {
                return $this->response()->error('机器人不存在');
            }

            $token = $bot->token;
            $telegram = new Api($token);

            // 保存默认命令
            $this->saveCommands($botId, 'default', $input['default'] ?? []);

            // 保存群组命令
            $this->saveCommands($botId, 'all_group_chats', $input['all_group_chats'] ?? []);

            // 发送命令到Telegram
            $this->sendCommandsToTelegram($telegram, 'default', $input['default'] ?? []);
            $this->sendCommandsToTelegram($telegram, 'all_group_chats', $input['all_group_chats'] ?? []);

            return $this->response()->success('命令设置成功')->refresh();
        } catch (\Exception $e) {
            Log::error('设置机器人命令失败：' . $e->getMessage());
            return $this->response()->error('设置命令失败: ' . $e->getMessage());
        }
    }

    /**
     * 保存命令到数据库
     *
     * @param int $botId
     * @param string $scope
     * @param array $commandIndices
     */
    protected function saveCommands($botId, $scope, $commandIndices)
    {
        // 先删除该机器人在此scope下的所有命令
        BotCommand::where('bot_id', $botId)
            ->where('scope', $scope)
            ->delete();

        // 获取命令列表
        $options = $scope === 'default' ? Commands::DEFAULT_OPTIONS : Commands::ALL_GROUP_OPTIONS;
        $commands = [];

        foreach ($commandIndices as $index) {
            if (isset($options[$index])) {
                $commandText = $options[$index];
                // 解析命令名称和描述
                list($command, $description) = $this->parseCommandText($commandText);
                
                // 创建新的命令记录
                BotCommand::create([
                    'bot_id' => $botId,
                    'command' => $command,
                    'description' => $description,
                    'scope' => $scope,
                    'data' => [
                        'command' => $command,
                        'description' => $description,
                    ],
                ]);
            }
        }
    }

    /**
     * 发送命令到Telegram
     *
     * @param Api $telegram
     * @param string $scope
     * @param array $commandIndices
     * @throws TelegramSDKException
     */
    protected function sendCommandsToTelegram($telegram, $scope, $commandIndices)
    {
        $options = $scope === 'default' ? Commands::DEFAULT_OPTIONS : Commands::ALL_GROUP_OPTIONS;
        $commands = [];

        foreach ($commandIndices as $index) {
            if (isset($options[$index])) {
                $commandText = $options[$index];
                list($command, $description) = $this->parseCommandText($commandText);
                
                $commands[] = [
                    'command' => $command,
                    'description' => $description,
                ];
            }
        }

        $telegram->setMyCommands([
            'commands' => json_encode($commands),
            'scope' => json_encode([
                'type' => $scope === 'default' ? 'default' : 'all_group_chats',
            ]),
        ]);
    }

    /**
     * 解析命令文本
     *
     * @param string $commandText
     * @return array
     */
    protected function parseCommandText($commandText)
    {
        $parts = explode(' - ', $commandText);
        $command = trim($parts[0]);
        $description = $parts[1] ?? '';
        
        return [$command, $description];
    }

    /**
     * Build a form here.
     */
    public function form()
    {
        $botId = $this->payload['id'] ?? null;

        // 获取已保存的默认命令
        $defaultCommands = $this->getCommandIndices($botId, 'default', Commands::DEFAULT_OPTIONS);
        
        // 获取已保存的群组命令
        $allGroupCommands = $this->getCommandIndices($botId, 'all_group_chats', Commands::ALL_GROUP_OPTIONS);

        $this->listbox('default', '私聊命令')
            ->options(Commands::DEFAULT_OPTIONS)
            ->required()
            ->default($defaultCommands)
            ->help('将您需要展示给用户的命令点击添加到右侧列表中。不需要的展示的可以点击右侧列表添加到左侧列表中。');
            
        $this->listbox('all_group_chats', '群组命令')
            ->options(Commands::ALL_GROUP_OPTIONS)
            ->required()
            ->default($allGroupCommands)
            ->help('将您需要展示给用户的命令点击添加到右侧列表中。不需要的展示的可以点击右侧列表添加到左侧列表中。');
    }

    /**
     * 获取命令索引
     *
     * @param int $botId
     * @param string $scope
     * @param array $options
     * @return array
     */
    protected function getCommandIndices($botId, $scope, $options)
    {
        if (!$botId) {
            return $scope === 'default' ? [0, 1] : [0];
        }

        // 从数据库获取该机器人已设置的命令
        $commands = BotCommand::where('bot_id', $botId)
            ->where('scope', $scope)
            ->get()
            ->pluck('command')
            ->toArray();

        // 将命令转换为索引
        $indices = [];
        foreach ($options as $index => $option) {
            $parts = explode(' - ', $option);
            $command = trim($parts[0]);
            
            if (in_array($command, $commands)) {
                $indices[] = $index;
            }
        }

        return empty($indices) ? ($scope === 'default' ? [0, 1] : [0]) : $indices;
    }

    /**
     * The data of the form.
     *
     * @return array
     */
    public function default()
    {
        $botId = $this->payload['id'] ?? null;
        
        if (!$botId) {
            return [
                'default' => [0, 1],
                'all_group_chats' => [0],
            ];
        }

        return [
            'default' => $this->getCommandIndices($botId, 'default', Commands::DEFAULT_OPTIONS),
            'all_group_chats' => $this->getCommandIndices($botId, 'all_group_chats', Commands::ALL_GROUP_OPTIONS),
        ];
    }
}

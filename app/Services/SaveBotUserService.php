<?php

namespace App\Services;

use App\Models\BotMessage;
use App\Models\BotUser;
use App\Models\SubmissionUser;
use Telegram\Bot\Objects\Chat;
use Telegram\Bot\Objects\Message;

trait SaveBotUserService
{
    public function save_bot_user($botInfo,Chat|null $user,Message|null $message)
    {
        BotUser::updateOrCreate(
            ['bot_id' => $botInfo->id, 'user_id' => $user->id],
            ['user_data' => $user],
        );

        $bot_message = new BotMessage();
        $bot_message->bot_id = $botInfo->id;
        $bot_message->user_id = $user->id;
        $bot_message->user_data = $user->toArray();
        $bot_message->data = $message->toArray();
        $bot_message->save();

        return true;
    }
}

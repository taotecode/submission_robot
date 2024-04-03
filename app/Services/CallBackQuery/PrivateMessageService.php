<?php

namespace App\Services\CallBackQuery;

use App\Enums\AuditorRole;
use App\Enums\KeyBoardData;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Exceptions\TelegramSDKException;

class PrivateMessageService
{
    use AuditorRoleCheckService;

    public function private_message($telegram,$callbackQuery,$from,$reviewGroup,$manuscript,$chatId): string
    {
        if ($this->baseCheck($telegram, $callbackQuery->id, $from->id, $reviewGroup->id) !== true) {
            return 'ok';
        }

        if ($this->roleCheck($telegram, $callbackQuery->id, $from->id, [
                AuditorRole::PRIVATE_CHAT_SUBMISSION,
            ]) !== true) {
            return 'ok';
        }

        $posted_by = $manuscript->posted_by;

        $inline_keyboard=KeyBoardData::PRIVATE_MESSAGE;

        $inline_keyboard['inline_keyboard'][0][0]['url'] = 'https://t.me/'.$posted_by['username'];
        $inline_keyboard['inline_keyboard'][0][1]['url'] = 'tg://openmessage?user_id='.$posted_by['id'];
        $inline_keyboard['inline_keyboard'][0][2]['url'] = 'https://t.me/@id'.$posted_by['id'];

        $text = '昵称：'.get_posted_by($posted_by)."\r\nUID: <code>{$posted_by['id']}</code>";

        if (isset($posted_by['username'])) {
            $inline_keyboard['inline_keyboard'][0][0]['url'] = 'https://t.me/'.$posted_by['username'];
            $text .= "\r\n用户名：@".$posted_by['username'];
        } else {
            unset($inline_keyboard['inline_keyboard'][0][0]);
        }

        try {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $text,
                'reply_markup' => json_encode($inline_keyboard),
                'parse_mode' => 'HTML',
            ]);

            return 'ok';
        } catch (TelegramSDKException $telegramSDKException) {
            Log::error($telegramSDKException);

            return 'error';
        }
    }
}

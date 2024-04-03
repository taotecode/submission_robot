<?php

namespace App\Services;

use App\Models\BotUser;
use App\Models\SubmissionUser;
use Telegram\Bot\Objects\Chat;

trait SaveBotUserService
{
    public function save_bot_user($botInfo,?Chat $user)
    {
        BotUser::updateOrCreate(
            ['bot_id' => $botInfo->id, 'userId' => $user->id],
            ['user_data' => $user],
        );

        $submissionUser = (new SubmissionUser())->where(['bot_id' => $botInfo->id, 'userId' => $user->id])->first();
        if ($submissionUser){
            $submissionUser->name=get_posted_by($user->toArray());
            $submissionUser->save();
        }

        return true;
    }
}

<?php

namespace App\Services\CallBackQuery;

use App\Models\BotUser;

class SettingsServices
{
    public function index(
        $telegram, $botInfo,$updateData, $command,$commandArray,$chat,$chatId,$messageId,$callbackQuery,$callbackQueryId,$message,$from,$replyToMessage
    )
    {
        $status=$commandArray[2];
        switch ($command) {
            case 'c_c_s_anonymous'://common_command_setting_anonymous；公共-命令-设置-匿名
                return $this->common_command_setting($telegram,$botInfo,'is_anonymous',$status,$chatId,$messageId);
            case 'c_c_s_d_m_p'://common_command_setting_disable_message_preview；公共-命令-设置-消息预览
                return $this->common_command_setting($telegram,$botInfo,'is_link_preview',$status,$chatId,$messageId);
            case 'c_c_s_d_n'://common_command_setting_disable_notification；公共-命令-设置-消息通知
                return $this->common_command_setting($telegram,$botInfo,'is_disable_notification',$status,$chatId,$messageId);
            case 's_p_m_s_f_o'://common_command_setting_forward_origin；公共-命令-设置-转发来源
                return $this->common_command_setting($telegram,$botInfo,'is_protect_content',$status,$chatId,$messageId);
            default:
                return 'error';
        }
    }

    private function common_command_setting($telegram,$botInfo,$type,$status,$chatId,$messageId)
    {
        //更新信息
        $botUser=BotUser::where('bot_id', $botInfo->id)->where('user_id', $chatId)->first();
        $type_text=($botUser->is_anonymous==1)?'匿名':'不匿名';
        $type_text1=($botUser->is_link_preview==1)?'是':'否';
        $type_text2=($botUser->is_disable_notification==1)?'是':'否';
        $type_text3=($botUser->is_protect_content==1)?'主动输入':'从不输入';

        $botUser->$type=$status;
        $botUser->save();

        switch ($type){
            case 'is_anonymous':
                $type_text=($status==1)?'匿名':'不匿名';
                break;
            case 'is_link_preview':
                $type_text1=($status==1)?'是':'否';
                break;
            case 'is_disable_notification':
                $type_text2=($status==1)?'是':'否';
                break;
            case 'is_protect_content':
                $type_text3=($status==1)?'主动输入':'从不输入';
                break;
        }

        return $telegram->editMessageReplyMarkup([
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => '投稿身份【'.$type_text.'】', 'callback_data' => 'c_c_s_anonymous:null:'.$botUser->is_anonymous],
                    ],
                    [
                        ['text' => '消息预览【'.$type_text1.'】', 'callback_data' => 'c_c_s_d_m_p:null:'.$botUser->is_link_preview],
                    ],
                    [
                        ['text' => '消息静默发送【'.$type_text2.'】', 'callback_data' => 'c_c_s_d_n:null:'.$botUser->is_disable_notification],
                    ],
                    [
                        ['text' => '消息来源【'.$type_text3.'】', 'callback_data' => 's_p_m_s_f_o:null:'.$botUser->is_protect_content],
                    ],
                ],
            ]),
        ]);

    }
}

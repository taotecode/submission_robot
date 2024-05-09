<?php

use App\Enums\KeyBoardData;
use App\Enums\KeyBoardName;

function getCacheMessageData($objectType, $chatId, $tag): array
{
    $messageCache = Cache::tags($tag.'.'.$chatId)->get($objectType);
    $messageId = $messageCache['message_id'] ?? '';
    $messageText = $messageCache[$objectType]['text'] ?? $messageCache['caption'] ?? '';

    return [$messageCache, $messageId, $messageText];
}

function service_isOpen_check_return_keyboard($botInfo): array
{
    $keyboard = [];
    $baseKeyboard = [
        'resize_keyboard' => true, // 让键盘大小适应屏幕
        'one_time_keyboard' => false, // 是否只显示一次
    ];

    if ($botInfo['is_submission'] == 0) {
        if ($botInfo['is_complaint'] == 0 && $botInfo['is_suggestion'] == 0) {
            $keyboard = [['start.HelpCenter']];
        } elseif ($botInfo['is_complaint'] == 0) {
            $keyboard = [['feedback.SubmitSuggestion', 'start.HelpCenter']];
        } elseif ($botInfo['is_suggestion'] == 0) {
            $keyboard = [['feedback.SubmitComplaint', 'start.HelpCenter']];
        } else {
            $keyboard = [['start.Feedback', 'start.HelpCenter']];
        }
    } elseif ($botInfo['is_complaint'] == 0) {
        if ($botInfo['is_suggestion'] == 0) {
            $keyboard = [['start.StartSubmission', 'start.HelpCenter']];
        } else {
            $keyboard = [['start.StartSubmission', 'feedback.SubmitSuggestion'], ['start.HelpCenter']];
        }
    } elseif ($botInfo['is_suggestion'] == 0) {
        $keyboard = [['start.StartSubmission', 'feedback.SubmitComplaint'], ['start.HelpCenter']];
    } else {
        return KeyBoardData::$START;
    }

    // 将键盘配置名称转换为具体的键盘名称
    $keyboard = array_map(function ($row) {
        return array_map(function ($key) {
            return get_keyboard_name_config($key, constant('App\Enums\KeyBoardName::'.explode('.', $key)[1]));
        }, $row);
    }, $keyboard);

    return array_merge($baseKeyboard, ['keyboard' => $keyboard]);
}

function service_isOpen_check_return_keyboard_old($botInfo): array
{
    if ($botInfo['is_submission'] == 0 && $botInfo['is_complaint'] == 0 && $botInfo['is_suggestion'] == 0) {
        return [
            'keyboard' => [
                [
                    get_keyboard_name_config('start.HelpCenter', KeyBoardName::HelpCenter),
                ],
            ],
            'resize_keyboard' => true, // 让键盘大小适应屏幕
            'one_time_keyboard' => false, // 是否只显示一次
        ];
    }
    if ($botInfo['is_submission'] == 0 && $botInfo['is_complaint'] == 0) {
        return [
            'keyboard' => [
                [
                    get_keyboard_name_config('feedback.SubmitSuggestion', KeyBoardName::SubmitSuggestion),
                    get_keyboard_name_config('start.HelpCenter', KeyBoardName::HelpCenter),
                ],
            ],
            'resize_keyboard' => true, // 让键盘大小适应屏幕
            'one_time_keyboard' => false, // 是否只显示一次
        ];
    }
    if ($botInfo['is_submission'] == 0 && $botInfo['is_suggestion'] == 0) {
        return [
            'keyboard' => [
                [
                    get_keyboard_name_config('feedback.SubmitComplaint', KeyBoardName::SubmitComplaint),
                    get_keyboard_name_config('start.HelpCenter', KeyBoardName::HelpCenter),
                ],
            ],
            'resize_keyboard' => true, // 让键盘大小适应屏幕
            'one_time_keyboard' => false, // 是否只显示一次
        ];
    }
    if ($botInfo['is_complaint'] == 0 && $botInfo['is_suggestion'] == 0) {
        return [
            'keyboard' => [
                [
                    get_keyboard_name_config('start.StartSubmission', KeyBoardName::StartSubmission),
                    get_keyboard_name_config('start.HelpCenter', KeyBoardName::HelpCenter),
                ],
            ],
            'resize_keyboard' => true, // 让键盘大小适应屏幕
            'one_time_keyboard' => false, // 是否只显示一次
        ];
    }
    if ($botInfo['is_submission'] == 0) {
        return [
            'keyboard' => [
                [
                    get_keyboard_name_config('start.Feedback', KeyBoardName::Feedback),
                    get_keyboard_name_config('start.HelpCenter', KeyBoardName::HelpCenter),
                ],
            ],
            'resize_keyboard' => true, // 让键盘大小适应屏幕
            'one_time_keyboard' => false, // 是否只显示一次
        ];
    }
    if ($botInfo['is_complaint'] == 0) {
        return [
            'keyboard' => [
                [
                    get_keyboard_name_config('start.StartSubmission', KeyBoardName::StartSubmission),
                    get_keyboard_name_config('feedback.SubmitSuggestion', KeyBoardName::SubmitSuggestion),
                ],
                [
                    get_keyboard_name_config('start.HelpCenter', KeyBoardName::HelpCenter),
                ],
            ],
            'resize_keyboard' => true, // 让键盘大小适应屏幕
            'one_time_keyboard' => false, // 是否只显示一次
        ];
    }
    if ($botInfo['is_suggestion'] == 0) {
        return [
            'keyboard' => [
                [
                    get_keyboard_name_config('start.StartSubmission', KeyBoardName::StartSubmission),
                    get_keyboard_name_config('feedback.SubmitComplaint', KeyBoardName::SubmitComplaint),
                ],
                [
                    get_keyboard_name_config('start.HelpCenter', KeyBoardName::HelpCenter),
                ],
            ],
            'resize_keyboard' => true, // 让键盘大小适应屏幕
            'one_time_keyboard' => false, // 是否只显示一次
        ];
    }

    return KeyBoardData::$START;
}

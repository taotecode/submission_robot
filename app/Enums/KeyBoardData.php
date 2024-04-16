<?php

namespace App\Enums;

use http\Env;

class KeyBoardData
{
    /**
     * 用户开启机器人初始键盘
     */
    public static array $START;

    /**
     * 用户开始投稿键盘
     */
    public static array $START_SUBMISSION;

    /**
     * 用户结束投稿键盘
     */
    public static array $END_SUBMISSION;

    /**
     * 用户选择频道投稿键盘
     * @var array
     */
    public static array $SELECT_CHANNEL;

    /**
     * 用户选择频道后结束投稿键盘
     * @var array
     */
    public static array $SELECT_CHANNEL_END;

    /**
     * 用户开始意见反馈键盘
     */
    public static array $START_FEEDBACK;

    /**
     * 用户开始投诉键盘
     * @var array
     */
    public static array $START_COMPLAINT;

    /**
     * @var array 用户结束投诉键盘
     */
    public static array $END_COMPLAINT;

    public static function init(): void
    {
        self::$START = [
            'keyboard' => [
                [
                    get_keyboard_name_config('start.StartSubmission', KeyBoardName::StartSubmission),
                ],
                [
                    get_keyboard_name_config('start.Feedback', KeyBoardName::Feedback),
                    get_keyboard_name_config('start.HelpCenter', KeyBoardName::HelpCenter),
                ]
            ],
            'resize_keyboard' => true, // 让键盘大小适应屏幕
            'one_time_keyboard' => false, // 是否只显示一次
        ];

        self::$START_SUBMISSION=[
            'keyboard' => [
                [
                    get_keyboard_name_config('submission.EndSending', KeyBoardName::EndSending),
                ],
                [
                    get_keyboard_name_config('submission.Restart', KeyBoardName::Restart),
                    get_keyboard_name_config('submission.CancelSubmission', KeyBoardName::CancelSubmission),
                ],
            ],
            'resize_keyboard' => true, // 让键盘大小适应屏幕
            'one_time_keyboard' => false, // 是否只显示一次
        ];

        self::$END_SUBMISSION=[
            'keyboard' => [
                [
                    get_keyboard_name_config('submission_end.ConfirmSubmissionOpen', KeyBoardName::ConfirmSubmissionOpen),
                    get_keyboard_name_config('submission_end.ConfirmSubmissionAnonymous', KeyBoardName::ConfirmSubmissionAnonymous),
                ],
                [
                    get_keyboard_name_config('submission.Restart', KeyBoardName::Restart),
                    get_keyboard_name_config('submission.CancelSubmission', KeyBoardName::CancelSubmission),
                ],
            ],
            'resize_keyboard' => true, // 让键盘大小适应屏幕
            'one_time_keyboard' => false, // 是否只显示一次
        ];

        self::$SELECT_CHANNEL=[
            'keyboard' => [
                [
                    get_keyboard_name_config('select_channel.SelectChannel', KeyBoardName::SelectChannel),
                ],
                [
                    get_keyboard_name_config('submission.Restart', KeyBoardName::Restart),
                    get_keyboard_name_config('submission.CancelSubmission', KeyBoardName::CancelSubmission),
                ],
            ],
            'resize_keyboard' => true, // 让键盘大小适应屏幕
            'one_time_keyboard' => false, // 是否只显示一次
        ];

        self::$SELECT_CHANNEL_END=[
            'keyboard' => [
                [
                    get_keyboard_name_config('submission_end.ConfirmSubmissionOpen', KeyBoardName::ConfirmSubmissionOpen),
                    get_keyboard_name_config('submission_end.ConfirmSubmissionAnonymous', KeyBoardName::ConfirmSubmissionAnonymous),
                ],
                [
                    get_keyboard_name_config('submission.Restart', KeyBoardName::Restart),
                    get_keyboard_name_config('submission.CancelSubmission', KeyBoardName::CancelSubmission),
                    get_keyboard_name_config('select_channel_end.SelectChannelAgain', KeyBoardName::SelectChannelAgain),
                ],
            ],
            'resize_keyboard' => true, // 让键盘大小适应屏幕
            'one_time_keyboard' => false, // 是否只显示一次
        ];

        self::$START_FEEDBACK = [
            'keyboard' => [
                [
                    get_keyboard_name_config('feedback.SubmitComplaint', KeyBoardName::SubmitComplaint),
                    get_keyboard_name_config('feedback.SubmitSuggestion', KeyBoardName::SubmitSuggestion),
                ],
                [
                    get_keyboard_name_config('common.Cancel', KeyBoardName::Cancel),
                ],
            ],
            'resize_keyboard' => true, // 让键盘大小适应屏幕
            'one_time_keyboard' => false, // 是否只显示一次
        ];

        self::$START_COMPLAINT = [
            'keyboard' => [
                [
                    get_keyboard_name_config('complaint.EndSending', KeyBoardName::EndSending),
                ],
                [
                    get_keyboard_name_config('complaint.Restart', KeyBoardName::Restart),
                    get_keyboard_name_config('common.Cancel', KeyBoardName::Cancel),
                ],
            ],
            'resize_keyboard' => true, // 让键盘大小适应屏幕
            'one_time_keyboard' => false, // 是否只显示一次
        ];

        self::$END_COMPLAINT = [
            'keyboard' => [
                [
                    get_keyboard_name_config('complaint_end.ConfirmComplaint', KeyBoardName::ConfirmComplaint),
                ],
                [
                    get_keyboard_name_config('complaint.Restart', KeyBoardName::Restart),
                    get_keyboard_name_config('common.Cancel', KeyBoardName::Cancel),
                ],
            ],
            'resize_keyboard' => true, // 让键盘大小适应屏幕
            'one_time_keyboard' => false, // 是否只显示一次
        ];
    }

    /**
     * 黑名单用户删除键盘
     */
    const BLACKLIST_USER_DELETE = [
        'remove_keyboard' => true,
    ];

    const Cancel = [
        'keyboard' => [
            [
                KeyBoardName::Cancel,
            ],
        ],
        'resize_keyboard' => true, // 让键盘大小适应屏幕
        'one_time_keyboard' => false, // 是否只显示一次
    ];
}

KeyBoardData::init();

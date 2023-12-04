<?php

namespace App\Enums;

class KeyBoardData
{
    /**
     * 用户开启机器人初始键盘
     */
    const START = [
        'keyboard' => [
            [
                '开始投稿',
            ],
            /*[
                '意见反馈',
                '帮助中心',
            ],*/
        ],
        'resize_keyboard' => true, // 让键盘大小适应屏幕
        'one_time_keyboard' => false, // 是否只显示一次
    ];

    /**
     * 用户开始投稿键盘
     */
    const START_SUBMISSION = [
        'keyboard' => [
            [
                '结束发送',
            ],
            [
                '重新开始',
                '取消投稿',
            ],
        ],
        'resize_keyboard' => true, // 让键盘大小适应屏幕
        'one_time_keyboard' => false, // 是否只显示一次
    ];

    /**
     * 用户结束投稿键盘
     */
    const END_SUBMISSION = [
        'keyboard' => [
            [
                '确认投稿（公开）',
                '确认投稿（匿名）',
            ],
            [
                '取消投稿',
            ],
        ],
        'resize_keyboard' => true, // 让键盘大小适应屏幕
        'one_time_keyboard' => false, // 是否只显示一次
    ];

    /**
     * 审核群组键盘
     */
    const REVIEW_GROUP = [
        'inline_keyboard' => [
            [
                ['text' => '通过', 'callback_data' => 'text_approved_submission'],
                ['text' => '拒绝', 'callback_data' => 'text_reject_submission'],
                ['text' => '私聊', 'callback_data' => 'private_message'],
            ],
            [
                ['text' => '快捷通过', 'callback_data' => 'text_approved_submission_quick'],
                ['text' => '快捷拒绝', 'callback_data' => 'text_reject_submission_quick'],
            ],
        ],
    ];

    /**
     * 审核群组稿件通过键盘
     */
    const REVIEW_GROUP_APPROVED = [
        'inline_keyboard' => [
            [
                ['text' => '已通过', 'callback_data' => 'approved_submission'],
            ],
        ],
    ];

    /**
     * 审核群组稿件拒绝键盘
     */
    const REVIEW_GROUP_REJECT = [
        'inline_keyboard' => [
            [
                ['text' => '已拒绝', 'callback_data' => 'reject_submission'],
            ],
        ],
    ];
}

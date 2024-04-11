<?php

namespace App\Enums;

use http\Env;

class KeyBoardData
{
    /**
     * 用户开启机器人初始键盘
     */
    const START = [
        'keyboard' => [
            [
                KeyBoardName::StartSubmission,
            ],
            [
                KeyBoardName::Feedback,
                KeyBoardName::HelpCenter,
            ],
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
                KeyBoardName::EndSending,
            ],
            [
                KeyBoardName::Restart,
                KeyBoardName::CancelSubmission,
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
                KeyBoardName::ConfirmSubmissionOpen,
                KeyBoardName::ConfirmSubmissionAnonymous,
            ],
            [
                KeyBoardName::Restart,
                KeyBoardName::CancelSubmission,
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
                ['text' => KeyBoardName::Approved, 'callback_data' => 'approved_submission'],
                ['text' => KeyBoardName::Rejected, 'callback_data' => 'reject_submission'],
                ['text' => KeyBoardName::PrivateChat, 'callback_data' => 'private_message'],
            ],
            [
                ['text' => KeyBoardName::QuickApproved, 'callback_data' => 'approved_submission_quick'],
                ['text' => KeyBoardName::QuickRejected, 'callback_data' => 'reject_submission_quick'],
            ],
        ],
    ];

    /**
     * 审核群组稿件通过键盘
     */
    const REVIEW_GROUP_APPROVED = [
        'inline_keyboard' => [
            [
                ['text' => KeyBoardName::ApprovedEnd, 'callback_data' => 'approved_submission_button'],
                ['text'=>KeyBoardName::ViewMessage,'url'=>'https://t.me/'],
            ],
            [
                ['text' => KeyBoardName::DeleteMessage, 'callback_data' => 'delete_submission_message'],
            ],
        ],
    ];

    /**
     * 审核群组稿件拒绝键盘
     */
    const REVIEW_GROUP_REJECT = [
        'inline_keyboard' => [
            [
                ['text' => KeyBoardName::RejectedEnd, 'callback_data' => 'reject_submission_button'],
            ],
        ],
    ];

    /**
     * 审核群组稿件删除键盘
     */
    const REVIEW_GROUP_DELETE = [
        'inline_keyboard' => [
            [
                ['text' => KeyBoardName::MessageDeleted, 'callback_data' => 'delete_submission_message_success'],
            ]
        ],
    ];

    /**
     * 黑名单用户删除键盘
     */
    const BLACKLIST_USER_DELETE = [
        'remove_keyboard' => true,
    ];

    /**
     * 白名单用户投稿完成发送到审核群组键盘
     */
    const WHITE_LIST_USER_SUBMISSION = [
        'inline_keyboard' => [
            [
                ['text'=>KeyBoardName::ViewMessage,'url'=>'https://t.me/'],
                ['text' => KeyBoardName::DeleteWhiteListUser, 'callback_data' => 'delete_white_list_user_submission_message'],
            ],
        ],
    ];

    /**
     * 私聊用户信息键盘
     */
    const PRIVATE_MESSAGE = [
        'inline_keyboard' => [
            [
                ['text' => '通用用户名联系', 'url' => 'https://t.me/'],
                ['text' => '客户端协议联系', 'url' => 'tg://openmessage?user_id='],
                ['text' => 'ID链接联系', 'url' => 'https://t.me/@id'],
            ],
        ],
    ];

    /**
     * 查询投稿人信息键盘
     */
    const QUERY_SUBMISSION_USER = [
        'inline_keyboard' => [
            [
                ['text' => '设置为普通用户', 'callback_data' => 'set'],
            ],
        ],
    ];

    const SELECT_CHANNEL = [
        'keyboard' => [
            [
                KeyBoardName::SelectChannel,
            ],
            [
                KeyBoardName::Restart,
                KeyBoardName::CancelSubmission,
            ],
        ],
        'resize_keyboard' => true, // 让键盘大小适应屏幕
        'one_time_keyboard' => false, // 是否只显示一次
    ];

    const SELECT_CHANNEL_END = [
        'keyboard' => [
            [
                KeyBoardName::ConfirmSubmissionOpen,
                KeyBoardName::ConfirmSubmissionAnonymous,
            ],
            [
                KeyBoardName::Restart,
                KeyBoardName::CancelSubmission,
                KeyBoardName::SelectChannelAgain,
            ],
        ],
        'resize_keyboard' => true, // 让键盘大小适应屏幕
        'one_time_keyboard' => false, // 是否只显示一次
    ];
    const START_FEEDBACK = [
        'keyboard' => [
            [
                KeyBoardName::SubmitComplaint,
                KeyBoardName::SubmitSuggestion,
            ],
            [
                KeyBoardName::Cancel,
            ],
        ],
        'resize_keyboard' => true, // 让键盘大小适应屏幕
        'one_time_keyboard' => false, // 是否只显示一次
    ];
}

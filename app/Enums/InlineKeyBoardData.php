<?php

namespace App\Enums;

class InlineKeyBoardData
{


    /**
     * 审核群组稿件通过显示的键盘
     * @var array
     */
    public static array $REVIEW_GROUP_APPROVED;

    /**
     * 审核群组稿件拒绝显示的键盘
     */
    public static array $REVIEW_GROUP_REJECT;

    /**
     * 审核群组稿件删除显示的键盘
     * @var array
     */
    public static array $REVIEW_GROUP_DELETE;

    /**
     * 白名单用户投稿完成发送到审核群组键盘
     * @var array
     */
    public static array $WHITE_LIST_USER_SUBMISSION;

    public static array $ERROR_FOR_MESSAGE;

    public static function init(): void
    {
        self::$REVIEW_GROUP_APPROVED = [
            'inline_keyboard' => [
                [
                    [
                        'text' => get_keyboard_name_config('review_group_approved.ApprovedEnd',KeyBoardName::ApprovedEnd),
                        'callback_data' => 'approved_submission_button'
                    ],
                    [
                        'text' => get_keyboard_name_config('review_group_approved.ViewMessage',KeyBoardName::ViewMessage),
                        'url' => 'https://t.me/'
                    ],
                ],
                [
                    [
                        'text' => get_keyboard_name_config('review_group_approved.DeleteMessage',KeyBoardName::DeleteMessage),
                        'callback_data' => 'delete_submission_message'
                    ],
                ],
            ],
        ];

        self::$REVIEW_GROUP_REJECT = [
            'inline_keyboard' => [
                [
                    [
                        'text' => get_keyboard_name_config('review_group_rejected.RejectedEnd',KeyBoardName::RejectedEnd),
                        'callback_data' => 'reject_submission_button'
                    ],
                ],
            ],
        ];

        self::$REVIEW_GROUP_DELETE = [
            'inline_keyboard' => [
                [
                    [
                        'text' => get_keyboard_name_config('review_group_deleted.MessageDeleted',KeyBoardName::MessageDeleted),
                        'callback_data' => 'delete_submission_message_success'
                    ],
                ],
            ],
        ];

        self::$WHITE_LIST_USER_SUBMISSION=[
            'inline_keyboard' => [
                [
                    [
                        'text' => get_keyboard_name_config('white_list_user_submission.ViewMessage',KeyBoardName::ViewMessage),
                        'url' => 'https://t.me/'
                    ],
                    [
                        'text' => get_keyboard_name_config('white_list_user_submission.DeleteWhiteListUser',KeyBoardName::DeleteWhiteListUser),
                        'callback_data' => 'delete_white_list_user_submission_message'
                    ],
                ],
            ],
        ];

        self::$ERROR_FOR_MESSAGE = [
            'inline_keyboard' => [
                [
                    [
                        'text' => get_keyboard_name_config('start.error_and_submission',KeyBoardName::StartSubmission),
                        'callback_data' => 'quick_submission'
                    ],
                ],
            ],
        ];
    }

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

    /**
     * 审核群组的稿件审核键盘
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
     * 审核群组的投诉审核键盘
     */
    const REVIEW_GROUP_COMPLAINT = [
        'inline_keyboard' => [
            [
                ['text' => KeyBoardName::Approved, 'callback_data' => 'approved_complaint'],
                ['text' => KeyBoardName::Rejected, 'callback_data' => 'reject_complaint'],
                ['text' => KeyBoardName::PrivateChat, 'callback_data' => 'private_message'],
            ],
            [
                ['text' => KeyBoardName::QuickApproved, 'callback_data' => 'approved_complaint_quick'],
                ['text' => KeyBoardName::QuickRejected, 'callback_data' => 'reject_complaint_quick'],
            ],
        ],
    ];
}

InlineKeyBoardData::init();

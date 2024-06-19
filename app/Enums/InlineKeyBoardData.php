<?php

namespace App\Enums;

class InlineKeyBoardData
{
    /**
     * 审核群组稿件通过显示的键盘
     */
    public static array $REVIEW_GROUP_APPROVED;

    /**
     * 审核群组稿件拒绝显示的键盘
     */
    public static array $REVIEW_GROUP_REJECT;

    /**
     * 审核群组稿件删除显示的键盘
     */
    public static array $REVIEW_GROUP_DELETE;

    /**
     * 白名单用户投稿完成发送到审核群组键盘
     */
    public static array $WHITE_LIST_USER_SUBMISSION;

    public static array $ERROR_FOR_MESSAGE;

    /**
     * @var array 用户选择是否显示来源键盘
     */
    public static array $FORWARD_ORIGIN_SELECT;

    /**
     * @var array 用户输入来源键盘
     */
    public static array $FORWARD_ORIGIN_INPUT;

    /**
     * @var array 用户确认输入来源键盘
     */
    public static array $FORWARD_ORIGIN_INPUT_CONFIRM;

    public static array $DISABLE_MESSAGE_PREVIEW;

    public static array $DISABLE_NOTIFICATION;

    public static function init(): void
    {
        self::$REVIEW_GROUP_APPROVED = [
            'inline_keyboard' => [
                [
                    [
                        'text' => get_keyboard_name_config('review_group_approved.ApprovedEnd', KeyBoardName::ApprovedEnd),
                        'callback_data' => 'approved_submission_button',
                    ],
                    [
                        'text' => get_keyboard_name_config('review_group_approved.ViewMessage', KeyBoardName::ViewMessage),
                        'url' => 'https://t.me/',
                    ],
                ],
                [
                    [
                        'text' => get_keyboard_name_config('review_group_approved.DeleteMessage', KeyBoardName::DeleteMessage),
                        'callback_data' => 'delete_submission_message',
                    ],
                ],
            ],
        ];

        self::$REVIEW_GROUP_REJECT = [
            'inline_keyboard' => [
                [
                    [
                        'text' => get_keyboard_name_config('review_group_rejected.RejectedEnd', KeyBoardName::RejectedEnd),
                        'callback_data' => 'reject_submission_button',
                    ],
                ],
            ],
        ];

        self::$REVIEW_GROUP_DELETE = [
            'inline_keyboard' => [
                [
                    [
                        'text' => get_keyboard_name_config('review_group_deleted.MessageDeleted', KeyBoardName::MessageDeleted),
                        'callback_data' => 'delete_submission_message_success',
                    ],
                ],
            ],
        ];

        self::$WHITE_LIST_USER_SUBMISSION = [
            'inline_keyboard' => [
                [
                    [
                        'text' => get_keyboard_name_config('white_list_user_submission.ViewMessage', KeyBoardName::ViewMessage),
                        'url' => 'https://t.me/',
                    ],
                    [
                        'text' => get_keyboard_name_config('white_list_user_submission.DeleteWhiteListUser', KeyBoardName::DeleteWhiteListUser),
                        'callback_data' => 'delete_white_list_user_submission_message',
                    ],
                ],
            ],
        ];

        self::$ERROR_FOR_MESSAGE = [
            'inline_keyboard' => [
                [
                    [
                        'text' => get_keyboard_name_config('start.error_and_submission', KeyBoardName::StartSubmission),
                        'callback_data' => 'quick_submission',
                    ],
                ],
            ],
        ];

        self::$FORWARD_ORIGIN_SELECT = [
            'inline_keyboard' => [
                [
                    [
                        'text' => get_keyboard_name_config('submission.forward_origin_select_Yes', KeyBoardName::Yes),
                        'callback_data' => 'forward_origin_select_Yes'
                    ],
                    [
                        'text' => get_keyboard_name_config('submission.forward_origin_select_No', KeyBoardName::No),
                        'callback_data' => 'forward_origin_select_No'
                    ]
                ],
            ],
        ];

        self::$FORWARD_ORIGIN_INPUT = [
            'inline_keyboard' => [
                [
                    [
                        'text' => get_keyboard_name_config('submission.forward_origin_input_cancel', KeyBoardName::Cancel),
                        'callback_data' => 'forward_origin_input_cancel'
                    ]
                ],
            ],
        ];

        self::$FORWARD_ORIGIN_INPUT_CONFIRM = [
            'inline_keyboard' => [
                [
                    [
                        'text' => get_keyboard_name_config('submission.forward_origin_input_confirm', KeyBoardName::Confirm),
                        'callback_data' => 'forward_origin_input_confirm'
                    ],
                    [
                        'text' => get_keyboard_name_config('submission.forward_origin_input_cancel', KeyBoardName::Cancel),
                        'callback_data' => 'forward_origin_input_cancel'
                    ]
                ],
            ],
        ];

        self::$DISABLE_MESSAGE_PREVIEW = [
            'inline_keyboard' => [
                [
                    [
                        'text' => get_keyboard_name_config('common.yes', KeyBoardName::Yes),
                        'callback_data' => 'disable_message_preview_yes'
                    ],
                    [
                        'text' => get_keyboard_name_config('common.no', KeyBoardName::No),
                        'callback_data' => 'disable_message_preview_no'
                    ]
                ],
            ]
        ];

        self::$DISABLE_NOTIFICATION = [
            'inline_keyboard' => [
                [
                    [
                        'text' => get_keyboard_name_config('common.yes', KeyBoardName::Yes),
                        'callback_data' => 'disable_notification_yes'
                    ],
                    [
                        'text' => get_keyboard_name_config('common.no', KeyBoardName::No),
                        'callback_data' => 'disable_notification_no'
                    ]
                ],
            ]
        ];

        self::$PROTECT_CONTENT = [
            'inline_keyboard' => [
                [
                    [
                        'text' => get_keyboard_name_config('common.yes', KeyBoardName::Yes),
                        'callback_data' => 'protect_content_yes'
                    ],
                    [
                        'text' => get_keyboard_name_config('common.no', KeyBoardName::No),
                        'callback_data' => 'protect_content_no'
                    ]
                ],
            ]
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

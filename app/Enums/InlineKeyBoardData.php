<?php

namespace App\Enums;

class InlineKeyBoardData
{
    const ERROR_FOR_MESSAGE = [
        'inline_keyboard' => [
            [
                ['text' => KeyBoardName::StartSubmission, 'callback_data' => 'start_submission'],
                ['text' => KeyBoardName::Feedback, 'callback_data' => 'feedback'],
            ],
            [
                ['text' => KeyBoardName::SubmitComplaint, 'callback_data' => 'submit_complaint'],
                ['text' => KeyBoardName::HelpCenter, 'callback_data' => 'help_center'],
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

<?php

namespace App\Enums;

class AuditorRole
{
    const QUICK_APPROVAL = 1;
    const QUICK_REJECTION = 2;
    const PRIVATE_CHAT_SUBMISSION = 3;

    const APPROVAL = 4;

    const REJECTION = 5;

    const DELETE_SUBMISSION = 6;

    const ROLE_NAME = [
        self::QUICK_APPROVAL => '快捷通过审核',
        self::QUICK_REJECTION => '快捷拒绝审核',
        self::PRIVATE_CHAT_SUBMISSION => '私聊投稿人',
        self::APPROVAL => '通过',
        self::REJECTION => '拒绝',
        self::DELETE_SUBMISSION => '删除投稿',
    ];
}

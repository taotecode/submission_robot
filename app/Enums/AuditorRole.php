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

    //添加黑名单
    const ADD_BLACK = 7;

    //设置投稿人类型
    const SET_SUBMISSION_USER_TYPE = 8;

    const ROLE_NAME = [
        self::QUICK_APPROVAL => '快捷通过审核',
        self::QUICK_REJECTION => '快捷拒绝审核',
        self::PRIVATE_CHAT_SUBMISSION => '私聊投稿人',
        self::APPROVAL => '通过',
        self::REJECTION => '拒绝',
        self::DELETE_SUBMISSION => '删除投稿',
        self::ADD_BLACK => '添加黑名单',
        self::SET_SUBMISSION_USER_TYPE => '设置投稿人身份',
    ];

    const ALL_ROLE = [
        self::QUICK_APPROVAL,
        self::QUICK_REJECTION,
        self::PRIVATE_CHAT_SUBMISSION,
        self::APPROVAL,
        self::REJECTION,
        self::DELETE_SUBMISSION,
        self::ADD_BLACK,
        self::SET_SUBMISSION_USER_TYPE,
    ];
}

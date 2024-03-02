<?php

namespace App\Enums;

class ManuscriptStatus
{
    const PENDING = 0;

    const APPROVED = 1;

    const REJECTED = 2;

    const DELETE = 3;

    const ALL_NAME = [
        self::PENDING => '待审核',
        self::APPROVED => '已通过',
        self::REJECTED => '已拒绝',
        self::DELETE => '已删除',
    ];
}

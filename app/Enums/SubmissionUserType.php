<?php

namespace App\Enums;

class SubmissionUserType
{
    const NORMAL = 0;

    const WHITE = 1;

    const BLACK = 2;

    const MAP = [
        self::NORMAL => '普通',
        self::WHITE => '白名单',
        self::BLACK => '黑名单',
    ];

    public static function getMap(): array
    {
        return [
            self::NORMAL => '普通',
            self::WHITE => '白名单',
            self::BLACK => '黑名单',
        ];
    }

    public static function getKey(): array
    {
        return array_keys(self::MAP);
    }
}

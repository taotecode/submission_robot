<?php

namespace App\Enums;

class Commands
{
    const DEFAULT = [
        'start' => '开始投稿',
        'help' => '帮助中心',
    ];

    const DEFAULT_OPTIONS = [
        0 => 'start - 开始投稿',
        1 => 'help - 帮助中心',
    ];

    const ALL_GROUP = [
        'get_group_id' => 'get_group_id - 获取群组ID',
    ];

    const ALL_GROUP_OPTIONS = [
        0 => 'get_group_id - 获取群组ID',
    ];
}

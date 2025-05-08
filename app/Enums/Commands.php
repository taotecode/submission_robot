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
        2 => 'get_me_id - 获取用户自己的ID',
        3 => 'my_submission - 我的投稿',
        4 => 'my_setting - 个人设置',
    ];

    const ALL_GROUP = [
        'get_group_id' => 'get_group_id - 获取群组ID',
    ];

    const ALL_GROUP_OPTIONS = [
        0 => 'get_group_id - 获取群组ID',
    ];

    const OPTIONS = [
        'start' => '开始使用机器人命令',
        'help' => '查看机器人帮助',
        'get_me_id' => '获取用户自己的ID',
        'get_group_id' => '获取用户当前所在群组使用该命令的群组ID',
        'list' => '展示当前机器人待审核的稿件列表',
    ];

    const SCOPES = [
        'default' => '默认（全部可用）',
        'all_private_chats' => '私聊（所有私聊可用）',
        'all_group_chats' => '群聊（所有群聊可用）',
        'all_chat_administrators' => '群聊（所有群聊中的管理员可用）',
        'chat' => '指定对话（需要对话的chat_id）',
    ];

    const COMMAND_START_BUTTON = [
        'start_submission'=>'开始投稿',
        'start_complaint'=>'意见反馈',
        'my_submission'=>'我的投稿',
        'my_setting'=>'个人设置',
    ];
}

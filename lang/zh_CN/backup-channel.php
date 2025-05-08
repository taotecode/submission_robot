<?php

return [
    'labels' => [
        'BackupChannel' => '备份频道管理',
        'backup_channel' => '备份频道管理',
    ],
    'fields' => [
        'channel_id' => '主频道',
        'channel.name' => '主频道用户名',
        'channel.appellation' => '主频道名称',
        'backup_name' => '备份频道用户名',
        'backup_appellation' => '备份频道名称',
        'is_public' => '是否公开频道',
        'chat_id' => '私有频道聊天ID',
        'sort_order' => '排序',
    ],
    'options' => [
        'is_public' => [
            1 => '公开',
            0 => '私有',
        ],
    ],
    'helps' => [
        'channel_id' => '选择要备份的主频道',
        'backup_name' => '备份频道公开链接，如：https://t.cn/backup_channel，那么就可以填：backup_channel，注意不要带@',
        'backup_appellation' => '备份频道名称，如：这是一个备份频道',
        'chat_id' => '私有频道的chat_id',
    ],
]; 
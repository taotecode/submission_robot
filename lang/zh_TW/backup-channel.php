<?php

return [
    'labels' => [
        'BackupChannel' => '備份頻道管理',
        'backup-channel' => '備份頻道管理',
    ],
    'fields' => [
        'channel_id' => '主頻道',
        'channel.name' => '主頻道用戶名',
        'channel.appellation' => '主頻道名稱',
        'backup_name' => '備份頻道用戶名',
        'backup_appellation' => '備份頻道名稱',
        'is_public' => '是否公開頻道',
        'chat_id' => '私有頻道聊天ID',
        'sort_order' => '排序',
    ],
    'options' => [
        'is_public' => [
            1 => '公開',
            0 => '私有',
        ],
    ],
    'helps' => [
        'channel_id' => '選擇要備份的主頻道',
        'backup_name' => '備份頻道公開鏈接，如：https://t.cn/backup_channel，那麼就可以填：backup_channel，注意不要帶@',
        'backup_appellation' => '備份頻道名稱，如：這是一個備份頻道',
        'chat_id' => '私有頻道的chat_id',
    ],
]; 
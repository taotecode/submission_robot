<?php

return [
    'labels' => [
        'BackupChannel' => 'Backup Channel Management',
        'backup-channel' => 'Backup Channel',
    ],
    'fields' => [
        'channel_id' => 'Main Channel',
        'channel.name' => 'Main Channel Username',
        'channel.appellation' => 'Main Channel Name',
        'backup_name' => 'Backup Channel Username',
        'backup_appellation' => 'Backup Channel Name',
        'is_public' => 'Is Public Channel',
        'chat_id' => 'Private Channel Chat ID',
        'sort_order' => 'Sort Order',
    ],
    'options' => [
        'is_public' => [
            1 => 'Public',
            0 => 'Private',
        ],
    ],
    'helps' => [
        'channel_id' => 'Select the main channel to backup',
        'backup_name' => 'Backup channel public link, e.g.: if the link is https://t.cn/backup_channel, then fill in: backup_channel, do not include @',
        'backup_appellation' => 'Backup channel name, e.g.: This is a backup channel',
        'chat_id' => 'Chat ID of the private channel',
    ],
]; 
<?php

return [
    'labels' => [
        'Manuscript' => '稿件管理',
        'manuscript' => '稿件管理',
    ],
    'fields' => [
        'type' => '类型',
        'text' => '投稿内容',
        'posted_by' => '投稿人信息',
        'data' => '投稿整体信息',
        'appendix' => '投稿附件',
        'approved' => '通过审核员',
        'reject' => '拒绝审核员',
        'one_approved' => '快速通过审核员',
        'one_reject' => '快速拒绝审核员',
        'status' => '状态',
        'bot_id' => '机器人',
        'channel_id' => '频道',
        'message_id' => '消息ID',
        'backup_message_ids' => '备份频道消息',
        'is_anonymous' => '匿名发布',
    ],
    'options' => [
    ],
    'helps' => [
        'text' => '支持HTML格式，请谨慎编辑',
        'is_anonymous' => '开启后将不显示投稿人信息',
    ],
    'actions' => [
        'publish' => '发布',
        'batch_publish' => '批量发布',
        'update_channel' => '更新频道',
        'create_manuscript' => '新建稿件',
    ],
    'messages' => [
        'publish_success' => '发布成功',
        'publish_failed' => '发布失败',
        'update_success' => '更新成功',
        'update_failed' => '更新失败',
        'manuscript_not_exist' => '稿件不存在',
        'select_manuscripts' => '请选择要发布的稿件',
        'publish_success_count' => '成功发布 :count 个稿件',
    ],
];

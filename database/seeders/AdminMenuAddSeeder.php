<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminMenuAddSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $data = [
            ['id' => 8, 'parent_id' => 0, 'order' => 2, 'title' => '我的机器人', 'icon' => 'fa-android', 'uri' => '/bots', 'extension' => '', 'show' => 1, 'created_at' => '2023-08-30 06:28:08', 'updated_at' => '2023-08-30 06:28:08'],
            ['id' => 9, 'parent_id' => 0, 'order' => 3, 'title' => '审核人群', 'icon' => 'fa-address-book', 'uri' => null, 'extension' => '', 'show' => 1, 'created_at' => '2023-08-30 06:28:08', 'updated_at' => '2023-08-30 06:28:08'],
            ['id' => 10, 'parent_id' => 9, 'order' => 1, 'title' => '审核人员', 'icon' => 'fa-user', 'uri' => '/auditors', 'extension' => '', 'show' => 1, 'created_at' => '2023-08-30 06:28:08', 'updated_at' => '2023-08-30 06:28:08'],
            ['id' => 11, 'parent_id' => 9, 'order' => 2, 'title' => '审核群组', 'icon' => 'fa-users', 'uri' => '/review_groups', 'extension' => '', 'show' => 1, 'created_at' => '2023-08-30 06:28:08', 'updated_at' => '2023-08-30 06:28:08'],
            ['id' => 12, 'parent_id' => 0, 'order' => 4, 'title' => '频道管理', 'icon' => 'fa-bullhorn', 'uri' => '/channel', 'extension' => '', 'show' => 1, 'created_at' => '2023-08-30 06:28:08', 'updated_at' => '2023-08-30 06:28:08'],
            ['id' => 15, 'parent_id' => 0, 'order' => 5, 'title' => '投稿人管理', 'icon' => 'fa-user', 'uri' => '/submission_user', 'extension' => '', 'show' => 1, 'created_at' => '2023-08-30 06:28:08', 'updated_at' => '2023-08-30 06:28:08'],
            ['id' => 16, 'parent_id' => 0, 'order' => 6, 'title' => '机器人用户群', 'icon' => 'fa-users', 'uri' => '/bot_user', 'extension' => '', 'show' => 1, 'created_at' => '2023-08-30 06:28:08', 'updated_at' => '2023-08-30 06:28:08'],
            ['id' => 17, 'parent_id' => 0, 'order' => 7, 'title' => '机器人消息', 'icon' => 'fa-users', 'uri' => '/bot_message', 'extension' => '', 'show' => 1, 'created_at' => '2023-08-30 06:28:08', 'updated_at' => '2023-08-30 06:28:08'],
            ['id' => 14, 'parent_id' => 0, 'order' => 8, 'title' => '稿件管理', 'icon' => 'fa-newspaper-o', 'uri' => '/manuscript', 'extension' => '', 'show' => 1, 'created_at' => '2023-08-30 06:28:08', 'updated_at' => '2023-08-30 06:28:08'],
            ['id' => 18, 'parent_id' => 0, 'order' => 9, 'title' => '配置管理', 'icon' => 'fa-gears', 'uri' => null, 'extension' => '', 'show' => 1, 'created_at' => '2023-08-30 06:28:08', 'updated_at' => '2023-08-30 06:28:08'],
            ['id' => 13, 'parent_id' => 18, 'order' => 1, 'title' => '配置表', 'icon' => 'fa-wrench', 'uri' => '/config', 'extension' => '', 'show' => 1, 'created_at' => '2023-08-30 06:28:08', 'updated_at' => '2023-08-30 06:28:08'],
            ['id' => 19, 'parent_id' => 18, 'order' => 2, 'title' => '键盘名称表', 'icon' => 'fa-sliders', 'uri' => '/keyboard_name_config', 'extension' => '', 'show' => 1, 'created_at' => '2023-08-30 06:28:08', 'updated_at' => '2023-08-30 06:28:08'],
        ];
        foreach ($data as $item) {
            DB::table('admin_menu')->updateOrInsert(['id' => $item['id']], $item);
        }
    }
}

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
            ['id' => 9, 'parent_id' => 0, 'order' => 4, 'title' => '审核人群', 'icon' => 'fa-address-book', 'uri' => null, 'extension' => '', 'show' => 1, 'created_at' => '2023-08-30 06:28:08', 'updated_at' => '2023-08-30 06:28:08'],
            ['id' => 10, 'parent_id' => 9, 'order' => 5, 'title' => '审核人员', 'icon' => 'fa-user', 'uri' => '/auditors', 'extension' => '', 'show' => 1, 'created_at' => '2023-08-30 06:28:08', 'updated_at' => '2023-08-30 06:28:08'],
            ['id' => 11, 'parent_id' => 9, 'order' => 6, 'title' => '审核群组', 'icon' => 'fa-users', 'uri' => '/review_groups', 'extension' => '', 'show' => 1, 'created_at' => '2023-08-30 06:28:08', 'updated_at' => '2023-08-30 06:28:08'],
            ['id' => 12, 'parent_id' => 0, 'order' => 3, 'title' => '频道管理', 'icon' => 'fa-bullhorn', 'uri' => '/channel', 'extension' => '', 'show' => 1, 'created_at' => '2023-08-30 06:28:08', 'updated_at' => '2023-08-30 06:28:08'],
            ['id' => 13, 'parent_id' => 2, 'order' => 13, 'title' => '配置表', 'icon' => 'fa-bullhorn', 'uri' => '/config', 'extension' => '', 'show' => 1, 'created_at' => '2023-08-30 06:28:08', 'updated_at' => '2023-08-30 06:28:08'],
            ['id' => 14, 'parent_id' => 0, 'order' => 13, 'title' => '稿件管理', 'icon' => 'fa-newspaper-o', 'uri' => '/manuscript', 'extension' => '', 'show' => 1, 'created_at' => '2023-08-30 06:28:08', 'updated_at' => '2023-08-30 06:28:08'],
        ];
        foreach ($data as $item) {
            DB::table('admin_menu')->updateOrInsert(['id' => $item['id']], $item);
        }
    }
}

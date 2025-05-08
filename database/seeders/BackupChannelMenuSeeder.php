<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BackupChannelMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 获取现有最大ID
        $maxId = DB::table('admin_menu')->max('id');
        
        // 将频道管理修改为子菜单模式
        DB::table('admin_menu')
            ->where('id', 12)
            ->update([
                'uri' => null,
                'updated_at' => now()
            ]);
            
        // 添加子菜单项
        $data = [
            [
                'id' => $maxId + 1, 
                'parent_id' => 12, 
                'order' => 1, 
                'title' => '主频道管理', 
                'icon' => 'fa-bullhorn', 
                'uri' => '/channel', 
                'extension' => '', 
                'show' => 1, 
                'created_at' => now(), 
                'updated_at' => now()
            ],
            [
                'id' => $maxId + 2, 
                'parent_id' => 12, 
                'order' => 2, 
                'title' => '备份频道管理', 
                'icon' => 'fa-copy', 
                'uri' => '/backup_channel', 
                'extension' => '', 
                'show' => 1, 
                'created_at' => now(), 
                'updated_at' => now()
            ],
        ];
        
        foreach ($data as $item) {
            DB::table('admin_menu')->updateOrInsert(['id' => $item['id']], $item);
        }
    }
} 
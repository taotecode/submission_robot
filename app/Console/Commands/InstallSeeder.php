<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class InstallSeeder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '初次安装系统时运行所有必要的种子文件';

    /**
     * 所有种子文件列表
     * 
     * @var array
     */
    protected $seeders = [
        \Database\Seeders\ConfigSeeder::class,
        \Database\Seeders\KeyboardNameConfigSeeder::class,
        \Database\Seeders\AdminMenuAddSeeder::class,
        \Database\Seeders\BotCommandsSeeder::class,
        \Database\Seeders\BackupChannelMenuSeeder::class,
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('开始执行初始化种子文件...');
        
        $this->info('执行数据库迁移...');
        $this->call('migrate');
        
        $this->info('开始执行种子文件...');
        foreach ($this->seeders as $seeder) {
            $this->info("运行: " . class_basename($seeder));
            $this->call('db:seed', ['--class' => $seeder]);
        }
        
        $this->info('系统初始化完成！');
        return Command::SUCCESS;
    }
}

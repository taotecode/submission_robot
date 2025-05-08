<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UpdateSeeder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update
                            {--migrate : 是否需要执行数据库迁移}
                            {--all : 运行所有种子文件}
                            {--config : 运行配置种子文件}
                            {--keyboard : 运行键盘配置种子文件}
                            {--menu : 运行菜单种子文件}
                            {--backup : 运行备份频道菜单种子文件}
                            {--commands : 运行机器人命令种子文件}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '系统更新时运行指定的种子文件，用于后期维护';

    /**
     * 种子文件映射
     * 
     * @var array
     */
    protected $seederMap = [
        'config' => \Database\Seeders\ConfigSeeder::class,
        'keyboard' => \Database\Seeders\KeyboardNameConfigSeeder::class,
        'menu' => \Database\Seeders\AdminMenuAddSeeder::class,
        'commands' => \Database\Seeders\BotCommandsSeeder::class,
        'backup' => \Database\Seeders\BackupChannelMenuSeeder::class,
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('开始执行更新...');
        
        // 执行数据库迁移
        if ($this->option('migrate')) {
            $this->info('执行数据库迁移...');
            $this->call('migrate');
        }
        
        // 确定要运行的种子文件
        $seedersToRun = [];
        
        // 检查是否运行所有种子文件
        if ($this->option('all')) {
            $seedersToRun = array_values($this->seederMap);
            $this->info('将运行所有种子文件');
        } else {
            // 检查各个选项
            foreach ($this->seederMap as $option => $seeder) {
                if ($this->option($option)) {
                    $seedersToRun[] = $seeder;
                    $this->info("添加到运行队列: " . class_basename($seeder));
                }
            }
        }
        
        // 没有选择任何种子文件
        if (empty($seedersToRun)) {
            $this->warn('没有选择任何种子文件，使用 --all 运行所有种子文件或选择特定种子文件。');
            $this->info('可用选项:');
            foreach ($this->seederMap as $option => $seeder) {
                $this->line("  --{$option} : " . class_basename($seeder));
            }
            return Command::SUCCESS;
        }
        
        // 运行选定的种子文件
        $this->info('开始执行种子文件...');
        foreach ($seedersToRun as $seeder) {
            $this->info("运行: " . class_basename($seeder));
            $this->call('db:seed', ['--class' => $seeder]);
        }
        
        $this->info('更新完成！');
        return Command::SUCCESS;
    }
}

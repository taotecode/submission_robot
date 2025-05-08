# 安装教程

以下教程为了方便快速搭建，使用Centos 7.6进行，最低支持服务器配置为：1H2G


## 支持环境

- PHP >= 8.2
- MySQL >= 5.7
- Redis >= 6.0
- Composer >= 2.1
- Nginx >= 1.16

PHP扩展：
- OpenSSL PHP 扩展
- PDO PHP 扩展
- Fileinfo PHP 扩展
- Redis PHP 扩展
- opcache PHP 扩展

## 克隆项目
首先先进入到你的网站根目录，如：/www，不同的人的网站目录位置不一样
```bash
cd /www
```
然后克隆项目
```bash
git clone https://github.com/taotecode/submission_robot.git
```

## 安装依赖

首先确保你已安装Composer，如果没有安装，可以参考[Composer官网](https://getcomposer.org/download/)进行安装

然后进入到项目目录
```bash
cd submission_robot
```

安装依赖
```bash
composer install
```

如果报错，可以尝试使用下面命令
```bash
composer install --ignore-platform-reqs
```

## 配置

执行命令复制配置文件
```bash
cp .env.example .env
```

然后编辑.env文件，修改数据库配置

具体配置内容可以参照laravel官网[配置](https://learnku.com/docs/laravel/10.x/configuration/14836)

## 数据库迁移和初始化

### 方式一：一键安装（推荐）

使用我们提供的一键安装命令来完成所有数据库迁移和种子文件的加载：

```bash
php artisan app:install
```

这个命令将会自动执行：
- 所有数据库迁移
- 所有必要的种子文件，包括系统配置、菜单、键盘设置等

### 方式二：分步骤安装

如果你想分步骤执行，可以使用以下命令：

```bash
# 执行数据库迁移
php artisan migrate

# 加载系统配置
php artisan db:seed --class=ConfigSeeder

# 加载管理员表
php artisan db:seed --class="Dcat\Admin\Models\AdminTablesSeeder"

# 加载菜单配置
php artisan db:seed --class=AdminMenuAddSeeder

# 加载其他必要配置
php artisan db:seed --class=KeyboardNameConfigSeeder
php artisan db:seed --class=BotCommandsSeeder
php artisan db:seed --class=BackupChannelMenuSeeder
```

## 配置网站伪静态

在Nginx配置文件中添加以下内容
```nginx
server {
    listen 80;
    server_name 你的域名;
    root /www/submission_robot/public;
    index index.php index.html index.htm;
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    location ~ \.php$ {
        fastcgi_pass unix:/run/php-fpm/www.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

或者直接配置伪静态规则
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

然后重启Nginx
```bash
systemctl restart nginx
```

## 访问后台

后台账户：admin，密码：admin，登陆后记得修改密码，登陆地址： **http://你的域名/admin**

## 版本更新

### 方式一：一键更新（推荐）

使用我们提供的更新命令快速完成系统更新：

```bash
# 更新代码
git pull
composer install

# 使用一键更新命令（包含数据库迁移）
php artisan app:update --migrate --all
```

### 高级更新选项

你可以按需选择只更新特定部分：

```bash
# 只执行数据库迁移
php artisan app:update --migrate

# 只更新系统配置
php artisan app:update --config

# 只更新菜单和备份频道设置
php artisan app:update --menu --backup

# 查看所有可用选项
php artisan app:update
```

### 方式二：分步骤更新

如果你希望手动控制更新过程，可以使用以下命令：

```bash
git pull
composer install
php artisan migrate
php artisan db:seed --class=ConfigSeeder
php artisan db:seed --class=AdminMenuAddSeeder
```

帮助文档及常见问题：[HELP.md](https://github.com/taotecode/submission_robot/blob/master/HELP.md)

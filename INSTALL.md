# 安装教程

以下教程为了方便快速搭建，使用Centos 7.6进行


## 支持环境

- PHP >= 8.1
- MySQL >= 5.7
- Redis >= 6.0
- Composer >= 2.1

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

## 数据库迁移

执行命令进行数据库迁移
```bash
php artisan migrate
php artisan db:seed --class=ConfigSeeder
php artisan db:seed --class="Dcat\Admin\Models\AdminTablesSeeder"
php artisan db:seed --class=AdminMenuAddSeeder
```

后台账户：admin，密码：admin，登陆后记得修改密码，登陆地址： **http://你的域名/admin**

## 版本更新

如果是更新版本，可以使用下面命令进行更新
```bash
git pull
composer install
php artisan migrate
php artisan db:seed --class=ConfigSeeder
php artisan db:seed --class=AdminMenuAddSeeder
```

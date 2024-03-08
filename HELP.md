# 帮助文档

## 命令大全

### 私聊中使用

- `/help` 查看帮助文档
- `/start` 开始使用机器人投稿
- `/get_me_id` 获取自己的 Telegram ID

### 群组中使用

- `/get_group_id` 获取当前群组的 ID


## 常见问题

### 出现报错： `putenv() has been disabled for security reasons` 怎么解决？
这种错误是因为服务器禁用了 `putenv` 函数，可以尝试在 `php.ini` 中搜索 `disable_functions`，然后将 `putenv` 从其中移除。

如果还有以下这些函数，也需要移除：
```
proc_open
proc_get_status
```

### 出现错误： `PHP Fatal error:  Uncaught ErrorException: file_put_contents(***): Failed to open stream: Permission denied in ***`

这种错误是因为没有权限写入文件，可以尝试给予项目目录写入权限：
```bash
cd 项目目录
# 给予 storage 目录写入权限
chmod -R 777 storage
```

## 问题或咨询提交并解决

在提交问题之前，请先查看 [常见问题](#常见问题)。

提交问题的时候，请附带并上传错误日志，方便我更快的解决问题。路径：`storage/logs/laravel.log`。

如果你在使用过程中遇到了问题，可以提交 [Issue](https://github.com/taotecode/submission_robot/issues)，我会尽快解决。

如果你有其他问题或咨询，可以通过以下方式联系我：

联系：[@laocheng_user_bot](https://t.me/laocheng_user_bot)

交流群：[纸飞机：@submission_robot_chat](https://t.me/submission_robot_chat)

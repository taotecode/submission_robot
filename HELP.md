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

# 帮助文档

## 命令大全

### 私聊中使用

- `/help` 查看帮助文档
- `/start` 开始使用机器人投稿
- `/get_me_id` 获取自己的 Telegram ID

### 群组中使用

- `/get_group_id` 获取当前群组的 ID

### 审核群组中使用

- `/list` 查看未审核的投稿

![image](https://github.com/taotecode/submission_robot/blob/master/docs/images/list.jpg)

- `/who` 查看投稿用户（需要回复投稿的稿件消息）

![image](https://github.com/taotecode/submission_robot/blob/master/docs/images/who.jpg)

- `/black` 拉黑用户（需要携带用户ID，如：`/black 12345678`）

![image](https://github.com/taotecode/submission_robot/blob/master/docs/images/black.jpg)

- `/s` 检索投稿（需要携带关键词，如：`/s 关键词`）

![image](https://github.com/taotecode/submission_robot/blob/master/docs/images/s.jpg)

***

## 后台使用

- `/admin` 进入后台管理

### 设置机器人

进入后台管理后，点击左侧菜单 `我的机器人`，然后点击 `新增`。

- 机器人称号：机器人的称号，用于区分不同的机器人，相当于一个昵称
- 机器人用户名：机器人的用户名，例如：`@submission_robot`
- 机器人 Token：机器人的 Token，例如：`123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11`，至于如何获取机器人 Token，请自行搜索
- 审核数量：每次审核的稿件数量，比如：`5`，表示这个稿件需要同时5个人审核通过才能发布
- 状态：机器人的状态，是否启用



#### 设置Web Hook
点击列表右侧的三点，然后点击 `设置Web Hook`，即可。

注意：
- 网站必须配置域名，免费的也好，收费的也好，都可以。
- 网站必须是https的，否则无法设置成功。且必须是公网IP。

相关小方案，如果获取免费SSL证书，可以使用这些厂家：Let's Encrypt、ZeroSSL、SSL For Free

**当您输入错机器人用户名或token的时候，需要重新设置Web Hook!!!**

#### 设置命令

点击列表右侧的三点，然后点击 `设置命令`，即可。

**当您输入错机器人用户名或token的时候，需要重新设置Web Hook和命令!!!**

#### 设置消息尾部

点击列表右侧的三点，然后点击 `设置消息尾部`。

你可以设置在投稿的尾部自定义一些文字，比如：`这是一条尾部消息`，这样在投稿成功的时候，会自动在尾部追加上对应文本。

消息尾部按钮组内容，可以自定义按钮组，比如：`交流群`、`投稿链接`，这样在投稿成功的时候，可以直接点击按钮进行操作。

#### 设置关键词

点击列表右侧的三点，然后点击 `设置关键词`。

关于自动关键词

首先需要配置预设的关键词，同样你可以添加针对性的词库用于自动分割词语。

什么意思呢？

比如：

关键词设置为：`投稿`、`交流`、`群组`、`链接`

用户投稿的文本为：`我想投稿一些内容到你的群组里面`

那么，机器人会自动将文本分割成：`我`、`想`、`投稿`、`一些`、`内容`、`到`、`你的`、`群组`、`里面`

然后，会自动匹配关键词，如果匹配到了，就会自动添加标签。上面示例中，会自动添加标签：`投稿`、`群组`

词库的作用是，如果你有一些特殊的词语，比如：`我想投稿`，那么你可以添加这个词语，这样用户投稿的时候，机器人就会自动分割为：`我想投稿`、`一些`、`内容`、`到`、`你的`、`群组`、`里面`

当你遇到词库都添加了词语的时候，机器人没有优先分割到词库的词语，那么你就需要在词语的后面添加一个空格再加上数字，这样机器人就会特级优先分割。

如：`我想投稿 1`，这样机器人就会优先分割为：`我想投稿`，而不是分割为：`我想`、`投稿`。

数字可以是任意数字，只要不是0就行。数值越大，优先级越高。最高优先级是10。请自行分配。

#### 设置发布频道

点击列表右侧的三点，然后点击 `设置发布频道`。
选择机器人发布的频道，只可以选择一个频道进行一对一发布。

***

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

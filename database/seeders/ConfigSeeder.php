<?php

namespace Database\Seeders;

use App\Models\Config;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            [
                'id' => 1,
                'group' => 'submission',
                'name' => 'start',
                'value' => "请直接发送您要投稿的内容\r\n发送完毕后，请点击下方的 “结束发送” 按钮。",
                'description' => '开始投稿提示语',
                'created_at' => '2023-09-17 08:12:22',
                'updated_at' => '2023-09-17 08:12:22',
            ],
            [
                'id' => 2,
                'group' => 'submission',
                'name' => 'restart',
                'value' => "已清空\r\n请重新发送内容。",
                'description' => '重新开始投稿提示语',
                'created_at' => '2023-09-17 08:37:09',
                'updated_at' => '2023-09-17 08:37:21',
            ],
            [
                'id' => 3,
                'group' => 'submission',
                'name' => 'cancel',
                'value' => '已取消投稿',
                'description' => '取消投稿提示语',
                'created_at' => '2023-09-17 08:39:01',
                'updated_at' => '2023-09-17 08:39:01',
            ],
            [
                'id' => 4,
                'group' => 'submission',
                'name' => 'error_for_text',
                'value' => "请点击下方的键盘按钮，或者发送对应的关键词。\r\n您也可以通过 /start 重新初始化所有操作。",
                'description' => '向Telegram聊天发送文本的错误消息。',
                'created_at' => '2023-09-17 08:40:58',
                'updated_at' => '2023-09-17 08:44:07',
            ],
            [
                'id' => 5,
                'group' => 'submission',
                'name' => 'start_text_tips',
                'value' => "已记录到草稿中。\r\n您可以继续发送新的内容来替换上一次的内容。\r\n当您觉得稿件内容合适时，点击下方的 “结束发送”。\r\n稿件草稿有效期为1天",
                'description' => '开始投稿输入内容的提示语',
                'created_at' => '2023-09-17 08:43:16',
                'updated_at' => '2023-09-17 08:43:48',
            ],
            [
                'id' => 6,
                'group' => 'submission',
                'name' => 'start_update_text_tips',
                'value' => "已更新当前稿件内容。\r\n您可以继续发送新的内容来替换上一次的内容。\r\n当您觉得稿件内容合适时，点击下方的 “结束发送”。\r\n稿件草稿有效期为1天",
                'description' => '开始投稿更新输入内容的提示语',
                'created_at' => '2023-09-17 08:45:06',
                'updated_at' => '2023-09-17 08:45:30',
            ],
            [
                'id' => 7,
                'group' => 'submission',
                'name' => 'preview_tips',
                'value' => "已生成预览。\r\n请确认您的投稿信息。",
                'description' => '预览投稿提示语',
                'created_at' => '2023-09-17 08:48:16',
                'updated_at' => '2023-09-17 08:48:41',
            ],
            [
                'id' => 8,
                'group' => 'submission',
                'name' => 'confirm',
                'value' => "✅ 投稿成功，我们将稍后通过机器人告知您审核结果，请保持联系畅通\r\n审核可能需要一定时间，如果您长时间未收到结果，可联系管理员。\r\n您现在可以开始下一个投稿。",
                'description' => '投稿提交提示语',
                'created_at' => '2023-09-17 08:49:45',
                'updated_at' => '2023-09-17 08:49:45',
            ],
            [
                'id' => 9,
                'group' => 'submission',
                'name' => 'review_approved_submission',
                'value' => "✅ 投稿已通过审核，已发送到频道。\r\n\r\n 稿件消息直达链接：<a href='{url}'>{title}</a>",
                'description' => '审核通过投稿提示语',
                'created_at' => '2023-11-23 21:13:00',
                'updated_at' => '2023-11-23 21:13:00',
            ],
            [
                'id' => 10,
                'group' => 'submission',
                'name' => 'review_rejected_submission',
                'value' => '❌ 投稿未通过审核，已删除。',
                'description' => '审核未通过投稿提示语',
                'created_at' => '2023-11-23 21:13:00',
                'updated_at' => '2023-11-23 21:13:00',
            ],
            [
                'id' => 11,
                'group' => 'submission',
                'name' => 'submission_is_empty',
                'value' => '您还没有输入任何内容，请重新输入！',
                'description' => '投稿内容为空提示语',
                'created_at' => '2023-11-23 21:13:00',
                'updated_at' => '2023-11-23 21:13:00',
            ],
            [
                'id' => 12,
                'group' => 'submission',
                'name' => 'confirm_white_list',
                'value' => "您已经在白名单中，您的投稿将直接发送到频道。\r\n\r\n 稿件消息直达链接：<a href='{url}'>{title}</a>",
                'description' => '白名单用户投稿提交提示语',
                'created_at' => '2023-11-23 21:13:00',
                'updated_at' => '2023-11-23 21:13:00',
            ],
            [
                'id' => 13,
                'group' => 'submission',
                'name' => 'black_list',
                'value' => '您已经在黑名单中，无法进行投稿操作。',
                'description' => '确认黑名单用户投稿提示语',
                'created_at' => '2023-11-23 21:13:00',
                'updated_at' => '2023-11-23 21:13:00',
            ],
            [
                'id' => 14,
                'group' => 'submission',
                'name' => 'review_delete_submission',
                'value' => '投稿已被管理员删除。您可以重新开始投稿。',
                'description' => '审核群组稿件删除提示语',
                'created_at' => '2023-11-23 21:13:00',
                'updated_at' => '2023-11-23 21:13:00',
            ],
            [
                'id' => 15,
                'group' => 'submission',
                'name' => 'preview_tips_channel',
                'value' => "已生成预览。\r\n请确认您的投稿信息。\r\n点击下方的 “选择发布频道” 按钮，选择将投稿发送到哪个频道。\r\n点击下方的 “重新编辑” 按钮，将返回到编辑状态。",
                'description' => '预览投稿并选择频道提示语',
                'created_at' => '2023-09-17 08:48:16',
                'updated_at' => '2023-09-17 08:48:41',
            ],
            [
                'id' => 16,
                'group' => 'submission',
                'name' => 'select_channel',
                'value' => '请选择您需要发布的频道',
                'description' => '选择频道提示语',
                'created_at' => '2023-09-17 08:48:16',
                'updated_at' => '2023-09-17 08:48:41',
            ],
            [
                'id' => 17,
                'group' => 'submission',
                'name' => 'select_channel_end',
                'value' => '您已选择频道，请点击下方的 “确认投稿” 按钮，或者点击下方的 “重新选择频道” 按钮。',
                'description' => '选择频道后的提示语',
                'created_at' => '2023-09-17 08:48:16',
                'updated_at' => '2023-09-17 08:48:41',
            ],
            [
                'id' => 18,
                'group' => 'help',
                'name' => 'start',
                'value' => '这里是帮助中心',
                'description' => '帮助中心内容',
                'created_at' => '2023-09-17 08:48:16',
                'updated_at' => '2023-09-17 08:48:41',
            ],
            [
                'id' => 19,
                'group' => 'feedback',
                'name' => 'start',
                'value' => "这里是意见反馈\r\n请选择您是要投诉还是建议反馈",
                'description' => '意见反馈开始提示语',
                'created_at' => '2023-09-17 08:48:16',
                'updated_at' => '2023-09-17 08:48:41',
            ],
            [
                'id' => 20,
                'group' => 'complaint',
                'name' => 'start',
                'value' => "请向这里转发您想要投诉的频道消息\r\n请注意，您必须转发您要投诉的频道消息过来，其他方式无效。",
                'description' => '开始投诉提示语',
                'created_at' => '2023-09-17 08:48:16',
                'updated_at' => '2023-09-17 08:48:41',
            ],

            [
                'id' => 21,
                'group' => 'complaint',
                'name' => 'start_text_tips',
                'value' => "已记录到草稿中。\r\n您可以继续发送新的内容来替换上一次的内容。\r\n当您觉得投诉内容合适时，点击下方的 “结束发送”。\r\n投诉草稿有效期为1天",
                'description' => '开始投诉输入内容的提示语',
                'created_at' => '2023-09-17 08:43:16',
                'updated_at' => '2023-09-17 08:43:48',
            ],
            [
                'id' => 22,
                'group' => 'complaint',
                'name' => 'start_update_text_tips',
                'value' => "已更新当前投诉内容。\r\n您可以继续发送新的内容来替换上一次的内容。\r\n当您觉得投诉内容合适时，点击下方的 “结束发送”。\r\n投诉草稿有效期为1天",
                'description' => '开始投诉更新输入内容的提示语',
                'created_at' => '2023-09-17 08:45:06',
                'updated_at' => '2023-09-17 08:45:30',
            ],
            [
                'id' => 23,
                'group' => 'complaint',
                'name' => 'start_empty_forward_origin',
                'value' => '无效的消息，请注意开启引用，不要复制消息。',
                'description' => '投诉收到的消息没有来源时的提示语',
                'created_at' => '2023-09-17 08:45:06',
                'updated_at' => '2023-09-17 08:45:30',
            ],
            [
                'id' => 24,
                'group' => 'complaint',
                'name' => 'start_forward_origin',
                'value' => '已收到您想投诉的频道消息，接下来，请发送您的投诉理由。投诉成功后，该频道消息将被删除。',
                'description' => '收到投诉的频道消息',
                'created_at' => '2023-09-17 08:45:06',
                'updated_at' => '2023-09-17 08:45:30',
            ],
            [
                'id' => 25,
                'group' => 'complaint',
                'name' => 'restart',
                'value' => "已清空\r\n请重新发送内容。",
                'description' => '重新开始投诉提示语',
                'created_at' => '2023-09-17 08:37:09',
                'updated_at' => '2023-09-17 08:37:21',
            ],
            [
                'id' => 26,
                'group' => 'complaint',
                'name' => 'cancel',
                'value' => '已取消投诉',
                'description' => '取消投诉提示语',
                'created_at' => '2023-09-17 08:39:01',
                'updated_at' => '2023-09-17 08:39:01',
            ],
            [
                'id' => 27,
                'group' => 'complaint',
                'name' => 'is_empty',
                'value' => '您还没有输入任何内容，请重新输入！',
                'description' => '投诉内容为空提示语',
                'created_at' => '2023-11-23 21:13:00',
                'updated_at' => '2023-11-23 21:13:00',
            ],
            [
                'id' => 28,
                'group' => 'complaint',
                'name' => 'preview_tips',
                'value' => "已生成预览。\r\n请确认您的投诉信息。",
                'description' => '预览投诉提示语',
                'created_at' => '2023-09-17 08:48:16',
                'updated_at' => '2023-09-17 08:48:41',
            ],
            [
                'id' => 29,
                'group' => 'complaint',
                'name' => 'custom_header_content',
                'value' => "⚠️‼️用户投诉请求\r\n\r\n",
                'description' => '投诉消息顶部文本',
                'created_at' => '2023-09-17 08:48:16',
                'updated_at' => '2023-09-17 08:48:41',
            ],
            [
                'id' => 30,
                'group' => 'complaint',
                'name' => 'custom_tail_content',
                'value' => "\r\n\r\n投诉的消息: {url}\r\n若该投诉被批准，则频道内对应的消息将被自动删除。",
                'description' => '投诉消息底部文本',
                'created_at' => '2023-09-17 08:48:16',
                'updated_at' => '2023-09-17 08:48:41',
            ],
            [
                'id' => 31,
                'group' => 'complaint',
                'name' => 'confirm_end',
                'value' => "✅ 投诉成功，稍后将通过机器人告知您审核结果，请保持联系畅通\r\n审核可能需要一定时间，如果您长时间未收到结果，可联系群内管理员。",
                'description' => '投诉提交提示语',
                'created_at' => '2023-09-17 08:48:16',
                'updated_at' => '2023-09-17 08:48:41',
            ],
            [
                'id' => 32,
                'group' => 'command',
                'name' => 'start',
                'value' => '您可以使用底部的操作键盘快速交互，或者发送 /help 命令查看详细的功能介绍',
                'description' => '/start 命令的回复',
                'created_at' => '2023-09-17 08:48:16',
                'updated_at' => '2023-09-17 08:48:41',
            ],
            [
                'id' => 33,
                'group' => 'command',
                'name' => 'help',
                'value' => '您可以使用底部的操作键盘快速交互，或者发送 /help 命令查看详细的功能介绍',
                'description' => '/help 命令的回复',
                'created_at' => '2023-09-17 08:48:16',
                'updated_at' => '2023-09-17 08:48:41',
            ],
            [
                'id' => 34,
                'group' => 'submission',
                'name' => 'not_open',
                'value' => '投稿服务已关闭，如需开启请联系管理员。',
                'description' => '投稿服务被停止的提示语',
                'created_at' => '2023-09-17 08:48:16',
                'updated_at' => '2023-09-17 08:48:41',
            ],
            [
                'id' => 35,
                'group' => 'complaint',
                'name' => 'not_open',
                'value' => '投诉服务已关闭，如需开启请联系管理员。',
                'description' => '投诉服务被停止的提示语',
                'created_at' => '2023-09-17 08:48:16',
                'updated_at' => '2023-09-17 08:48:41',
            ],
            [
                'id' => 36,
                'group' => 'submission',
                'name' => 'expired',
                'value' => '您的稿件草稿已过期，您可以重新投稿，点击下方键盘即可',
                'description' => '稿件草稿过期的提示语',
                'created_at' => '2023-09-17 08:48:16',
                'updated_at' => '2023-09-17 08:48:41',
            ],
            [
                'id' => 36,
                'group' => 'submission',
                'name' => 'expired_in_one_hour',
                'value' => '您的稿件草稿将在一小时后过期，如果您不需要继续编辑，请点击下方键盘提交稿件，或者取消稿件。',
                'description' => '稿件草稿即将一小时后过期的提示语',
                'created_at' => '2023-09-17 08:48:16',
                'updated_at' => '2023-09-17 08:48:41',
            ],
            [
                'id' => 37,
                'group' => 'submission',
                'name' => 'channel_keywords',
                'value' => "\r\n\r\n关键词：",
                'description' => '稿件发布到频道到消息中，关键词显示的内容。例：关键词：#新闻 #娱乐',
                'created_at' => '2023-09-17 08:48:16',
                'updated_at' => '2023-09-17 08:48:41',
            ],
            [
                'id' => 38,
                'group' => 'submission',
                'name' => 'channel_anonymous',
                'value' => "\r\n\r\n匿名投稿",
                'description' => '稿件发布到频道到消息中，匿名投稿显示的内容。例：匿名投稿',
                'created_at' => '2023-09-17 08:48:16',
                'updated_at' => '2023-09-17 08:48:41',
            ],
            [
                'id' => 39,
                'group' => 'submission',
                'name' => 'channel_anonymous_no',
                'value' => "\r\n\r\n投稿人：{posted_by}",
                'description' => '稿件发布到频道到消息中，匿名投稿显示的内容。例：投稿人：taotecode',
                'created_at' => '2023-09-17 08:48:16',
                'updated_at' => '2023-09-17 08:48:41',
            ],
            [
                'id' => 40,
                'group' => 'submission',
                'name' => 'select_forward_origin_is_tip',
                'value' => "请选择是否在稿件中标注您投稿消息中的来源。",
                'description' => '当投稿消息中存在来源时，用户主动选择是否标注来源的提示语，例：请选择是否在稿件中标注您投稿消息中的来源。',
                'created_at' => '2023-09-17 08:48:16',
                'updated_at' => '2023-09-17 08:48:41',
            ],
            [
                'id' => 41,
                'group' => 'submission',
                'name' => 'select_forward_origin_yes_tip',
                'value' => "您已确定在当前稿件中标注转发来源。您可以检查稿件，然后再次点击【结束发送】",
                'description' => '当投稿消息中存在来源时，用户主动选择了【是】的提示语，例：您已确定在当前稿件中标注转发来源。您可以检查稿件，然后再次点击【结束发送】',
                'created_at' => '2023-09-17 08:48:16',
                'updated_at' => '2023-09-17 08:48:41',
            ],
            [
                'id' => 42,
                'group' => 'submission',
                'name' => 'select_forward_origin_no_tip',
                'value' => "您已确定在当前稿件中不标注转发来源。您可以检查稿件，然后再次点击【结束发送】",
                'description' => '当投稿消息中存在来源时，用户主动选择了【否】的提示语，例：您已确定在当前稿件中不标注转发来源。您可以检查稿件，然后再次点击【结束发送】',
                'created_at' => '2023-09-17 08:48:16',
                'updated_at' => '2023-09-17 08:48:41',
            ],
            [
                'id' => 43,
                'group' => 'submission',
                'name' => 'select_forward_origin_input_tip',
                'value' => "您当前投稿的消息没有消息来源标志，您可以回复本条消息来标注您的稿件来源。\r\n比如回复内容：<a href='https://www.google.com'>新闻网</a>\r\n您可以选择不携带链接，您也可以选择携带链接，但请注意，链接必须是有效的，否则将无法标注来源。",
                'description' => "当投稿消息中不存在来源时，需要用户主动输入消息来源的提示语，例：您当前投稿的消息没有消息来源标志，您可以回复本条消息来标注您的稿件来源。\r\n比如回复内容：<a href='https://www.google.com'>新闻网</a>\r\n您可以选择不携带链接，您也可以选择携带链接，但请注意，链接必须是有效的，否则将无法标注来源。",
                'created_at' => '2023-09-17 08:48:16',
                'updated_at' => '2023-09-17 08:48:41',
            ],
            [
                'id' => 44,
                'group' => 'submission',
                'name' => 'select_forward_origin_input_c_tip',
                'value' => "如果您不想输入，您可以点击【取消输入来源】来进行下一步投稿操作",
                'description' => "当投稿消息中不存在来源时，用户主动输入消息来源时，给予用户取消的操作的提示语，例：您已取消输入标注消息来源，您可以点击【结束发送】来进行投稿的下一步操作。",
                'created_at' => '2023-09-17 08:48:16',
                'updated_at' => '2023-09-17 08:48:41',
            ],
            [
                'id' => 45,
                'group' => 'submission',
                'name' => 'select_forward_origin_input_cancel_tip',
                'value' => "您已取消输入标注消息来源，您可以点击【结束发送】来进行投稿的下一步操作。",
                'description' => "当投稿消息中不存在来源时，用户取消主动输入消息来源的提示语，例：您已取消输入标注消息来源，您可以点击【结束发送】来进行投稿的下一步操作。",
                'created_at' => '2023-09-17 08:48:16',
                'updated_at' => '2023-09-17 08:48:41',
            ],
            [
                'id' => 46,
                'group' => 'submission',
                'name' => 'select_forward_origin_input_confirm_tip',
                'value' => "已记录您输入的消息来源，您可以继续回复消息来更新来源，或者点击下方键盘进行投稿的下一步操作\r\n来源：{data}",
                'description' => "当投稿消息中不存在来源时，用户主动输入消息来源后机器人进行回复的提示语，例：已记录您输入的消息来源，您可以继续回复消息来更新来源，或者点击下方键盘进行投稿的下一步操作\r\n来源：{data}",
                'created_at' => '2023-09-17 08:48:16',
                'updated_at' => '2023-09-17 08:48:41',
            ],
            [
                'id' => 47,
                'group' => 'submission',
                'name' => 'select_forward_origin_input_media_tip',
                'value' => "输入标注消息来源不支持媒体消息！",
                'description' => "当用户主动输入消息来源后，非文本或链接的提示语",
                'created_at' => '2023-09-17 08:48:16',
                'updated_at' => '2023-09-17 08:48:41',
            ],
            [
                'id' => 48,
                'group' => 'submission',
                'name' => 'select_forward_origin_input_id_error_tip',
                'value' => "请回复指定消息来完成输入来源操作！",
                'description' => "当用户主动输入消息来源后，并未回复指定消息或未指定消息进行回复的提示语",
                'created_at' => '2023-09-17 08:48:16',
                'updated_at' => '2023-09-17 08:48:41',
            ],
            [
                'id' => 49,
                'group' => 'submission',
                'name' => 'forward_origin_text_link',
                'value' => "\r\n\r\n <b>来源频道：</b><a href='{link}'>{name}</a>",
                'description' => "稿件发布到频道到消息中，消息来源显示的内容（包含链接）。例：<b>来源频道：</b><a href='{link}'>{name}</a>",
                'created_at' => '2023-09-17 08:48:16',
                'updated_at' => '2023-09-17 08:48:41',
            ],
            [
                'id' => 50,
                'group' => 'submission',
                'name' => 'forward_origin_text',
                'value' => "\r\n\r\n <b>来源频道：</b>{name}",
                'description' => "稿件发布到频道到消息中，消息来源显示的内容（不包含链接）。例：<b>来源频道：</b>{name}",
                'created_at' => '2023-09-17 08:48:16',
                'updated_at' => '2023-09-17 08:48:41',
            ],
            [
                'id' => 51,
                'group' => 'submission',
                'name' => 'disable_message_preview_select_tip',
                'value' => "请问您是否要将消息内的链接开启链接预览?",
                'description' => "当后台设置消息预览功能为用户自主选择时，向用户发送选择键盘的提示语。例：请问您是否要将消息内的链接开启链接预览?",
                'created_at' => '2023-09-17 08:48:16',
                'updated_at' => '2023-09-17 08:48:41',
            ],
            [
                'id' => 52,
                'group' => 'submission',
                'name' => 'disable_message_preview_end_tip',
                'value' => "您已完成选择，请继续下一步操作",
                'description' => "当后台设置消息预览功能为用户自主选择时，用户选择完的提示语。例：您已完成选择，请继续下一步操作",
                'created_at' => '2023-09-17 08:48:16',
                'updated_at' => '2023-09-17 08:48:41',
            ],
            [
                'id' => 53,
                'group' => 'submission',
                'name' => 'disable_notification_select_tip',
                'value' => "请问您是否要将消息使用静默发送?",
                'description' => "当后台设置消息静默发送功能为用户自主选择时，向用户发送选择键盘的提示语。例：请问您是否要将消息使用静默发送?",
                'created_at' => '2023-09-17 08:48:16',
                'updated_at' => '2023-09-17 08:48:41',
            ],
            [
                'id' => 54,
                'group' => 'submission',
                'name' => 'disable_notification_end_tip',
                'value' => "您已完成选择，请继续下一步操作",
                'description' => "当后台设置消息静默发送功能为用户自主选择时，用户选择完的提示语。例：您已完成选择，请继续下一步操作",
                'created_at' => '2023-09-17 08:48:16',
                'updated_at' => '2023-09-17 08:48:41',
            ],
            [
                'id' => 55,
                'group' => 'submission',
                'name' => 'protect_content_select_tip',
                'value' => "请问您是否要禁止消息被转发或保存?",
                'description' => "当后台设置禁止消息被转发或保存功能为用户自主选择时，向用户发送选择键盘的提示语。例：请问您是否要禁止消息被转发或保存?",
                'created_at' => '2023-09-17 08:48:16',
                'updated_at' => '2023-09-17 08:48:41',
            ],
            [
                'id' => 56,
                'group' => 'submission',
                'name' => 'protect_content_end_tip',
                'value' => "您已完成选择，请继续下一步操作",
                'description' => "当后台设置禁止消息被转发或保存功能为用户自主选择时，用户选择完的提示语。例：您已完成选择，请继续下一步操作",
                'created_at' => '2023-09-17 08:48:16',
                'updated_at' => '2023-09-17 08:48:41',
            ],
        ];
        //        DB::table('config')->insert($data);
        foreach ($data as $item) {
            if (config('app.env') === 'local') {
                (new Config())->updateOrCreate(['id' => $item['id']], $item);
            } else {
                (new Config())->firstOrCreate(['id' => $item['id']], $item);
            }
        }
    }
}

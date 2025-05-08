<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\Bot;
use App\Admin\Repositories\BotCommand;
use App\Enums\Commands;
use Dcat\Admin\Form;
use Dcat\Admin\Form\NestedForm;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class BotCommandController extends AdminController
{

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid(): Grid
    {
        return Grid::make(new BotCommand('bot'), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('bot.appellation');
            $grid->column('command');
            $grid->column('scope')->display(function ($scope) {
                return Commands::SCOPES[$scope];
            });
            $grid->column('description');
            $grid->column('created_at');
            $grid->column('updated_at')->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');

            });
        });
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     *
     * @return Show
     */
    protected function detail($id)
    {
        return Show::make($id, new BotCommand('bot'), function (Show $show) {
            $show->field('id');
            $show->field('bot.appellation');
            $show->field('command');
            $show->field('description');
            $show->field('scope');
            $show->field('created_at');
            $show->field('updated_at');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form(): Form
    {
        return Form::make(new BotCommand(), function (Form $form) {
            $commands = Commands::OPTIONS;


            $form->display('id');
            $form->select('bot_id')->options((new Bot())->getSelectOptions())->default(1)->required()->help('所属机器人ID，这将会将这个命令绑定到这个机器人下。');
            $form->select('command')->options($commands)
                ->when('start', function (Form $form) {
                    $form->textarea('data.text', '命令文本内容')
                        ->default("您可以使用底部的操作键盘快速交互，或者发送 /help 命令查看详细的功能介绍")
                        ->help("仅可更改命令的文本显示内容<br>支持html格式(参考<a href='https://core.telegram.org/bots/api#html-style' target='_blank'>https://core.telegram.org/bots/api#html-style</a>)。");
                    $form->embeds('data.button', '消息尾部按钮组内容', function ($form) {
                        $form->text('tips', '说明')->disable()->placeholder('说明')->help('可以通过第几行第几个来改变按钮名称的顺序，比如第一行第一个设置的是【个人设置】，第二个是【我的投稿】，那么实际显示也会按这个来');
                        $options = Commands::COMMAND_START_BUTTON;
                        $form->select('key11', '按钮方法')->options($options)->default('start_submission')->help('(第一行第一个)');
                        $form->text('text11', '按钮文字')->default('开始投稿')->help('(第一行第一个)');

                        $form->select('key12', '按钮方法')->options($options)->default('start_complaint')->help('(第一行第二个)');
                        $form->text('text12', '按钮文字')->default('意见反馈')->help('(第一行第二个)');

                        $form->select('key21', '按钮方法')->options($options)->default('my_submission')->help('(第二行第一个)');
                        $form->text('text21', '按钮文字')->default('我的投稿')->help('(第二行第一个)');

                        $form->select('key22', '按钮方法')->options($options)->default('my_setting')->help('(第二行第二个)');
                        $form->text('text22', '按钮文字')->default('个人设置')->help('(第二行第二个)');
                    })->saving(function ($v) {
                        // 转化为json格式存储
                        return json_encode($v);
                    });
                })
                ->when('help', function (Form $form) {
                    $form->textarea('data.text', '命令文本内容')
                        ->default("这里是帮助中心\r您可以使用底部的操作键盘快速交互，或者发送 /help 命令查看详细的功能介绍")
                        ->help("仅可更改命令的文本显示内容<br>支持html格式(参考<a href='https://core.telegram.org/bots/api#html-style' target='_blank'>https://core.telegram.org/bots/api#html-style</a>)。");
                })
                ->when('get_me_id', function (Form $form) {
                    $form->textarea('data.text', '命令文本内容')
                        ->default("您的ID：<pre>{chatId}</pre>")
                        ->help("
命令支持变量：<br>
<pre>{chatID}</pre>
【{chatID}】用户的chat_id<br>
仅可更改命令的文本显示内容<br>
支持html格式(参考<a href='https://core.telegram.org/bots/api#html-style' target='_blank'>https://core.telegram.org/bots/api#html-style</a>)。
");
                })
                ->when('get_group_id', function (Form $form) {
                    $form->textarea('data.text', '命令文本内容')
                        ->default("群组名称：<pre>{chatTitle}</pre> \r 群组ID ：<pre>{chatId}</pre> \r 机器人是否是管理员：{isAdmin}")
                        ->help("
命令支持变量：<br>
<pre>{chatTitle}</pre>【{chatTitle}】群组名称<br>
<pre>{chatId}</pre>【{chatId}】群组ID<br>
<pre>{isAdmin}</pre>【{isAdmin}】机器人是否是管理员<br>
仅可更改命令的文本显示内容<br>
支持html格式(参考<a href='https://core.telegram.org/bots/api#html-style' target='_blank'>https://core.telegram.org/bots/api#html-style</a>)。
");
                })
                ->when('list', function (Form $form) {
                    $form->number('data.number', '单页列表展示数量')
                        ->default(10)
                        ->help("为了防止机器人无法发送更多信息，请尽量设置在15个以内");
                })
                ->required()->default('start')->help('命令名称');
            $form->select('scope')->options(Commands::SCOPES)->when('chat', function (Form $form) {
                $form->text('data.chat_id')->help('如果不知道chat_id，可以先给需要的对话使用<pre>/get_chat_id</pre>命令并获取chat_id<b>（前提是机器人已添加这个命令）</b>。');
            })->required();
            $form->text('description');

            $form->display('created_at');
            $form->display('updated_at');

            $form->saving(function (Form $form) {
                if ($form->input('command')==='start'){
                }
            });
        });
    }
}

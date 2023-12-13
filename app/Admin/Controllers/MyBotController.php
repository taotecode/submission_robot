<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Form\LexiconCheck;
use App\Admin\Repositories\Bot;
use App\Admin\RowActions\MyBot\SetCommands;
use App\Admin\RowActions\MyBot\SetWebHook;
use App\Models\Channel;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Show;
use Illuminate\Support\Facades\Storage;

class MyBotController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {

        return Grid::make(new Bot(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('appellation');
            $grid->column('name')->display(function ($name) {
                return '@'.$name;
            })->copyable();
            $grid->column('review_num');
            $grid->column('status')->switch();
            $grid->column('created_at');
            $grid->column('updated_at')->sortable();

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                // append一个操作
                $actions->append(new SetWebHook());
                $actions->append(new SetCommands());
            });

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');

            });
        });
    }

    /**
     * Make a show builder.
     *
     * @param  mixed  $id
     * @return Show
     */
    protected function detail($id)
    {
        return Show::make($id, new Bot(), function (Show $show) {
            $show->field('id');
            $show->field('appellation');
            $show->field('name');
            $show->field('token');
            $show->field('created_at');
            $show->field('updated_at');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new Bot(), function (Form $form) {
            $form->display('id');
            $form->text('appellation')->help('机器人的用户名');
            $form->text('name')->help('不需要携带@符号，如：tougao_bot')->required();
            $form->password('token')->help('通过@BotFather创建机器人获取')->required();
            $form->number('review_num')->min(1)->max(30)->default(1)->required()
                ->help('每条投稿消息的审核数量。如：设置1，那么只需要一个人就可以通过或拒绝。设置2，那么就需要两个人就可以通过或拒绝。<br>最小值为：1');
            $form->textarea('tail_content')->help("每条投稿消息的尾部内容，支持html格式(参考<a href='https://core.telegram.org/bots/api#html-style' target='_blank'>https://core.telegram.org/bots/api#html-style</a>)。");

            $form->checkbox('channel_ids', '发布频道')
                ->options(Channel::all()->pluck('appellation', 'id'))
                ->help('选择需要发布的频道，可以多选。');

            $form->switch('status')->default(1);

            $form->radio('is_auto_keyword')
                ->when('1', function (Form $form) {
                    $form->textarea('keyword')->default('新闻')->help('每行一个关键词<br>当稿件文本经过词库分词后的词语中，含有关键词，则会在消息尾部加入如：#新闻 #的标签');
                    $form->textarea('lexicon')->default('新闻')->help('
                    词库格式为每行一个词，如果需要提升分词准确率，可以在词语后面加上词性，词性之间用空格隔开，词性列表如下：<br>
        新闻 1<br>
        一般数值在1-10之间，数值越大，分词越准确，但是分词速度越慢，建议平均值为：3<br>
        可以点击右上角【词库验证】进行分词测试<br>
                    ');
                })
                ->options([
                    '0' => '关闭自动关键词',
                    '1' => '开启自动关键词',
                ])
                ->default('1');

            $form->display('created_at');
            $form->display('updated_at');

            $form->saved(function (Form $form, $result) {
                // 判断是否是新增操作
                if ($form->isCreating()) {
                    //自增ID
                    $newId = $form->getKey();
                } else {
                    $newId = $form->model()->id;
                }
                // 保存词库
                $lexicon = $form->input('lexicon'); //Lexicon
                Storage::put("public/lexicon_{$newId}.txt", $lexicon);
            });

            $form->tools(function (Form\Tools $tools) {
                $tools->append(new LexiconCheck());
            });
        });
    }
}

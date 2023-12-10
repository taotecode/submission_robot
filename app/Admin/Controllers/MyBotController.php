<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\Bot;
use App\Admin\RowActions\MyBot\SetCommands;
use App\Admin\RowActions\MyBot\SetWebHook;
use App\Models\Channel;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Show;

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

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}

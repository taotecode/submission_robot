<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\Bot;
use App\Admin\RowActions\MyBot\DelWebHook;
use App\Admin\RowActions\MyBot\SetChannel;
use App\Admin\RowActions\MyBot\SetCommands;
use App\Admin\RowActions\MyBot\SetIsAutoKeyword;
use App\Admin\RowActions\MyBot\SetTailContent;
use App\Admin\RowActions\MyBot\SetWebHook;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Http\JsonResponse;
use Dcat\Admin\Layout\Content;
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
            $grid->column('review_approved_num')->badge();
            $grid->column('review_reject_num')->badge();
            $grid->column('status')->switch();
            $grid->column('created_at');
            $grid->column('updated_at')->sortable();

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                // append一个操作
                if ($actions->row->webhook_status < 1) {
                    $actions->append(new SetWebHook());
                } else {
                    $actions->append(new DelWebHook());
                }
                $actions->append(new SetCommands());
                $actions->append(new SetTailContent());
                $actions->append(new SetIsAutoKeyword());
                $actions->append(new SetChannel());
            });

            $grid->filter(function (Grid\Filter $filter) {
                $filter->panel();
                $filter->equal('id');
                $filter->like('appellation');
                $filter->like('name');
                $filter->equal('status')->select([0 => '禁用', 1 => '启用']);
                $filter->between('created_at')->datetime();
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
            $form->text('appellation')->help('机器人的用户名名称，如：我的测试机器人')->required();
            $form->text('name')->help('不需要携带@符号，如：tougao_bot')->required();
            $form->password('token')->help('通过@BotFather创建机器人获取')->required();
            $form->number('review_approved_num')->min(1)->max(30)->default(1)->required()
                ->help('每条投稿消息的审核数量。如：设置1，那么只需要一个人就可以通过或拒绝。设置2，那么就需要两个人就可以通过或拒绝。<br>最小值为：1');
            $form->number('review_reject_num')->min(1)->max(30)->default(1)->required()
                ->help('每条投稿消息的审核数量。如：设置1，那么只需要一个人就可以通过或拒绝。设置2，那么就需要两个人就可以通过或拒绝。<br>最小值为：1');

            $form->switch('is_message_text_preprocessing')->default(1)->help('是否开启消息文本预处理？<br>开启后，将会对消息文本格式进行保留，如：空格、换行、链接、加粗等。');
            $radio_options=[1 => '开启', 0 => '关闭',2=>'用户在投稿时主动选择'];
            $form->radio('is_link_preview')->default(1)->options($radio_options)->help('是否开启消息预览？<br>开启后，将会对消息文件、链接、图片进行预览。');
            $form->radio('is_disable_notification')->default(1)->options($radio_options)->help('是否开启消息静默发送？<br>开启后，用户在频道中，将不会收到消息提醒。');
            $form->radio('is_protect_content')->default(1)->options($radio_options)->help('是否开启消息禁止被转发和保存？<br>开启后，将会对消息进行禁止转发和保存。');

            $form->radio('is_forward_origin')
                ->when(1, function (Form $form) {
                    $form->switch('is_forward_origin_select')->default(1)
                        ->help('
是否开启消息来源用户主动选择是否标注？<br>
开启后，用户投稿的消息如果带有来源频道消息，将会在确认投稿之前让用户选择是否标注来源信息。<br>
关闭后，用户投稿的消息如果带有来源频道消息，将会自动标注来源信息。
');
                    $form->switch('is_forward_origin_input')->default(1)
                        ->help('
是否开启用户主动输入消息来源进行标注？<br>
开启后，用户投稿的消息如果没有带有来源频道消息，将会在确认投稿之前让用户主动输入来源信息。<br>
关闭后，用户投稿的消息如果没有带有来源频道消息，将不会显示来源信息。
');
                })
                ->options([
                    1 => '开启',
                    0 => '关闭',
                ])
                ->default(1)
                ->help('是否开启消息来源自动标注？<br>开启后，用户投稿的消息如果带有来源频道消息或没有来源消息，将会自动或手动标注来源频道以及对应的消息链接。');

            $form->switch('is_submission')->default(1)->help('是否开启投稿服务？');
            $form->switch('is_complaint')->default(1)->help('是否开启投诉服务？');
            $form->switch('is_suggestion')->default(1)->help('是否开启建议服务？');

            $form->switch('status')->default(1);
            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}

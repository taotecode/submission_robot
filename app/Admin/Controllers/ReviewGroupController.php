<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\ReviewGroup;
use App\Admin\RowActions\ReviewGroup\Auditor;
use App\Models\Bot as BotModel;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Show;

class ReviewGroupController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new ReviewGroup('bot'), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('appellation');
            $grid->column('bot.appellation')->label();
            $grid->column('group_id')->copyable();
            $grid->column('name')->display(function ($name) {
                if (! empty($name)) {
                    return '@'.$name;
                }

                return 'NULL';
            })->copyable();
            $grid->column('created_at');
            $grid->column('updated_at')->sortable();

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                // append一个操作
                $actions->append(new Auditor());
            });

            $grid->filter(function (Grid\Filter $filter) {
                $filter->panel();
                $filter->equal('id');
                $filter->like('appellation');
                $filter->equal('bot_id')->select(BotModel::all()->pluck('appellation', 'id'));
                $filter->like('group_id');
                $filter->like('name');
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
        return Show::make($id, new ReviewGroup(), function (Show $show) {
            $show->field('id');
            $show->field('group_id');
            $show->field('name');
            $show->field('appellation');
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
        $repository = new ReviewGroup();
        $botAll = BotModel::all()->pluck('appellation', 'id')->toArray();
        $groupAll = $repository->model()->all();

        return Form::make($repository, function (Form $form) use ($botAll, $groupAll) {
            $form->display('id');

            //仅显示未被其他审核群组绑定的机器人
            foreach ($groupAll as $key => $value) {
                if (isset($botAll[$value->bot_id])) {
                    if ($form->isEditing() && $value->id === $form->model()->id) {
                        continue;
                    }
                    unset($botAll[$value->bot_id]);
                }
            }

            $form->select('bot_id')->options($botAll)->required()->help('一个群组只能关联一个机器人，一个机器人也只能关联一个群组');
            $form->text('group_id')->required()->help(
                '可将机器人拉入到制定审核群组，然后发送命令获取：<pre>/get_group_id</pre>（<b>前提是机器人已部署好,web hook也设置好</b>）'
            );
            $form->text('name')->help('非必填，如果群组有公开链接，如：https://t.cn/this_a_group，那么就可以填：this_a_group');
            $form->text('appellation');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}

<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\Bot;
use App\Admin\Repositories\SubmissionUser;
use App\Enums\SubmissionUserType;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Show;

class SubmissionUserController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new SubmissionUser(), function (Grid $grid) {

            $grid->model()->orderBy('id', 'desc');

            $grid->column('id')->sortable();
            $grid->column('bot_id');
            $grid->column('type')->display(function ($type) {
                return SubmissionUserType::MAP[$type];
            })->label();
            $grid->column('userId')->copyable();
            $grid->column('name');
            $grid->column('created_at');
            $grid->column('updated_at')->sortable();

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                // append一个操作
                $actions->append(new \App\Admin\RowActions\SubmissionUser\AddAuditorUser());
            });

            $grid->filter(function (Grid\Filter $filter) {
                $filter->panel();
                $filter->equal('id');
                $filter->equal('bot_id')->select((new Bot())->getSelectOptions());
                $filter->equal('type')->select(SubmissionUserType::MAP);
                $filter->equal('userId');
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
        return Show::make($id, new SubmissionUser(), function (Show $show) {
            $show->field('id');
            $show->field('type');
            $show->field('userId');
            $show->field('name');
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
        return Form::make(new SubmissionUser(), function (Form $form) {
            $form->display('id');
            $form->select('bot_id')->options((new Bot())->getSelectOptions())->required()->help('所属机器人ID，这将会将这个用户绑定到这个机器人下。');
            $form->radio('type')->options(SubmissionUserType::MAP)->default(0)->required()->help('普通：正常进入投稿审核流程，白名单：无视投稿审核，直接发布，黑名单：在黑名单中的用户不能提交');
            $form->text('userId')->required()->help('投稿人TG ID');
            $form->text('name')->help('投稿人昵称');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}

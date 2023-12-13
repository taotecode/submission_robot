<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\SubmissionUser;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

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
            $grid->column('id')->sortable();
            $grid->column('type')->display(function ($type) {
                $textArray = ['普通', '白名单', '黑名单'];
                return $textArray[$type];
            })->label();
            $grid->column('userId')->copyable();
            $grid->column('name');
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
            $form->radio('type')->options(['0' => '普通', '1' => '白名单','2'=>'黑名单'])->default('0')->required()->help('普通：正常进入投稿审核流程，白名单：无视投稿审核，直接发布，黑名单：在黑名单中的用户不能提交');
            $form->text('userId')->required()->help('投稿人TG ID');
            $form->text('name')->help('投稿人昵称');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}

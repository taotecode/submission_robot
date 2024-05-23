<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\Complaint;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Show;

class ComplaintController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Complaint(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('bot_id');
            $grid->column('channel_id');
            $grid->column('message_id');
            $grid->column('type');
            $grid->column('text');
            $grid->column('posted_by');
            $grid->column('posted_by_id');
            $grid->column('data');
            $grid->column('approved');
            $grid->column('reject');
            $grid->column('one_approved');
            $grid->column('one_reject');
            $grid->column('status');
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
     * @param  mixed  $id
     * @return Show
     */
    protected function detail($id)
    {
        return Show::make($id, new Complaint(), function (Show $show) {
            $show->field('id');
            $show->field('bot_id');
            $show->field('channel_id');
            $show->field('message_id');
            $show->field('type');
            $show->field('text');
            $show->field('posted_by');
            $show->field('posted_by_id');
            $show->field('data');
            $show->field('approved');
            $show->field('reject');
            $show->field('one_approved');
            $show->field('one_reject');
            $show->field('status');
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
        return Form::make(new Complaint(), function (Form $form) {
            $form->display('id');
            $form->text('bot_id');
            $form->text('channel_id');
            $form->text('message_id');
            $form->text('type');
            $form->text('text');
            $form->text('posted_by');
            $form->text('posted_by_id');
            $form->text('data');
            $form->text('approved');
            $form->text('reject');
            $form->text('one_approved');
            $form->text('one_reject');
            $form->text('status');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}

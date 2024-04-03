<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\BotUser;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class BotUserController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new BotUser(), function (Grid $grid) {

            // 禁用
            $grid->disableCreateButton();

            $grid->model()->orderBy('id', 'desc');

            $grid->column('id')->sortable();
            $grid->column('bot_id');
            $grid->column('userId');
            $grid->column('user_data');
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
        return Show::make($id, new BotUser(), function (Show $show) {
            $show->field('id');
            $show->field('bot_id');
            $show->field('userId');
            $show->field('user_data');
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
        return Form::make(new BotUser(), function (Form $form) {
            $form->display('id');
            $form->text('bot_id');
            $form->text('userId');
            $form->text('user_data');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}

<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\Channel;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Show;

class ChannelController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Channel(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('name')->display(function ($name) {
                if (! empty($name)) {
                    return '@'.$name;
                }

                return 'NULL';
            })->copyable();
            $grid->column('appellation');
            $grid->column('created_at');
            $grid->column('updated_at')->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
                $filter->like('name');
                $filter->like('appellation');
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
        return Show::make($id, new Channel(), function (Show $show) {
            $show->field('id');
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
        return Form::make(new Channel(), function (Form $form) {
            $form->display('id');
            $form->text('name')->help('频道公开链接，如：https://t.cn/this_a_channel，那么就可以填：this_a_channel，注意不要带@');
            $form->text('appellation')->help('频道名称，如：这是一个频道');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}

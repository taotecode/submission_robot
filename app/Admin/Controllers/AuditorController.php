<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\Auditor;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Show;

class AuditorController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $repository = new Auditor();

        $role_options = $repository->model()::ROLE;

        return Grid::make($repository, function (Grid $grid) use ($role_options) {
            $grid->column('id')->sortable();
            $grid->column('name');
            $grid->column('userId')->copyable();
            $grid->column('role')->display(function ($role) use ($role_options) {
                $textArray = [];
                foreach ($role_options as $key => $value) {
                    if (in_array($key, $role)) {
                        $textArray[] = $value;
                    }
                }

                return implode('｜', $textArray);
            })->label();
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
        return Show::make($id, new Auditor(), function (Show $show) {
            $show->field('id');
            $show->field('userId');
            $show->field('role');
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
        $repository = new Auditor();

        $role_options = $repository->model()::ROLE;

        return Form::make($repository, function (Form $form) use ($role_options) {
            $form->display('id');
            $form->text('name')->help('审核员名称');
            $form->text('userId')->required()->help('审核员TG ID');
            $form->multipleSelect('role')->required()->options($role_options);

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}

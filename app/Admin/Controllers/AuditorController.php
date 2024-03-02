<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\Auditor;
use App\Enums\AuditorRole;
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

        return Grid::make($repository, function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('name');
            $grid->column('userId')->copyable();
            $grid->column('role')->display(function ($role) {
                $textArray = [];
                foreach (AuditorRole::ROLE_NAME as $key => $value) {
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

        return Form::make($repository, function (Form $form) {
            $form->display('id');
            $form->text('name')->help('审核员名称');
            $form->text('userId')->required()->help('审核员TG ID');
            $form->multipleSelect('role')->required()->options(AuditorRole::ROLE_NAME)->help('审核员角色');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}

<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\KeyboardNameConfig;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class KeyboardNameConfigController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new KeyboardNameConfig(), function (Grid $grid) {

            $grid->model()->orderBy('id', 'desc');

            $grid->column('id')->sortable();
            $grid->column('name')->display(function ($name) {
                return $this->group.'.'.$name;
            })->copyable();
            $grid->column('value');
            $grid->column('description');
            $grid->column('created_at');
            $grid->column('updated_at')->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->panel();
                $filter->equal('id');
                $filter->like('group');
                $filter->like('description');
                $filter->like('value');
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
        return Show::make($id, new KeyboardNameConfig(), function (Show $show) {
            $show->field('id');
            $show->field('group');
            $show->field('name');
            $show->field('value');
            $show->field('description');
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
        return Form::make(new KeyboardNameConfig(), function (Form $form) {
            $form->display('id');
            $form->text('group')->rules('required|min:3|max:100|regex:/^[A-Za-z_]+$/', [
                'regex' => '必须全部为英文字符',
                'min' => '不能少于3个字符',
                'max' => '不能大于10个字符',
            ])->required();
            $form->text('name')->rules('required|min:3|max:100|regex:/^[A-Za-z_]+$/', [
                'regex' => '必须全部为英文字符',
                'min' => '不能少于3个字符',
                'max' => '不能大于10个字符',
            ])->required();
            $form->text('description')->required();
            $form->textarea('value')->required();

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}

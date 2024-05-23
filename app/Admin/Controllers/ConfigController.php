<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\Config;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Show;

class ConfigController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Config(), function (Grid $grid) {

            $grid->model()->orderBy('id', 'desc');

            $grid->column('id')->sortable();
            $grid->column('description');
            $grid->column('name')->display(function ($name) {
                return $this->group.'.'.$name;
            })->copyable();
            $grid->column('value')->limit(10);
            $grid->column('created_at');
            $grid->column('updated_at')->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->panel();
                $filter->equal('id');
                $filter->like('group');
                $filter->like('name');
                $filter->like('description');
                $filter->like('value');
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
        return Show::make($id, new Config(), function (Show $show) {
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
        return Form::make(new Config(), function (Form $form) {
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
            $form->textarea('value')
                ->help("
支持html格式(参考<a href='https://core.telegram.org/bots/api#html-style' target='_blank'>https://core.telegram.org/bots/api#html-style</a>)。<br>
如果字符内含有变量，请不要随意改动变量，如：<pre>{url}</pre>您可以移动位置，但是不能改为这种名称。<pre>{uri}</pre><br>
其次，如果是这种<pre>".htmlspecialchars("<a href='{url}'>{title}</a>").'</pre>这种内容，请不要改动a标签以及href属性，只能改动{title}这种变量的左右内容，如：<pre>'.htmlspecialchars("<a href='{url}'>标题：{title}</a>").'</pre><br>
');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}

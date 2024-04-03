<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\Manuscript;
use App\Enums\ObjectType;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Show;

class ManuscriptController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Manuscript(), function (Grid $grid) {

            $grid->model()->orderBy('id', 'desc');

            $grid->column('id')->sortable();
            $grid->column('type')->display(function ($type) {
                return ObjectType::data[$type];
            })->label();
            $grid->column('text')->title();
            $grid->column('posted_by')->display(function ($posted_by) {
                return get_posted_by($posted_by);
            })->expand(function () {
                // 返回显示的详情
                $uid = $this->posted_by['id'];
                $first_name = $this->posted_by['first_name'] ?? '';
                $last_name = $this->posted_by['last_name'] ?? '';
                $username = $this->posted_by['username'] ?? '';

                return "<div style='padding:10px 10px'><p>UID: $uid</p><p>first name: $first_name</p><p>last name: $last_name</p><p>用户名: $username</p></div>";
            });
            //            $grid->column('appendix');
            $grid->column('approved')->display(function ($approved) {
                return count($approved);
            })->expand(function () {
                $html = "<div style='padding:10px 10px'>";
                foreach ($this->approved as $key => $item) {
                    $id= $item['id'];
                    $first_name = $item['first_name'] ?? '';
                    $last_name = $item['last_name'] ?? '';
                    $html .= "<p>· {$id} | {$first_name} {$last_name}</p>";
                }

                return $html.'</div>';
            });
            $grid->column('reject')->display(function ($reject) {
                return count($reject);
            })->expand(function () {
                $html = "<div style='padding:10px 10px'>";
                foreach ($this->reject as $key => $item) {
                    $id= $item['id'];
                    $first_name = $item['first_name'] ?? '';
                    $last_name = $item['last_name'] ?? '';
                    $html .= "<p>· {$id} | {$first_name} {$last_name}</p>";
                }

                return $html.'</div>';
            });
            $grid->column('one_approved')->display(function ($one_approved) {
                if (empty($one_approved)) {
                    return '无';
                }
                return get_posted_by($one_approved);
            })->expand(function () {
                // 返回显示的详情
                $uid = $this->one_approved['id'] ?? '';
                $first_name = $this->one_approved['first_name'] ?? '';
                $last_name = $this->one_approved['last_name'] ?? '';
                $username = $this->one_approved['username'] ?? '';

                return "<div style='padding:10px 10px'><p>UID: $uid</p><p>first name: $first_name</p><p>last name: $last_name</p><p>用户名: $username</p></div>";
            });
            $grid->column('one_reject')->display(function ($one_reject) {
                if (empty($one_reject)) {
                    return '无';
                }

                return get_posted_by($one_reject);
            })->expand(function () {
                // 返回显示的详情
                $uid = $this->one_reject['id'] ?? '';
                $first_name = $this->one_reject['first_name'] ?? '';
                $last_name = $this->one_reject['last_name'] ?? '';
                $username = $this->one_reject['username'] ?? '';

                return "<div style='padding:10px 10px'><p>UID: $uid</p><p>first name: $first_name</p><p>last name: $last_name</p><p>用户名: $username</p></div>";
            });
            $grid->column('status')->display(function ($status) {
                return \App\Enums\ManuscriptStatus::ALL_NAME[$status];
            })->label();
            $grid->column('created_at');
            $grid->column('updated_at')->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->panel();
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
        return Show::make($id, new Manuscript(), function (Show $show) {
            $show->field('id');
            $show->field('type');
            $show->field('text');
            $show->field('posted_by');
            $show->field('data');
            $show->field('appendix');
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
        return Form::make(new Manuscript(), function (Form $form) {
            $form->display('id');
            $form->text('type');
            $form->text('text');
            $form->text('posted_by');
            $form->text('data');
            $form->text('appendix');
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

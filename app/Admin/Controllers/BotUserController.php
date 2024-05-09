<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\BotUser;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Show;

class BotUserController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new BotUser('bot'), function (Grid $grid) {

            // 禁用
            $grid->disableCreateButton();

            $grid->model()->orderBy('id', 'desc');

            $grid->column('id')->sortable();
            $grid->column('bot.appellation');
            $grid->column('type')->display(function () {
                return admin_trans('bot-user.type')[$this->user_data['type']];
            })->label();
            $grid->column('user_id');
            $grid->column('user_name')->display(function () {
                if ($this->user_data['type'] != 'private') {
                    return $this->user_data['title'];
                }

                return get_posted_by($this->user_data);
            })->expand(function () {
                if ($this->user_data['type'] != 'private') {
                    return "<div style='padding:10px 10px'><p>title: {$this->user_data['title']}</p></div>";
                }
                // 返回显示的详情
                $uid = $this->user_data['id'] ?? '';
                $first_name = $this->user_data['first_name'] ?? '';
                $last_name = $this->user_data['last_name'] ?? '';
                $username = $this->user_data['username'] ?? '';

                return "<div style='padding:10px 10px'><p>UID: $uid</p><p>first name: $first_name</p><p>last name: $last_name</p><p>用户名: $username</p></div>";
            });
            $grid->column('created_at');
            $grid->column('updated_at')->sortable();

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableEdit();
                $actions->disableQuickEdit();
            });

            $grid->filter(function (Grid\Filter $filter) {
                $filter->panel();
                $filter->equal('id');
                $filter->equal('bot_id')->select(function () {
                    return \App\Models\Bot::all()->pluck('appellation', 'id');
                });
                $filter->equal('user_id');
                $filter->like('user_data', '用户名称');
                $filter->between('created_at')->datetime();
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
        return Show::make($id, new BotUser('bot'), function (Show $show) {
            $show->field('id');
            $show->field('bot.appellation');
            $show->field('user_id');
            $show->field('user_name')->as(function () {
                return get_posted_by($this->user_data);
            });
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
            $form->text('user_id');
            $form->text('user_data');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}

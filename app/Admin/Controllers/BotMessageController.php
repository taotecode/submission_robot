<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\BotMessage;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class BotMessageController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new BotMessage('bot'), function (Grid $grid) {
            // 禁用
            $grid->disableCreateButton();

            $grid->model()->orderBy('id', 'desc');

            $grid->column('id')->sortable();
            $grid->column('bot.appellation');
            $grid->column('user_name')->display(function () {
                return get_posted_by($this->userData);
            })->expand(function ($model) {
                if (empty($this->userData)) {
                    return '暂无信息';
                }
                if ($this->userData['type'] != 'private'){
                    return "<div style='padding:10px 10px'><p>title: {$this->userData['title']}</p></div>";
                }
                // 返回显示的详情
                $uid = $this->userData['id'] ?? '';
                $first_name = $this->userData['first_name'] ?? '';
                $last_name = $this->userData['last_name'] ?? '';
                $username = $this->userData['username'] ?? '';
                return "<div style='padding:10px 10px'><p>UID: $uid</p><p>first name: $first_name</p><p>last name: $last_name</p><p>用户名: $username</p></div>";
            });

            $grid->column('text')->display(function () {
                if (!empty($this->data['text'])){
                    return $this->data['text'];
                }
                if (!empty($this->data['caption'])){
                    return $this->data['caption'];
                }
                return '暂无信息';
            })->expand(function ($model) {
                $text = '暂无信息';
                if (!empty($this->data['text'])){
                    $text = $this->data['text'];
                }
                if (!empty($this->data['caption'])){
                    $text = $this->data['caption'];
                }
                $html="<div style='padding:10px 10px'><p>内容: {$text}</p></div>";
                //如果有图片
//                if (!empty($this->data['photo'])){
//                    $html.="<div style='padding:10px 10px'><p>图片: </p>";
//                    $imagesNum = count($this->data['photo'])-1;
//                    $imageFileId = $this->data['photo'][$imagesNum]['file_id'];
//                    $photo = get_file_url($imageFileId);
//                    $html.="<img src='{$photo}' style='max-width: 100%;height: auto;'>";
//                    $html.="</div>";
//                }
                return $html;
            });

            $grid->column('created_at');
            $grid->column('updated_at')->sortable();

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableEdit();
                $actions->disableQuickEdit();
            });

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
                $filter->equal('bot_id')->select(function () {
                    return \App\Models\Bot::all()->pluck('appellation', 'id');
                });
                $filter->equal('userId');
                $filter->like('user_data','用户名称');
                $filter->between('created_at')->datetime();
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
        return Show::make($id, new BotMessage(), function (Show $show) {
            $show->field('id');
            $show->field('bot_id');
            $show->field('userId');
            $show->field('userData');
            $show->field('data');
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
        return Form::make(new BotMessage(), function (Form $form) {
            $form->display('id');
            $form->text('bot_id');
            $form->text('userId');
            $form->text('userData');
            $form->text('data');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}

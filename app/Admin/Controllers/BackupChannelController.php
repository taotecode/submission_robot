<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\BackupChannel;
use App\Models\Channel;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Show;

class BackupChannelController extends AdminController
{
    /**
     * 标题
     *
     * @return string
     */
    protected $title = '备份频道管理';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new BackupChannel(['channel']), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('channel.name', '主频道')->display(function ($name) {
                if (! empty($name)) {
                    return '@'.$name;
                }
                return 'NULL';
            });
            $grid->column('channel.appellation', '主频道名称');
            $grid->column('backup_name')->display(function ($name) {
                if (! empty($name)) {
                    return '@'.$name;
                }
                return 'NULL';
            })->copyable();
            $grid->column('backup_appellation');
            $grid->column('is_public')->using([1 => '公开', 0 => '私有'])->badge([
                1 => 'success',
                0 => 'blue',
            ]);
            $grid->column('chat_id')->display(function ($chat_id) {
                if ($this->is_public == 0 && !empty($chat_id)) {
                    return $chat_id;
                }
                return '-';
            });
            $grid->column('sort_order')->sortable()->editable(true);
            $grid->column('created_at');
            $grid->column('updated_at')->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->panel();
                $filter->equal('id');
                $filter->equal('channel_id', '主频道')->select(
                    Channel::query()->pluck('appellation', 'id')
                );
                $filter->like('backup_name');
                $filter->like('backup_appellation');
                $filter->equal('is_public')->select([1 => '公开', 0 => '私有']);
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
        return Show::make($id, new BackupChannel(['channel']), function (Show $show) {
            $show->field('id');
            $show->field('channel.name', '主频道');
            $show->field('channel.appellation', '主频道名称');
            $show->field('backup_name');
            $show->field('backup_appellation');
            $show->field('is_public')->using([1 => '公开', 0 => '私有']);
            $show->field('chat_id');
            $show->field('sort_order');
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
        return Form::make(new BackupChannel(), function (Form $form) {
            $form->display('id');
            
            $form->select('channel_id', '主频道')
                ->options(Channel::query()->pluck('appellation', 'id'))
                ->required()
                ->help('选择要备份的主频道');
                
            $form->text('backup_name')
                ->help('备份频道公开链接，如：https://t.cn/backup_channel，那么就可以填：backup_channel，注意不要带@');
                
            $form->text('backup_appellation')
                ->help('备份频道名称，如：这是一个备份频道');
                
            $form->radio('is_public')
                ->options([1 => '公开', 0 => '私有'])
                ->default(1)
                ->when(0, function (Form $form) {
                    $form->text('chat_id')->help('私有频道的chat_id');
                });
                
            $form->number('sort_order')->default(0);

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
} 
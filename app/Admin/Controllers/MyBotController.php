<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\Bot;
use App\Admin\RowActions\MyBot\DelWebHook;
use App\Admin\RowActions\MyBot\SetChannel;
use App\Admin\RowActions\MyBot\SetCommands;
use App\Admin\RowActions\MyBot\SetIsAutoKeyword;
use App\Admin\RowActions\MyBot\SetTailContent;
use App\Admin\RowActions\MyBot\SetWebHook;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Http\JsonResponse;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Show;
use Illuminate\Support\Facades\Storage;

class MyBotController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {

        return Grid::make(new Bot(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('appellation');
            $grid->column('name')->display(function ($name) {
                return '@'.$name;
            })->copyable();
            $grid->column('review_num')->badge();
            $grid->column('status')->switch();
            $grid->column('created_at');
            $grid->column('updated_at')->sortable();

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                // append一个操作
                if ($actions->row->webhook_status < 1) {
                    $actions->append(new SetWebHook());
                } else {
                    $actions->append(new DelWebHook());
                }
                $actions->append(new SetCommands());
                $actions->append(new SetTailContent());
                $actions->append(new SetIsAutoKeyword());
                $actions->append(new SetChannel());
            });

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
        return Show::make($id, new Bot(), function (Show $show) {
            $show->field('id');
            $show->field('appellation');
            $show->field('name');
            $show->field('token');
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
        return Form::make(new Bot(), function (Form $form) {
            $form->display('id');
            $form->text('appellation')->help('机器人的用户名');
            $form->text('name')->help('不需要携带@符号，如：tougao_bot')->required();
            $form->password('token')->help('通过@BotFather创建机器人获取')->required();
            $form->number('review_num')->min(1)->max(30)->default(1)->required()
                ->help('每条投稿消息的审核数量。如：设置1，那么只需要一个人就可以通过或拒绝。设置2，那么就需要两个人就可以通过或拒绝。<br>最小值为：1');

            $form->switch('status')->default(1);
            $form->display('created_at');
            $form->display('updated_at');
        });
    }

    public function lexiconCheck($id, Content $content)
    {
        if (request()->isMethod('PUT')) {
            $lexicon = request()->input('lexicon');
            $text = request()->input('text');
            $time = time();
            Storage::put("public/lexicon_temp_{$time}.txt", $lexicon);
            $result = quickCut($text, storage_path('app/public/lexicon_temp_'.$time.'.txt'));
            Storage::delete("public/lexicon_temp_{$time}.txt");
            if (empty($result)) {
                return JsonResponse::make()->error('分词结果为空');
            }

            return JsonResponse::make()->alert()->success('分词结果为')->detail(implode('|', $result));
        }
        $form = Form::make(new Bot(), function (Form $form) use ($id) {
            $form->action(route('dcat.admin.bots.lexiconCheck', $id));
            $form->textarea('text', '稿件文本内容')->required();
            $form->textarea('lexicon', '词库')->required()->help('
        词库格式为每行一个词，如果需要提升分词准确率，可以在词语后面加上词性，词性之间用空格隔开，词性列表如下：<br>
        新闻 1<br>
        一般数值在1-10之间，数值越大，分词越准确，但是分词速度越慢，建议平均值为：3<br>
        ');

            $form->footer(function ($footer) {

                // 去掉`继续编辑`checkbox
                $footer->disableEditingCheck();

                // 去掉`继续创建`checkbox
                $footer->disableCreatingCheck();

                // 去掉`查看`checkbox
                $footer->disableViewCheck();
            });
        });

        return $content
            ->translation($this->translation())
            ->title($this->title())
            ->description('词库验证')
            ->body($form->edit($id));
    }
}

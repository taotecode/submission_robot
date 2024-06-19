<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\Config;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Show;
use Dcat\Admin\Support\JavaScript;

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
            $grid->disableBatchDelete();
            $grid->disableDeleteButton();
            $grid->disableRowSelector();
            $grid->disableCreateButton();
            $grid->setActionClass(Grid\Displayers\Actions::class);

            $grid->model()->orderBy('id', 'desc');

            $grid->column('id')->sortable();
            $grid->column('description');
            $grid->column('name')->display(function ($name) {
                return $this->group . '.' . $name;
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
     * @param mixed $id
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
            ])->disable();
            $form->text('name')->rules('required|min:3|max:100|regex:/^[A-Za-z_]+$/', [
                'regex' => '必须全部为英文字符',
                'min' => '不能少于3个字符',
                'max' => '不能大于10个字符',
            ])->disable();
            $form->text('description')->disable();
            /*$form->textarea('value')
                ->help("
支持html格式(参考<a href='https://core.telegram.org/bots/api#html-style' target='_blank'>https://core.telegram.org/bots/api#html-style</a>)。<br>
如果字符内含有变量，请不要随意改动变量，如：<pre>{url}</pre>您可以移动位置，但是不能改为这种名称。<pre>{uri}</pre><br>
其次，如果是这种<pre>".htmlspecialchars("<a href='{url}'>{title}</a>").'</pre>这种内容，请不要改动a标签以及href属性，只能改动{title}这种变量的左右内容，如：<pre>'.htmlspecialchars("<a href='{url}'>标题：{title}</a>").'</pre><br>
');*/
            $form->editor('value')->options([
                'branding' => false,
                'forced_root_block' => false,
                'force_p_newlines' => false,
                'force_br_newlines' => true,
                'newline_behavior'=> 'linebreak',
                'remove_linebreaks'=>false,
                'menubar' => 'file edit',
                'toolbar' => [
                    'bold italic underline strikethrough spoiler link blockquote codesample',
                    'undo redo paste removeformat code preview fullscreen'
                ],
                'formats' => [
                    'bold' => ['inline' => 'b'],
                    'italic' => ['inline' => 'i'],
                    'underline' => ['inline' => 'u'],
                    'strikethrough' => ['inline' => 's'],
                    'spoiler' => ['inline' => 'span', 'classes' => 'tg-spoiler'],
                    'link' => ['inline' => 'a', 'attributes' => ['href' => '{href}']],
                    'code' => ['inline' => 'code'],
                    'pre' => ['block' => 'pre'],
                    'br'=>['inline' => 'br']
                ],
                'valid_elements' => [
                    'b', 'strong', 'i', 'em', 'u', 'ins', 's', 'strike', 'del','br',
                    'span[class=tg-spoiler]', 'tg-spoiler',
                    'a[href]', 'a[href=tg://]',
                    'tg-emoji[emoji-id]', 'code', 'pre', 'pre[code]', 'blockquote', 'blockquote[expandable]'
                ],
                'link_dialog' => [
                    'url' => [
                        'pattern' => '/^(tg:\/\/|https?:\/\/)/',
                        ' schemes' => ['tg', 'http', 'https'],
                    ],
                ],
                'invalid_elements' => '*', // 禁止所有其他 HTML 元素
                'extended_valid_elements' => 'tg-emoji[emoji-id]', // 允许 tg-emoji 元素

            ])->required()
                ->customFormat(function ($value){
                    $value = str_replace(PHP_EOL, '<br />', $value);
                    $value = str_replace(' ', '&nbsp;', $value);
                    return $value;
                })
                ->help("
支持html格式(参考<a href='https://core.telegram.org/bots/api#html-style' target='_blank'>https://core.telegram.org/bots/api#html-style</a>)。<br>
如果字符内含有变量，请不要随意改动变量，如：<pre>{url}</pre>您可以移动位置，但是不能改为这种名称。<pre>{uri}</pre><br>
其次，如果是这种<pre>" . htmlspecialchars("<a href='{url}'>{title}</a>") . '</pre>这种内容，请不要改动a标签以及href属性，只能改动{title}这种变量的左右内容，如：<pre>' . htmlspecialchars("<a href='{url}'>标题：{title}</a>") . '</pre><br>
');

            $form->display('created_at');
            $form->display('updated_at');

            $form->submitted(function (Form $form) {
                // 获取 POST 请求中的字符串
                $value = $form->input('value');

                // 将 <br> 替换为换行符
                $value = str_replace('<br />', PHP_EOL, $value);
                $form->value=$value;
            });
        });
    }
}

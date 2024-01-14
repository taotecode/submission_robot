<?php

namespace App\Admin\RowActions\MyBot;

use App\Admin\Forms\MyBot\TailContentForm;
use Dcat\Admin\Grid\RowAction;
use Dcat\Admin\Widgets\Modal;

class SetTailContent extends RowAction
{
    /**
     * 标题
     *
     * @return string
     */
    protected $title = '【设置消息尾部】';

    public function render()
    {
        // 实例化表单类并传递自定义参数
        $form = TailContentForm::make()
            ->payload([
                'id' => $this->getKey(),
                'tail_content' => $this->row->tail_content,
                'tail_content_button' => json_encode($this->row->tail_content_button??[]),
            ]);

        return Modal::make()
            ->lg()
            ->scrollable() // 设置弹窗内容可滚动
            ->title($this->title)
            ->body($form)
            ->button($this->title);
    }
}

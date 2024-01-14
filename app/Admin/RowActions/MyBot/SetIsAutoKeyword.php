<?php

namespace App\Admin\RowActions\MyBot;

use App\Admin\Forms\MyBot\IsAutoKeyword;
use Dcat\Admin\Grid\RowAction;
use Dcat\Admin\Widgets\Modal;

class SetIsAutoKeyword extends RowAction
{
    /**
     * 标题
     *
     * @return string
     */
    protected $title = '【设置关键词】';

    public function render()
    {
        $form = IsAutoKeyword::make()
            ->payload([
                'id' => $this->getKey(),
                'is_auto_keyword' => $this->row->is_auto_keyword,
                'keyword' => $this->row->keyword,
                'lexicon' => $this->row->lexicon,
            ]);

        return Modal::make()
            ->lg()
            ->scrollable() // 设置弹窗内容可滚动
            ->title($this->title)
            ->body($form)
            ->button($this->title);
    }
}

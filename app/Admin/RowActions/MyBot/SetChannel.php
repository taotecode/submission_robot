<?php

namespace App\Admin\RowActions\MyBot;

use App\Admin\Forms\MyBot\Channel;
use Dcat\Admin\Grid\RowAction;
use Dcat\Admin\Widgets\Modal;

class SetChannel extends RowAction
{
    /**
     * 标题
     *
     * @return string
     */
    protected $title = '【设置发布频道】';

    public function render()
    {
        $form = Channel::make()
            ->payload([
                'id' => $this->getKey(),
                'channel_id' => $this->row->channel_id??null,
            ]);

        return Modal::make()
            ->lg()
            ->scrollable() // 设置弹窗内容可滚动
            ->title($this->title)
            ->body($form)
            ->button($this->title);
    }
}

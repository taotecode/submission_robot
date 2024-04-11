<?php

namespace App\Admin\RowActions\MyBot;

use App\Admin\Forms\MyBot\SetChannel as SetChannelForm;
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
        if (empty($this->row->channel_ids)) {
            $channel_ids = "";
        }else{
            $channel_ids = json_encode($this->row->channel_ids);
        }
        $form = SetChannelForm::make()
            ->payload([
                'id' => $this->getKey(),
                'channel_ids' => $channel_ids,
            ]);

        return Modal::make()
            ->lg()
            ->scrollable() // 设置弹窗内容可滚动
            ->title($this->title)
            ->body($form)
            ->button($this->title);
    }
}

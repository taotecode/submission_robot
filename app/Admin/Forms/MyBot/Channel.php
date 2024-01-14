<?php

namespace App\Admin\Forms\MyBot;

use App\Models\Bot;
use Dcat\Admin\Contracts\LazyRenderable;
use Dcat\Admin\Traits\LazyWidget;
use Dcat\Admin\Widgets\Form;

class Channel extends Form implements LazyRenderable
{
    use LazyWidget; // 使用异步加载功能

    // 处理请求
    public function handle(array $input)
    {
        $id = $input['id'];
        $channel_ids = $input['channel_ids'];
        $channel_ids = array_filter($channel_ids);

        $bot = Bot::find($id);
        $bot->channel_ids = $channel_ids;
        if ($bot->save()) {
            return $this->response()->success('操作成功')->refresh();
        } else {
            return $this->response()->error('操作失败')->refresh();
        }
    }

    // 构建表单
    public function form()
    {
        // 获取外部传递参数
        $id = $this->payload['id'] ?? null;
        $channel_ids = $this->payload['channel_ids'] ?? null;
        if ($channel_ids){
            $channel_ids = json_decode($channel_ids,true);
        }

        $this->checkbox('channel_ids', '发布频道')
            ->options(\App\Models\Channel::all()->pluck('appellation', 'id'))
            ->default($channel_ids)
            ->help('选择需要发布的频道，可以多选。');

        $this->hidden('id')->default($id);
    }
}

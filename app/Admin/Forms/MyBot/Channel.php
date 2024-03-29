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
        $channel_id = $input['channel_id'];

        $bot = Bot::find($id);
        $bot->channel_id = $channel_id;
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
        $channel_id = $this->payload['channel_id'] ?? null;
        if (empty($channel_id)) {
            $channel_id=null;
        }

        $this->select('channel_id', '发布频道')
            ->options(\App\Models\Channel::all()->pluck('appellation', 'id'))
            ->help('选择需要发布的频道，不可以多选。')
            ->default($channel_id);

        $this->hidden('id')->default($id);
    }
}

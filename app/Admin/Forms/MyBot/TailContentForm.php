<?php

namespace App\Admin\Forms\MyBot;

use App\Models\Bot;
use Dcat\Admin\Contracts\LazyRenderable;
use Dcat\Admin\Traits\LazyWidget;
use Dcat\Admin\Widgets\Form;

class TailContentForm extends Form implements LazyRenderable
{
    use LazyWidget; // 使用异步加载功能

    // 处理请求
    public function handle(array $input)
    {
        $id = $input['id'];
        $tail_content = $input['tail_content'];
        $tail_content_button = json_decode($input['tail_content_button'], true);
        $tail_content_button = array_filter($tail_content_button);
        $tail_content_button_array = [];
        $tail_content_button_num = 0;
        foreach ($tail_content_button as $k => $v) {
            if (empty($v)) {
                continue;
            }
            $tail_content_button_num++;
            foreach ($v as $key => $value) {
                if (empty($value)) {
                    continue;
                }
                if (! filter_var($value, FILTER_VALIDATE_URL)) {
                    return $this->response()->error('链接格式错误')->refresh();
                }
                $tail_content_button_array[$tail_content_button_num][] = ['text' => $key, 'url' => $value];
            }
        }
        $tail_content_button_array = array_values(array_filter($tail_content_button_array));

        $bot = Bot::find($id);
        $bot->tail_content = $tail_content;
        $bot->tail_content_button = $tail_content_button_array;
        if ($bot->save()) {
            return $this->response()->success('操作成功')->refresh();
        } else {
            return $this->response()->error('操作失败')->refresh();
        }
    }

    // 构建表单
    public function form()
    {
        $kv_1 = ['交流群' => '', '投稿链接' => ''];
        $kv_2 = [];
        $kv_3 = [];
        $kv_4 = [];
        $kv_5 = [];

        // 获取外部传递参数
        $id = $this->payload['id'] ?? null;
        $tail_content = $this->payload['tail_content'] ?? null;
        $tail_content_button = $this->payload['tail_content_button'] ?? null;
        if (! empty($tail_content_button)) {
            $tail_content_button = json_decode($tail_content_button, true);
            foreach ($tail_content_button as $k => $v) {
                if ($k === 0) {
                    foreach ($v as $key => $value) {
                        $kv_1[$value['text']] = $value['url'];
                    }
                } elseif ($k === 1) {
                    foreach ($v as $key => $value) {
                        $kv_2[$value['text']] = $value['url'];
                    }
                } elseif ($k === 2) {
                    foreach ($v as $key => $value) {
                        $kv_3[$value['text']] = $value['url'];
                    }
                } elseif ($k === 3) {
                    foreach ($v as $key => $value) {
                        $kv_4[$value['text']] = $value['url'];
                    }
                } elseif ($k === 4) {
                    foreach ($v as $key => $value) {
                        $kv_5[$value['text']] = $value['url'];
                    }
                }
            }
        }

        $this->textarea('tail_content', '消息尾部文本内容')->default($tail_content)->help("每条投稿消息的尾部内容，支持html格式(参考<a href='https://core.telegram.org/bots/api#html-style' target='_blank'>https://core.telegram.org/bots/api#html-style</a>)。");
        $this->embeds('tail_content_button', '消息尾部按钮组内容', function ($form) use ($kv_1, $kv_2, $kv_3, $kv_4, $kv_5) {
            $form->text('tips', '提示')->disable()->placeholder('提示')->help('每条投稿消息的尾部的按钮组内容。仅支持按钮链接，即点击按钮跳转链接。每行最多支持10个按钮，最多支持5行。');
            $form->keyValue('1', '第一行')->setKeyLabel('文本')->setValueLabel('链接')->rules('max:10')->default($kv_1);
            $form->keyValue('2', '第二行')->setKeyLabel('文本')->setValueLabel('链接')->rules('max:10')->default($kv_2);
            $form->keyValue('3', '第三行')->setKeyLabel('文本')->setValueLabel('链接')->rules('max:10')->default($kv_3);
            $form->keyValue('4', '第四行')->setKeyLabel('文本')->setValueLabel('链接')->rules('max:10')->default($kv_4);
            $form->keyValue('5', '第五行')->setKeyLabel('文本')->setValueLabel('链接')->rules('max:10')->default($kv_5);
        })->saving(function ($v) {
            // 转化为json格式存储
            return json_encode($v);
        });

        $this->hidden('id')->default($id);
    }
}

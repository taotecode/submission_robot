<?php

namespace App\Admin\Forms\MyBot;

use App\Models\Bot;
use Dcat\Admin\Contracts\LazyRenderable;
use Dcat\Admin\Traits\LazyWidget;
use Dcat\Admin\Widgets\Form;
use Illuminate\Support\Facades\Storage;

class IsAutoKeyword extends Form implements LazyRenderable
{
    use LazyWidget; // 使用异步加载功能

    // 处理请求
    public function handle(array $input)
    {
        $id = $input['id'];
        $is_auto_keyword = $input['is_auto_keyword'];
        $keyword = $input['keyword'];
        $lexicon = $input['lexicon'];

        $bot = Bot::find($id);
        $bot->is_auto_keyword = $is_auto_keyword;
        $bot->keyword = $keyword;
        $bot->lexicon = $lexicon;
        // 保存词库
        Storage::put("public/lexicon_{$id}.txt", $lexicon);
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
        $is_auto_keyword = $this->payload['is_auto_keyword'] ?? null;
        $keyword = $this->payload['keyword'] ?? null;
        $lexicon = $this->payload['lexicon'] ?? null;

        $this->radio('is_auto_keyword')
            ->when('1', function (Form $form) use ($id, $keyword, $lexicon) {
                $form->textarea('keyword')->help('每行一个关键词<br>当稿件文本经过词库分词后的词语中，含有关键词，则会在消息尾部加入如：#新闻 #的标签')->default('新闻')->value($keyword);
                $form->textarea('lexicon')->help('
                    词库格式为每行一个词，如果需要提升分词准确率，可以在词语后面加上词性，词性之间用空格隔开，词性列表如下：<br>
        新闻 1<br>
        一般数值在1-10之间，数值越大，分词越准确，但是分词速度越慢，建议平均值为：3<br>
        可以点击<a href="'.route('dcat.admin.bots.lexiconCheck', $id).'">词库验证</a>进行分词测试<br>
                    ')->default('新闻')->value($lexicon);
            })
            ->options([
                '0' => '关闭自动关键词',
                '1' => '开启自动关键词',
            ])
            ->help('
开启自动关键词后，会自动在消息尾部加入关键词标签，如：#新闻 #的标签<br>
注意：开启自动关键词后，需要在下方填写关键词和词库，否则无法正常工作。<br>
注意：服务器性能最少需要1核2G内存，否则会导致分词失败。对服务器性能要求较高。')
            ->default('1')->value($is_auto_keyword);

        $this->hidden('id')->default($id);
    }
}

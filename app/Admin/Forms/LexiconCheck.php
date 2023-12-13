<?php

namespace App\Admin\Forms;

use Dcat\Admin\Widgets\Form;
use Illuminate\Support\Facades\Storage;

class LexiconCheck extends Form
{
    /**
     * Handle the form request.
     */
    public function handle(array $input): mixed
    {

        $time = time();
        Storage::put("public/lexicon_temp_{$time}.txt", $input['lexicon']);
        $result = quickCut($input['text'], storage_path('app/public/lexicon_temp_'.$time.'.txt'));
        Storage::delete("public/lexicon_temp_{$time}.txt");

        if (empty($result)) {
            return $this->response()->error('分词结果为空');
        }

        return $this->response()->alert()->success('分词结果为')->detail(implode('|', $result));
    }

    /**
     * Build a form here.
     */
    public function form()
    {
        $this->text('text', '稿件文本内容')->required();
        $this->textarea('lexicon', '词库')->required()->help('
        词库格式为每行一个词，如果需要提升分词准确率，可以在词语后面加上词性，词性之间用空格隔开，词性列表如下：<br>
        新闻 1<br>
        一般数值在1-10之间，数值越大，分词越准确，但是分词速度越慢，建议平均值为：3<br>
        ');
    }

    /**
     * The data of the form.
     *
     * @return array
     */
    public function default()
    {
        /*return [
            'name'  => 'John Doe',
            'email' => 'John.Doe@gmail.com',
        ];*/
    }
}

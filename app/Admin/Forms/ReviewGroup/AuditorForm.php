<?php

namespace App\Admin\Forms\ReviewGroup;

use App\Models\Auditor;
use App\Models\ReviewGroupAuditor;
use Dcat\Admin\Contracts\LazyRenderable;
use Dcat\Admin\Traits\LazyWidget;
use Dcat\Admin\Widgets\Form;

class AuditorForm extends Form implements LazyRenderable
{
    use LazyWidget;

    // 使用异步加载功能

    // 处理请求
    public function handle(array $input)
    {
        $model = new ReviewGroupAuditor();
        $auditor_ids = array_filter($input['auditor_id']);//获取提交的审核员id
        $review_group_id = $input['review_group_id'];//获取审核组id
        $existing_ids = json_decode($input['id'], true);//获取已存在的审核员id

        $sqlData = [];
        $deleteIds = array_diff($existing_ids, $auditor_ids); // 需要删除的审核员ID
        $addIds = array_diff($auditor_ids, $existing_ids); // 需要新增的审核员ID

        // 删除不在提交的审核员中的已存在审核员
        foreach ($deleteIds as $key => $auditorId) {
            $model->destroy($key);
        }

        // 添加新的审核员
        foreach ($addIds as $auditorId) {
            $sqlData[] = [
                'review_group_id' => $review_group_id,
                'auditor_id' => $auditorId,
            ];
        }

        if (empty($sqlData)) {
            return $this->response()->success('操作成功')->refresh();
        }

        if ($model->insert($sqlData)) {
            return $this->response()->success('操作成功')->refresh();
        }

        return $this->response()->error('操作失败');
    }

    public function form()
    {
        // 获取外部传递参数
        $review_group_id = $this->payload['review_group_id'] ?? null;

        $auditorAll = Auditor::all()->pluck('name', 'id')->toArray();

        $reviewGroupAuditor = ReviewGroupAuditor::where('review_group_id', $review_group_id)->pluck('auditor_id', 'id')->toArray();

        $this->checkbox('auditor_id', '审核员')
            ->options($auditorAll)
            ->default(array_values($reviewGroupAuditor));

        $this->hidden('review_group_id')->default($review_group_id);
        $this->hidden('id')->default(json_encode($reviewGroupAuditor));
    }
}

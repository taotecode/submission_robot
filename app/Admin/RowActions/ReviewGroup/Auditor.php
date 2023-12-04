<?php

namespace App\Admin\RowActions\ReviewGroup;

use App\Admin\Forms\ReviewGroup\AuditorForm;
use Dcat\Admin\Grid\RowAction;
use Dcat\Admin\Widgets\Modal;

class Auditor extends RowAction
{
    protected $title = '审核人员管理';

    public function render()
    {
        // 实例化表单类并传递自定义参数
        $form = AuditorForm::make()->payload(['review_group_id' => $this->getKey()]);

        return Modal::make()
            ->lg()
            ->title($this->title)
            ->body($form)
            ->button($this->title);
    }
}

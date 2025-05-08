<?php

namespace App\Admin\Extensions\Tools;

use App\Enums\ManuscriptStatus;
use App\Models\Manuscript;
use Dcat\Admin\Grid\BatchAction;
use Illuminate\Http\Request;

class BatchPublishManuscript extends BatchAction
{
    // 确认弹窗信息
    public function confirm()
    {
        return '确定要批量发布选中的稿件吗？';
    }

    // 处理请求
    public function handle(Request $request)
    {
        // 获取选中的稿件ID数组
        $keys = $this->getKey();
        
        $success = 0;
        
        foreach (Manuscript::find($keys) as $manuscript) {
            // 调用ManuscriptController的publishToChannel方法
            $controller = new \App\Admin\Controllers\ManuscriptController();
            if ($controller->publishToChannel($manuscript)) {
                $success++;
            }
        }
        
        $message = "成功发布 {$success} 个稿件";
        
        return $this->response()->success($message)->refresh();
    }
} 
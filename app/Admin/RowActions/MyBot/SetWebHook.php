<?php

namespace App\Admin\RowActions\MyBot;

use App\Services\BaseService;
use Dcat\Admin\Actions\Response;
use Dcat\Admin\Grid\RowAction;
use Exception;
use Illuminate\Http\Request;

class SetWebHook extends RowAction
{
    /**
     * 标题
     *
     * @return string
     */
    public function title()
    {
        return '【设置Web Hook】';
    }

    /**
     * 设置确认弹窗信息，如果返回空值，则不会弹出弹窗
     *
     * 允许返回字符串或数组类型
     *
     * @return array|string|void
     */
    public function confirm()
    {
        return [
            // 确认弹窗 title
            '设置Web Hook提示',
            // 确认弹窗 content
            '请确保您的网站已开启HTTPS访问，并且SSL证书完整。',
        ];
    }

    /**
     * 处理请求
     */
    public function handle(Request $request): Response
    {
        // 获取当前行ID
        $id = $this->getKey();
        $token = $request->input('token');

        try {
            (new BaseService)->setWebHook($id, $token);

            return $this->response()->success('设置成功')->refresh();
        } catch (Exception $e) {
            // 返回响应结果并刷新页面
            return $this->response()->error('设置失败: '.$e->getMessage())->refresh();
        }
    }

    /**
     * 设置要POST到接口的数据
     *
     * @return array
     */
    public function parameters()
    {
        return [
            // 发送当前行 token 字段数据到接口
            'token' => $this->row->token,
        ];
    }
}

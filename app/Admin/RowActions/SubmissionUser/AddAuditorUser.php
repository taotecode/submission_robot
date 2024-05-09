<?php

namespace App\Admin\RowActions\SubmissionUser;

use App\Enums\AuditorRole;
use Dcat\Admin\Actions\Response;
use Dcat\Admin\Grid\RowAction;
use Exception;
use Illuminate\Http\Request;

class AddAuditorUser extends RowAction
{
    /**
     * 标题
     */
    public function title(): string
    {
        return '【快速添加至审核人员】';
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
            '快速添加至审核人员',
            // 确认弹窗 content
            '您可以将该用户快速添加至审核人员，默认权限<b>【通过、拒绝】</b>，是否继续？<br><br>'.
            "注意：该操作不可逆！如需撤回请到<a href='".admin_url('/auditors')."'>【审核人员】</a>中进行操作！<br><br>".
            '如添加完毕，请及时通知用户！加入相应群聊！<br><br>'.
            "<b>添加审核人员后，您需要去<a href='".admin_url('/review_groups')."'>【审核群组】</a>中针对某个群组进行【审核人员管理】添加相应人员</b>",
        ];
    }

    /**
     * 处理请求
     */
    public function handle(Request $request): Response
    {
        // 获取当前行ID
        $userId = $request->input('userId');
        $name = $request->input('name');

        try {
            $auditor = new \App\Models\Auditor();

            $auditor->userId = $userId;
            $auditor->name = $name;
            $auditor->role = [AuditorRole::APPROVAL, AuditorRole::REJECTION];
            $auditor->save();

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
            'userId' => $this->row->userId,
            'name' => $this->row->name,
        ];
    }
}

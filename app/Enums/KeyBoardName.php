<?php

namespace App\Enums;

class KeyBoardName
{
    const StartSubmission='开始投稿';

    const Feedback='意见反馈';

    const HelpCenter='帮助中心';

    const EndSending='结束发送';

    const Restart='重新开始';

    const CancelSubmission='取消投稿';

    //确认投稿（公开）
    const ConfirmSubmissionOpen='确认投稿（公开）';

    //确认投稿（匿名）
    const ConfirmSubmissionAnonymous='确认投稿（匿名）';

    const Cancel='取消';

    //通过
    const Approved='通过';

    const QuickApproved='快速通过';

    const ApprovedEnd='已通过';

    //拒绝
    const Rejected='拒绝';

    const QuickRejected='快速拒绝';

    const RejectedEnd='已拒绝';

    const PrivateChat='私聊';

    const SelectChannel='选择发布频道';

    const SelectChannelAgain='重新选择频道';

    //查看消息
    const ViewMessage='查看消息';

    //删除消息
    const DeleteMessage='删除消息';

    //消息已被删除
    const MessageDeleted='消息已被删除';

    //删除白名单用户投稿
    const DeleteWhiteListUser='删除白名单用户投稿';


    //提交投诉
    const SubmitComplaint='提交投诉';

    //提交建议
    const SubmitSuggestion='提交建议';
}

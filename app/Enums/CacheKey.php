<?php

namespace App\Enums;

class CacheKey
{
    // 投稿服务缓存
    const Submission = 'submission';

    //投稿缓存用户列表
    const SubmissionUserList = 'submission_user_list';

    // 投诉服务缓存
    const Complaint = 'complaint';

    // 建议服务缓存
    const Suggestion = 'suggestion';
}

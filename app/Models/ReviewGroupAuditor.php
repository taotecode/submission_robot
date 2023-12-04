<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

class ReviewGroupAuditor extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'review_group_auditors';

    protected $fillable = [
        'review_group_id', 'auditor_id',
    ];

    public function review_group()
    {
        return $this->belongsTo('App\Models\ReviewGroup', 'review_group_id');
    }

    public function auditor()
    {
        return $this->belongsTo('App\Models\User', 'auditor_id');
    }
}

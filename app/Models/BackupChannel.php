<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BackupChannel extends Model
{
    use HasFactory, HasDateTimeFormatter;

    protected $fillable = [
        'channel_id',
        'backup_name',
        'backup_appellation',
        'chat_id',
        'is_public',
        'sort_order',
        'created_at',
        'updated_at',
    ];

    /**
     * 获取关联的主频道
     */
    public function channel()
    {
        return $this->belongsTo(Channel::class, 'channel_id', 'id');
    }
}

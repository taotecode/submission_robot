<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

class Channel extends Model
{
    use HasDateTimeFormatter;

    protected $fillable = [
        'name',
        'appellation',
        'sort_order',
        'created_at',
        'updated_at',
    ];

    /**
     * 获取该频道的所有备份频道
     */
    public function backupChannels()
    {
        return $this->hasMany(BackupChannel::class, 'channel_id', 'id');
    }
}

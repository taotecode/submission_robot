<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

class Config extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'config';

    protected static function boot(): void
    {
        parent::boot();

        static::created(function ($model) {
            cache()->put('config:'.$model->group.':'.$model->name, $model->value, now()->addWeek());
        });

        static::updated(function ($model) {
            cache()->put('config:'.$model->group.':'.$model->name, $model->value, now()->addWeek());
        });

        static::deleted(function ($model) {
            cache()->forget('config:'.$model->group.':'.$model->name);
        });
    }
}

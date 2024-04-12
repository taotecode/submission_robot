<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;

class KeyboardNameConfig extends Model
{
	use HasDateTimeFormatter;
    protected $table = 'keyboard_name_config';

    protected static function boot(): void
    {
        parent::boot();

        static::created(function ($model) {
            cache()->put('keyboard_name:'.$model->group.':'.$model->name, $model->value, now()->addWeek());
        });

        static::updated(function ($model) {
            cache()->put('keyboard_name:'.$model->group.':'.$model->name, $model->value, now()->addWeek());
        });

        static::deleted(function ($model) {
            cache()->forget('keyboard_name:'.$model->group.':'.$model->name);
        });
    }
}

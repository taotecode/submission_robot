<?php

use App\Models\Config as ConfigModel;
use App\Models\KeyboardNameConfig as KeyboardNameConfigModel;

function get_config($name, $default = null)
{
    $nameArray = explode('.', $name);
    if (count($nameArray) <= 1) {
        $data = ConfigModel::where('name', reset($nameArray))->first();
        if (empty($data)) {
            return $default;
        }
        cache()->put('config:'.$data->group.':'.$data->name, $data->value, now()->addWeek());
        $value = $data->value;
    } else {
        if (cache()->has('config:'.$nameArray[0].':'.$nameArray[1])) {
            $data = cache()->get('config:'.$nameArray[0].':'.$nameArray[1]);
            if (empty($data)) {
                return $default;
            }
            $value = $data;
        } else {
            $data = ConfigModel::where(['group' => $nameArray[0], 'name' => $nameArray[1]])->first();
            if (empty($data)) {
                return $default;
            }
            $value = $data->value;
            cache()->put('config:'.$data->group.':'.$data->name, $data->value, now()->addWeek());
        }
    }

    return $value;
}
function get_keyboard_name_config($name, $default = null)
{
    $nameArray = explode('.', $name);
    if (count($nameArray) <= 1) {
        $data = KeyboardNameConfigModel::where('name', reset($nameArray))->first();
        if (empty($data)) {
            return $default;
        }
        cache()->put('keyboard_name:'.$data->group.':'.$data->name, $data->value, now()->addWeek());
        $value = $data->value;
    } else {
        if (cache()->has('keyboard_name:'.$nameArray[0].':'.$nameArray[1])) {
            $data = cache()->get('keyboard_name:'.$nameArray[0].':'.$nameArray[1]);
            if (empty($data)) {
                return $default;
            }
            $value = $data;
        } else {
            $data = KeyboardNameConfigModel::where(['group' => $nameArray[0], 'name' => $nameArray[1]])->first();
            if (empty($data)) {
                return $default;
            }
            $value = $data->value;
            cache()->put('keyboard_name:'.$data->group.':'.$data->name, $data->value, now()->addWeek());
        }
    }

    return $value;
}

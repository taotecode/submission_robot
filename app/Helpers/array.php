<?php

function containsSubarray($array, $subarray, $key = null, $return = 'bool'): bool|int|string
{
    foreach ($array as $index => $arr) {
        if (! empty($key)) {
            if (array_key_exists($key, $arr)) {
                if (in_array($arr[$key], $subarray)) {
                    if ($return === 'bool') {
                        return true;
                    } else {
                        return $index;
                    }
                }
            }
        } else {
            if (in_array($arr, $subarray)) {
                if ($return === 'bool') {
                    return true;
                } else {
                    return $index;
                }
            }
        }
    }

    return false;
}

<?php

function get_posted_by($data)
{
    if (! empty($data['first_name']) && ! empty($data['last_name'])) {
        return $data['first_name'].' '.$data['last_name'];
    }
    if (! empty($data['username'])) {
        return $data['username'];
    }

    return '未知';
}

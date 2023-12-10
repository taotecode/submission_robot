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

function escapeMarkdownV2($text)
{
    $escapeChars = str_split('_*[]()~`>#+-=|{}.!');
    foreach ($escapeChars as $char) {
        $text = str_replace($char, '\\'.$char, $text);
    }

    return $text;
}

/**
 * telegram消息预处理
 *
 * @return mixed
 */
function telegram_message_pre_process($text, $entities)
{
    foreach ($entities as $entity) {
        $offset = $entity['offset'];
        $length = $entity['length'];
        $type = $entity['type'];
        // 提取出这个实体对应的文本
        $entityText = substr($text, $offset, $length);
        switch ($type) {
            case 'url':
                // 将 URL 包装在 Markdown 的链接语法中
                $text = str_replace($entityText, "[{$entityText}]({$entityText})", $text);
                break;
            case 'text_mention':
                // 处理 @username
                $text = str_replace($entityText, "[{$entityText}](tg://user?id={$entity->user()->id()})", $text);
                break;
            case 'hashtag':
                // 处理 #tag
                $escapedHashtag = str_replace('#', '\#', $entityText);
                $text = substr_replace($text, $escapedHashtag, $offset, $length);
                //$text = str_replace($entityText, "\\{$entityText}", $text);
                break;
                // 其他你想要处理的类型...
        }
    }

    return escapeMarkdownV2($text);
}

<?php

use Fukuball\Jieba\Finalseg;
use Fukuball\Jieba\Jieba;
use Illuminate\Support\Str;
use lucadevelop\TelegramEntitiesDecoder\EntityDecoder;

function get_posted_by($data)
{
    if (! empty($data['first_name']) && ! empty($data['last_name'])) {
        return $data['first_name'].' '.$data['last_name'];
    }
    if (! empty($data['first_name'])) {
        return $data['first_name'];
    }
    if (! empty($data['last_name'])) {
        return $data['last_name'];
    }
    if (! empty($data['username'])) {
        return $data['username'];
    }

    if (! empty($data['title'])) {
        return $data['title'];
    }

    if (! empty($data['id'])) {
        return $data['id'];
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

/**
 * 快速调用分词
 *
 * @return array
 */
function quickCut($text, $lexicon_path)
{
    ini_set('memory_limit', '1024M');
    Jieba::init();
    Finalseg::init();
    if (! empty($lexicon_path)) {
        Jieba::loadUserDict($lexicon_path);
    }

    return Jieba::cut($text);
}

function get_text_title($string)
{
    $replaced = Str::replace('\r\n', PHP_EOL, $string);
    $limitedString = Str::limit($replaced);

    return Str::before($limitedString, PHP_EOL);
}

function get_file_url($file_id)
{
    $telegram = new \Telegram\Bot\Api(config('services.telegram.bot_token'));
    $file = $telegram->getFile(['file_id' => $file_id]);

    return 'https://api.telegram.org/file/bot'.config('services.telegram.bot_token').'/'.$file_id;
}

function preprocessMessageText(mixed $message, object $botInfo): array|string
{
    $messageCacheData = $message->toArray();

    if (! empty($messageCacheData['text']) && $botInfo->is_message_text_preprocessing == 1) {
        $entity_decoder = new EntityDecoder('HTML');
        try {
            $messageCacheDataTmp = is_object($message) ? $message : collect($message);
            $messageCacheData['text'] = $entity_decoder->decode($messageCacheDataTmp);
        } catch (Exception $e) {
            Log::error('消息文字预处理失败：'.$e->getMessage());

            return 'error';
        }
    }

    return $messageCacheData;
}

function preprocessMessageCaption(mixed $message, object $botInfo): array|string
{
    $messageCacheData = $message->toArray();

    if (! empty($messageCacheData['caption']) && $botInfo->is_message_text_preprocessing == 1) {
        $entity_decoder = new EntityDecoder('HTML');
        try {
            $messageCacheDataTmp = is_object($message) ? $message : collect($message);
            $messageCacheData['caption'] = $entity_decoder->decode($messageCacheDataTmp);
        } catch (Exception $e) {
            Log::error('消息标题预处理失败：'.$e->getMessage());

            return 'error';
        }
    }

    return $messageCacheData;
}

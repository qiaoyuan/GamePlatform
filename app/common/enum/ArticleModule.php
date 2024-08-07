<?php

namespace app\common\enum;

enum ArticleModule: string
{
    case Article = 'article';
    case Help = 'help';
    case Notice = 'notice';

    public static function getMap(): array
    {
        return [
            self::Article->value => '文章',
            self::Help->value => '帮助中心',
            self::Notice->value => '公告',
        ];
    }
}

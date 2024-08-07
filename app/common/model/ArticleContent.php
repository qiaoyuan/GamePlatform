<?php

namespace app\common\model;

/**
 * @property int $article_id 文章ID
 * @property string $content 具体信息
 * @property string $banner 轮播信息
 * @property string $file 附件
 */
class ArticleContent extends Base
{
    protected $table = 'article_content';
    protected $pk = '';
    protected $autoWriteTimestamp = false;
    protected $field = [
        'article_id',
        'content',
        'banner',
        'file',
    ];
    protected $type = [
        'file' => 'array',
        'banner' => 'array'
    ];
}

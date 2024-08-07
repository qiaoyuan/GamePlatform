<?php

namespace app\common\validate;

class Article extends Base
{
    protected $rule = [
        'title|标题' => ['require'],
        'article_category_id|分类' => ['require'],
        'thumb|封面图' => ['requireIf:module,article'],
        'status|状态' => ['require'],
        'desc|简介' => [],
        'sort|排序' => ['integer'],
        'is_index|是否首页推荐' => ['boolean'],
    ];

    protected $message = [];

    protected $scene = [
        'add' => ['title', 'article_category_id', 'thumb', 'status', 'desc', 'sort', 'is_index'],
        'edit' => ['title', 'article_category_id', 'thumb', 'status', 'desc', 'sort', 'is_index', 'id'],
    ];
}

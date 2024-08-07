<?php

namespace app\common\validate;

class ArticleCategory extends Base
{
    protected $rule = [
        'title|分类名称' => ['require'],
    ];

    protected $message = [];

    protected $scene = [
        'add' => ['title'],
        'edit' => ['title', 'id'],
    ];
}

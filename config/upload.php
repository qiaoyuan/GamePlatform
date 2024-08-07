<?php
return [
    'bucket' => 'bz-bnt',
    'file_bucket' => '',
    'user' => [
        'img' => [
            'size' => 80 * 1024 * 1024,
            'ext' => 'jpeg,jpg,png,gif,mp4',
        ],
        'url' => fullDomain('api') . '/user/upload/index',
        'prefix' => env('upload.user', 'u')
    ],
    'admin' => [
        'img' => [
            'size' => 80 * 1024 * 1024,
            'ext' => 'jpeg,jpg,png,gif,mp4',
        ],
        'url' => fullDomain('api') . '/admin/upload/index',
        'prefix' => env('upload.admin', 's')
    ],
    'empty' => [
        '380_244' => '/empty.png'
    ]
];

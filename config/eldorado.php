<?php
// Eldorado 平台 API 配置
// Token 端点：POST /api/authentication/seller/token，body JSON {clientId, clientSecret}
// 改价端点：PUT /api/v1/currency-management/me/offers/{offerId}/change-price，Bearer token 鉴权
return [
    'base_uri'        => env('ELDORADO_BASE_URI', 'https://www.eldorado.gg'),
    'timeout'         => 15,
    // access_token 缓存时长（秒），略短于平台返回的 expiresIn（约 899s），预留安全余量
    'token_cache_ttl' => 800,
];

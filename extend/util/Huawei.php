<?php

namespace util;

use HuaweiCloud\SDK\Cdn\V1\Model\CreateRefreshTasksRequest;
use HuaweiCloud\SDK\Cdn\V1\Model\RefreshTaskRequest;
use HuaweiCloud\SDK\Cdn\V1\Model\RefreshTaskRequestBody;
use HuaweiCloud\SDK\Cdn\V1\CdnClient;
use HuaweiCloud\SDK\Core\Auth\GlobalCredentials;
use HuaweiCloud\SDK\Core\Http\HttpConfig;
use think\facade\App;

class Huawei
{

    public static function instance()
    {
        $instance = mData('huawei_instance');
        if (!$instance) {
            $ak = "CQZHOOXSCIQZNGSXH7GG";
            $sk = "7635YFctSZjZbuW3HD2jfSbp3AIyRIglXNCGehHz";
            $endpoint = "https://cdn.myhuaweicloud.com";
            $domainId = "31f1647f0e3c49c2a0527bbd37ebfab4";
            $credentials = new GlobalCredentials($ak,$sk,$domainId);
            $config = HttpConfig::getDefaultConfig();
            $config->setIgnoreSslVerification(true);
            $instance = CdnClient::newBuilder()
                ->withHttpConfig($config)
                ->withEndpoint($endpoint)
                ->withCredentials($credentials)
                ->build();
        }
        return $instance;
    }

    public static function refresh(array $urls)
    {
        $instance = self::instance();
        $request = new CreateRefreshTasksRequest();

        $r = new RefreshTaskRequest();
        $body = new RefreshTaskRequestBody();
        $body->setUrls($urls);
        $r->setRefreshTask($body);
        $request->setBody($r);
        return $instance->CreateRefreshTasks($request);
    }
}

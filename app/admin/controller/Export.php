<?php

namespace app\admin\controller;

use app\admin\BaseController;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use think\exception\HttpResponseException;
use think\helper\Str;

class Export extends BaseController
{
    public function run()
    {
        $api = input('_api');
        [$controller, $action] = explode('/', trim($api, '/'));
        $controller = sprintf('\\app\\admin\\controller\\%s', Str::studly($controller));
        $controller = new $controller($this->app);
        $columns = $controller->columns();
        try {
            $controller->$action();
        } catch(\Exception $e) {
            if ($e instanceof HttpResponseException) {
                $response = $e->getResponse();
                $list = $response->getData()['data']['list'];
                (new \util\Export())->writeHeader($columns)
                    ->writeData(
                        array_column($columns, 'v'),
                        $list,
                        $this->columnSearchList(new Client(['base_uri' => fullDomain('api')]), $columns)
                    )->run();
                return;
            }
        }
        $this->error('导出失败');
    }

    private function columnSearchList(Client $httpClient, array $columnList): array
    {
        if (empty($columnList)) {
            return [];
        }
        $replaceList = [];
        $existed = [];
        foreach ($columnList as $v) {
            if (!isset($v['replace']) || !$v['replace'] || empty($v['searchList'])) {
                continue;
            }
            $list = $v['searchList'];
            if (is_string($v['searchList']) || (is_array($v['searchList']) && !empty($v['searchList']['url']))) {
                $url = is_string($v['searchList']) ? $v['searchList'] : $v['searchList']['url'];
                // 是否已经请求过了
                if (!empty($existed[$url])) {
                    $list = $existed[$url];
                } else {
                    try {
                        $res = $httpClient->post('admin/' . trim($url, '/'), [
                            'headers' => [
                                'Content-Type' => 'application/json',
                                'Authorization' => $this->request->header(config('jwt.field')),
                            ],
                        ]);
                        $columnInfo = json_decode($res->getBody()->getContents(), true);
                    } catch (GuzzleException $e) {
                        trace($e->getMessage(), 'columnSearchList');
                        continue;
                    }
                    if (!isset($columnInfo['code']) || $columnInfo['code'] != 0) {
                        trace(json_encode($columnInfo), 'columnSearchList');
                        continue;
                    }
                    $list = $columnInfo['data']['list'] ?? [];
                    $existed[$url] = $list;
                }
            }
            $replaceList[$v['v']] = is_array($list) ? $list : [];
        }
        return $replaceList;
    }
}

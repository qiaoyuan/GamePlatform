<?php

namespace app\common\command;

use app\common\model\YtPing;
use app\common\model\YtServices;
use app\common\model\YtWebsite;
use app\common\model\YtWebsource;
use GuzzleHttp\Client;
use think\facade\Db;
use think\helper\Arr;
use think\helper\Str;

class Domain extends Base
{

    private string $key = '491ad2f2bf495c6cdd70a2760d5c2857';

    private Client $client;

    protected function configure()
    {
        $this->setConfigure('domain');
        $this->client = new Client([
            'timeout' => 10,
            'verify' => false,
            'base_uri' => 'https://api.boce.com'
        ]);
    }

    public function index()
    {
        $channelList = [YtServices::CHANNEL_LT];
        foreach ($channelList as $channel) {
            $sites = YtWebsite::where('direct', 1)
                ->where('is_check_ip', 1)
                ->where('pid', 0)
                ->whereRaw('is_need_update_ip & ' . (1 << ($channel - 1)) . ' > 0')
                ->select();
            foreach ($sites as $site) {
                $domains = [];
                if (Str::contains($site->url, 'mirror')) {
                    $domains[] = parse_url($site->url, PHP_URL_HOST);
                } else {
                    $sourceList = YtWebsource::where('siteid', $site->id)->column('source');
                    foreach ($sourceList as $s) {
                        $domains[] = parse_url($s, PHP_URL_HOST);
                    }
                    $domains = array_values(array_unique($domains));
                }
                $nodeIds = $this->getNodeIds($channel, $site->check_ip_node);
                if (!$nodeIds) {
                    continue;
                }
                foreach ($domains as $domain) {
                    if ($domain) {
                        $exist = YtPing::where('domain', $domain)
                            ->where('created_at', '>', date('Y-m-d H:i:s', strtotime('-1 day')))
                            ->where('status', 1)
                            ->where('result', '<>', '')
                            ->where('siteid', '<>', $site->id)
                            ->order('id', 'DESC')
                            ->find();
                        if ($exist) {
                            $this->mLog(sprintf('%d %s复用已经存在的结果%d', $site->id , $domain, $exist->id));
                            $site->is_need_update_ip = array_diff($site->is_need_update_ip, [$channel])
                                ? array_values(array_diff($site->is_need_update_ip, [$channel]))
                                : [];
                            $site->handleIpResult($exist, json_decode($exist->getData('result'), true));
                            continue;
                        }
                        try {
                            $res = $this->client->get('/v3/task/create/ping?key=' . $this->key . '&node_ids=' . implode(',', $nodeIds) . '&host=' . $domain);
                            $site->is_need_update_ip = array_diff($site->is_need_update_ip, [$channel])
                                ? array_values(array_diff($site->is_need_update_ip, [$channel]))
                                : [];
                            $site->save();
                            $content = (string)$res->getBody();
                            $this->mLog($domain);
                            $this->mLog($content);
                            $json = json_decode($content, true);
                            if ($json) {
                                if ($json['error_code'] == 0) {
                                    YtPing::create([
                                        'siteid' => $site->id,
                                        'domain' => $domain,
                                        'channel' => $channel,
                                        'task_id' => $json['data']['id'] ?? '',
                                        'result' => '',
                                        'status' => 0,
                                    ]);
                                } else {
                                    $this->mLog($domain . '创建任务失败：' . $json['error'] ?? '');
                                }
                            } else {
                                $this->mLog($domain . '创建任务失败：没有返回');
                            }
                        } catch (\Exception $e) {
                            $this->mLog('创建任务：' . $e->getMessage());
                            $this->mLog('创建任务：' . $e->getTraceAsString());
                        }
                    }
                }
            }
        }
    }

    private function getNodeIds(int $channel, int $count = 0): array
    {
        $count = $count - 1;
        $gz = [
            1 => 108,
            2 => 110,
            3 => 80980,
        ];
        $list = fastCache('node_list', function () {
            $res = $this->client->get('/v3/node/list?key=' . $this->key);
            $content = (string)$res->getBody();
            $this->mLog('  getNode   ' . $content);
            $json = json_decode($content, true);
            return $json['data']['list'] ?? [];
        }, 86400);
        if ($list) {
            $list = array_column(array_filter($list, function ($item) use ($channel) {
                return $item['isp_name'] == YtServices::$channelMap[$channel] && !in_array($item['node_name'], [
                    '新疆', '西藏', '青海', '云南', '黑龙江', '甘肃', '内蒙古', '吉林', '宁夏', '贵州'
                    ]);
            }), 'id');
            if ($count > 0 && count($list) > $count) {
                $list = array_values(Arr::only($list, array_rand($list, $count)));
            }
            if (!in_array($gz[$channel], $list)) {
                $list[] = $gz[$channel];
            }
            return $list;
        }
        return [];
    }

    public function getResult()
    {
        $list = YtPing::where('status', 0)
            ->where('created_at', '<=', date('Y-m-d H:i:s', time() - 59))
            ->where('created_at', '>=', date('Y-m-d H:i:s', time() - 120))
            ->with(['site'])
            ->select();
        foreach ($list as $ping) {
            try {
                $res = $this->client->get('https://api.boce.com/v3/task/ping/' . $ping->task_id . '?key=' . $this->key);
                $content = (string)$res->getBody();
                if ($content) {
                    $json = json_decode($content, true);
                    if (empty($json['list'])) {
                        $this->mLog('  getResultFail   ' . $content);
                        continue;
                    }
                    foreach ($json['list'] as &$row) {
                        $row = Arr::only($row, ['ip_isp', 'ip_region', 'ip', 'node_name', 'node_id', 'origin_ip']);
                    }
                    $ping->result = json_encode($json, 320);
                    $this->mLog('  getResult   ' . $ping->result);
                    $ping->status = 1;
                    $ping->save();
                    $ping->site->handleIpResult($ping, $json);
                }
            } catch (\Exception $e) {
                $this->mLog('getResult:' . $e->getMessage());
                $this->mLog('getResult:' . $e->getTraceAsString());
            }
        }
    }

    public function node()
    {
        var_dump(count($this->getNodeIds(YtServices::CHANNEL_LT, 3)));
        var_dump(implode(',', $this->getNodeIds(YtServices::CHANNEL_LT, 5)));
    }

    public function test()
    {
        $sites = YtWebsite::where('direct', 1)
            ->where('is_check_ip', 1)
            ->select();
        foreach ($sites as $item) {
            $item->handOutIp(2);
        }
    }

    public function refresh()
    {
        $r = YtWebsite::where([
            ['is_check_ip', '=', 1],
            ['match_channel', '=', '01000'],
            ['refresh_at', '<', date('Y-m-d H:i:s', time() - 86400 * 10)],
            ['pid', '=', 0]
        ])->update(['is_need_update_ip' => 2]);
        $this->mLog('更新数量' . $r);
    }
}

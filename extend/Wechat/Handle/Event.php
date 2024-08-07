<?php
namespace Wechat\Handle;

use app\common\model\MpEvent;
use EasyWeChat\Kernel\Contracts\EventHandlerInterface;

class Event implements EventHandlerInterface
{
    /**
     * @inheritDoc
     */
    public function handle($payload = null)
    {
        $payload['EventKey'] = str_replace('qrscene_', '', $payload['EventKey'] ?? '');
        $data = [
            'openid' => $payload['FromUserName'],
            'event' => $payload['Event'],
            'param' => $payload['EventKey'] ?? ''
        ];
        if (in_array($data['event'], ['subscribe', 'SCAN', 'unsubscribe'])) {
            MpEvent::create($data);
        }
        if (method_exists($this, $data['event'])) {
            $event = $data['event'];
            $result = $this->$event($data);
        } else {
            $result = $this->other($data);
        }
        trace($result, 'mp_return');
        return $result;
    }

    public function subscribe($data)
    {
        return true;
    }

    public function SCAN($data)
    {
        return '';
    }

    public function other($data)
    {
        return true;
    }
}

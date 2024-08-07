<?php
namespace Wechat\Handle;

use app\common\model\MpMessage;
use EasyWeChat\Kernel\Contracts\EventHandlerInterface;
use EasyWeChat\Kernel\Messages\Text;
use think\helper\Arr;

class Message implements EventHandlerInterface
{
    /**
     * @inheritDoc
     */
    public function handle($payload = null)
    {
        if (method_exists($this, $payload['MsgType'])) {
            $type = $payload['MsgType'];
            $result = $this->$type($payload);
        } else {
            $result = $this->other($payload);
        }
        MpMessage::create([
            'openid' => $payload['FromUserName'],
            'receive' => Arr::except($payload, ['FromUserName', 'ToUserName', 'CreateTime']),
            'reply' => $result->getAttribute('content', ''),
            'type' => $payload['MsgType'],
        ]);
        return $result;
    }

    public function text($payload)
    {
        $content = $payload['Content'];
        return new Text('欢迎');
    }

    public function other($payload)
    {
        return new Text('谢谢');
    }
}

<?php

namespace Kaiheila\httpAPI;

use Swlib\Saber;

class SendMessage
{
    public $httpConn;

    public function __construct($token)
    {
        $this->httpConn = Saber::create([
            'base_uri' => "https://www.kaiheila.cn",
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bot ' . $token
            ]
        ]);
    }

    public function sendText($content, $target_id, $is_quote = false, $quote = '', $type = 1)
    {
        if ($is_quote) {
            $reply = array(
                'type' => $type,
                'target_id' => $target_id,
                'content' => $content,
                'quote' => $quote
            );
        } else {
            $reply = array(
                'type' => $type,
                'target_id' => $target_id,
                'content' => $content
            );
        }
        try {
            $this->httpConn->post('/api/v3/message/create', json_encode($reply));
        } catch (\Swlib\Http\Exception\ClientException $e) {
            echo date('[Y-m-d H:i:s]') . " [ERROR] 发送开黑啦消息超时\n";
        }
    }
}
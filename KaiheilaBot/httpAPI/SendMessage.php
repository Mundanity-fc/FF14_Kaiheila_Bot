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
            'tiemout' => 3,
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
        $this->httpConn->post('/api/v3/message/create', json_encode($reply));
    }
}
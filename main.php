<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config.php';

use kaiheila\api\base\Session;
use kaiheila\api\base\WebsocketSession;
use kaiheila\api\helpers\ApiHelper;
use Swlib\Saber;


\Co\run(function () {
    $session = new WebsocketSession(TOKEN, BASE_URL, __DIR__ . '/session.pid');
    // 侦听所有的接收frame事件
    $session->on(Session::EVENT_RECEIVE_FRAME, function ($frame) use ($session) {
        $session->log('receiveFrame', '收到Frame');
    });
    //侦听所有的频道事件
    $session->on('GROUP*', function ($frame) use ($session) {
        $session->log('receiveGroup', '收到频道消息');
        $data = $frame->d["content"];
        $sender = $frame->d["extra"]["author"]["username"];
        $channel = $frame->d["extra"]["channel_name"];
        echo "收到了来自" . $channel . "的" . $sender . "发来的消息：" . $data . "\n";
        $saber = Saber::create([
            'base_uri' => "https://www.kaiheila.cn",
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bot ' . TOKEN
            ]
        ]);
        $rep = array('target_id' => '3704577011642133', 'content' => '收到！');
        $rep = json_encode($rep);
        echo $saber->post('/api/v3/message/create', $rep);
        if (str_starts_with($data, '/'))
            echo "有效指令\n";
        else
            echo "无效指令\n";
    });
    //只侦听频道内的文字消息，并回复
    $session->on('GROUP_1', function ($frame) use ($session) {
        $session->log('receiveMsg', $frame);
        $client = new ApiHelper('/api/v3/channel/message', TOKEN, BASE_URL);
        $ret = $client->setBody([
            'channel_id' => $frame->d['target_id'],
            'content' => '恭喜你完成整个的对接',
            'object_name' => 1,
        ])->send(ApiHelper::POST);
    });
    $session->start();
});
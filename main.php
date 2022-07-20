<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config.php';
require __DIR__ . '/KaiheilaBot/commandInterpreter/TaskProcessor.php';

use kaiheila\api\base\WebsocketSession;
use Kaiheila\httpAPI\SendMessage;
use KaiheilaBot\Interpreter\TaskProcessor;

function mainWork()
{
    $loop = false;

    // 构造数据库链接对象
    $dbConn_opts = 'host=' . DbHost . ' port=' . DbPort . ' dbname=' . DbName . ' user=' . DbUsername . ' password=' . DbPassword;
    $dbConn = pg_connect($dbConn_opts);

    // 构造 http 通讯对象
    $httpAPI = new SendMessage(TOKEN);
    $processor = new TaskProcessor($dbConn, $httpAPI, XIVAPIPrivateKey);

    // 构造 Websocket 通讯对象
    $session = new WebsocketSession(TOKEN, BASE_URL, __DIR__ . '/session.pid');

    //修改 log 文件位置 （BaseObject内容）
    $session->logFile = __DIR__ . '/FF14BotMsg.log';

    // Websocket 消息监听
    $session->on('GROUP*', function ($frame) use ($session, $processor) {
        $session->log('receiveGroup', '收到频道消息');
        $messageData = $frame->d['content'];
        $messageInfo = array(
            'channelID' => $frame->d['target_id'],
            'messageID' => $frame->d['msg_id'],
            'senderID' => $frame->d['author_id'],
            'serverID' => $frame->d['extra']['guild_id'],
            'channelName' => $frame->d['extra']['channel_name'],
            'senderName' => $frame->d['extra']['author']['username']

        );
        if (str_starts_with($messageData, '/')) {
            $processor->run($messageData, $messageInfo);
        }

    });

    $session->start();
}

\Co\run(function () {
    mainWork();
});
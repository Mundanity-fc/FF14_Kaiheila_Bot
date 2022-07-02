<?php

namespace KaiheilaBot\Interpreter;
require __DIR__ . '/../httpAPI/SendMessage.php';

use Kaiheila\httpAPI\SendMessage;

class TaskProcessor
{
    public bool $loop;
    public array $commandList = [];
    public array $messageInfo = [];
    public $dbConn;
    public SendMessage $httpAPI;

    public function __construct($dbConn, $httpAPI)
    {
        $this->dbConn = $dbConn;
        $this->httpAPI = $httpAPI;
    }

    function run($command, $messageInfo): void
    {
        $this->commandSplit($command);
        $this->messageInfo = $messageInfo;
        $this->taskChecker();
    }

    public function commandSplit($commandSplit): void
    {
        $this->commandList = explode(' ', $commandSplit, 2);
        $this->commandList[0] = substr($this->commandList[0], 1);
    }

    function taskChecker(): void
    {
        if ($this->commandList[0] === '任务') {
            $this->questSearch();
        } else {
            $this->httpAPI->sendText('错误指令', $this->messageInfo['channelID'], true, $this->messageInfo['messageID']);
        }
    }

    function questSearch(): void
    {
        if (count($this->commandList) !== 2) {
            $this->httpAPI->sendText('指令参数有误，请确认后重新输入', $this->messageInfo['channelID'], true, $this->messageInfo['messageID']);
        } else {
            $searchSQL = 'select * from questlist where questlist.quest = \'' . $this->commandList[1] . '\'';
            $result = pg_query($this->dbConn, $searchSQL);
            if (!$result) {
                $this->httpAPI->sendText('数据库出错', $this->messageInfo['channelID'], true, $this->messageInfo['messageID']);
            }
            $questList = pg_fetch_assoc($result);
            if (!$questList) {
                $this->httpAPI->sendText('没有结果，请确保输入的任务名正确！', $this->messageInfo['channelID'], true, $this->messageInfo['messageID']);
            } else {
                $this->httpAPI->sendText('没写呢', $this->messageInfo['channelID'], true, $this->messageInfo['messageID']);
            }
        }
    }
}
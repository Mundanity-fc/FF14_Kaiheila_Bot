<?php

namespace KaiheilaBot\commandInterpreter;
require __DIR__ . '/../httpAPI/SendMessage.php';
require __DIR__ . '/module/QuestSearch.php';
require __DIR__ . '/../databaseManager/mysql.php';
require __DIR__ . '/../databaseManager/postgresql.php';

use Kaiheila\databaseManager\mysql;
use Kaiheila\databaseManager\postgresql;
use Kaiheila\httpAPI\SendMessage;
use KaiheilaBot\commandInterpreter\module\QuestSearch;

class TaskProcessor
{
    /*
     * 类属性
     * 包含总体需调用到的对象等
     * */

    //循环判定，目前未想好功能，可能最后利用循环指令栈代替
    private bool $loop;
    //指令列表，对每条指令进行划分
    private array $commandList = [];
    //频道信息属性数组，其中包含了信息的发送人及频道id等信息
    private array $messageInfo = [];
    //数据库连接对象
    private $db;
    //Kook服务器通讯对象
    private SendMessage $httpAPI;

    //
    private $QuestSearch;

    /*
     * 构造函数
     * 传递数据库对象、Kook通讯对象与XIV开发者密钥
     * */
    public function __construct($dbSetting, $httpAPI, $XIVAPIKey)
    {
        if ($dbSetting[0] === 'PostgreSQL') {
            $this->db = new postgresql($dbSetting[1]);
        }
        if ($dbSetting[0] === 'MySQL') {
            $this->db = new mysql($dbSetting[1]);
        }
        $this->httpAPI = $httpAPI;
        $this->QuestSearch = new QuestSearch($this->db, $XIVAPIKey);
    }

    /*
     * 运行函数
     * 为该类的总函数，进行指令的处理
     * */
    public function run($command, $messageInfo): void
    {
        $this->commandSplit($command);
        $this->messageInfo = $messageInfo;
        $this->taskChecker();
    }

    /*
     * 指令参数分割函数
     * 负责对指令进行参数拆分，以第一个空格未分割，划分为[指令]与[参数]两部分，其中[参数]部分存在可以再分的情况，需要在次级函数中进行再次分割
     * */
    private function commandSplit($commandSplit): void
    {
        $this->commandList = explode(' ', $commandSplit, 2);
        $this->commandList[0] = substr($this->commandList[0], 1);

        //去除诸如 “/任务 ”，这样的错误指令
        if (count($this->commandList) === 2) {
            if ($this->commandList[1] === "") {
                array_pop($this->commandList);
            }
        }
    }

    /*
     * 指令检测函数
     * 负责对指令进行循论查找，找不到则返回错误指令报告
     * */
    private function taskChecker(): void
    {
        if ($this->commandList[0] === '任务') {
            $data = $this->QuestSearch->run($this->commandList, $this->messageInfo);
        } else {
            $msg = '错误指令';
            $target_id = $this->messageInfo['channelID'];
            $is_quote = true;
            $quote = $this->messageInfo['messageID'];
            $type = 1;
            $data = array($msg, $target_id, $is_quote, $quote, $type);
        }
        $this->httpAPI->sendText($data[0], $data[1], $data[2], $data[3], $data[4]);
    }
}
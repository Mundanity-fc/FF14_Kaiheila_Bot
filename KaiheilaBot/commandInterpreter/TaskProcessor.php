<?php

namespace KaiheilaBot\commandInterpreter;
require __DIR__ . '/../httpAPI/SendMessage.php';
require __DIR__ . '/module/QuestSearch.php';
require __DIR__ . '/module/TextTrainslate.php';
require __DIR__ . '/module/Helper.php';
require __DIR__ . '/../databaseManager/mysql.php';
require __DIR__ . '/../databaseManager/postgresql.php';

use Kaiheila\databaseManager\mysql;
use Kaiheila\databaseManager\postgresql;
use Kaiheila\httpAPI\SendMessage;
use KaiheilaBot\commandInterpreter\module\Helper;
use KaiheilaBot\commandInterpreter\module\QuestSearch;
use KaiheilaBot\commandInterpreter\module\TextTrainslate;

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
    //机器人状态
    private array $status = [];
    //任务检索指令对象
    private $QuestSearch;
    //文本翻译指令对象
    private $TextTrainslate;
    //指令帮助对象
    private $Healper;

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
        $this->QuestSearch = new QuestSearch($this->db);
        $this->QuestSearch->getConfig($XIVAPIKey);
        $this->TextTrainslate = new TextTrainslate($this->db);
        $this->Healper = new Helper($this->db);
        $this->status = array(
            'start' => date('c'),
            'commandCount' => 0,
            'correctCount' => 0);
    }

    /*
     * 运行函数
     * 为该类的总函数，进行指令的处理
     * */
    public function run($command, $messageInfo): void
    {
        ++$this->status['commandCount'];
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
        if ((count($this->commandList) === 2) && $this->commandList[1] === "") {
            array_pop($this->commandList);
        }
    }

    /*
     * 状态查询函数
     * 查询机器人的运行时长与指令处理数量等信息，未来可再次拓展
     * */
    private function getStatus(): array
    {
        $time_diff = date_diff(date_create($this->status['start']), date_create(date('c')));
        $timeString1 = '机器人本次启动于：' . substr($this->status['start'], 0, 10) . ' ' . substr($this->status['start'], 11, 8) . "\n";
        $timeString2 = '至今已运行了：';
        if ($time_diff->y !== 0) {
            $timeString2 .= $time_diff->y . '年';
        }
        if ($time_diff->m !== 0) {
            $timeString2 .= $time_diff->m . '月';
        }
        if ($time_diff->d !== 0) {
            $timeString2 .= $time_diff->d . '天';
        }
        if ($time_diff->h !== 0) {
            $timeString2 .= $time_diff->h . '小时';
        }
        if ($time_diff->i !== 0) {
            $timeString2 .= $time_diff->i . '分钟';
        }
        if ($time_diff->s !== 0) {
            $timeString2 .= $time_diff->s . '秒';
        }
        $timeString2 .= "\n";
        $timeString3 = '目前，机器人已经响应了 ' . $this->status['commandCount'] . ' 条指令，其中正确执行的有效指令有 ' . $this->status['correctCount'] . ' 条';
        $msg = $timeString1 . $timeString2 . $timeString3;
        $target_id = $this->messageInfo['channelID'];
        $is_quote = true;
        $quote = $this->messageInfo['messageID'];
        $type = 1;
        return array($msg, $target_id, $is_quote, $quote, $type);
    }

    /*
     * 指令检测函数
     * 负责对指令进行循论查找，找不到则返回错误指令报告
     * */
    private function taskChecker(): void
    {
        switch ($this->commandList[0]) {
            case '任务':
                ++$this->status['correctCount'];
                $data = $this->QuestSearch->run($this->commandList, $this->messageInfo);
                break;
            case '状态':
                ++$this->status['correctCount'];
                $data = $this->getStatus();
                break;
            case '物品':
                //Code here 2
                ++$this->status['correctCount'];
                break;
            case '价格':
                //Code here 3
                ++$this->status['correctCount'];
                break;
            case '翻译':
                ++$this->status['correctCount'];
                $data = $this->TextTrainslate->run($this->commandList, $this->messageInfo);
                break;
            case '帮助':
                ++$this->status['correctCount'];
                $data = $this->Healper->run($this->commandList, $this->messageInfo);
                break;
            default:
                $msg = '错误指令';
                $target_id = $this->messageInfo['channelID'];
                $is_quote = true;
                $quote = $this->messageInfo['messageID'];
                $type = 1;
                $data = array($msg, $target_id, $is_quote, $quote, $type);
                break;
        }
        $this->httpAPI->sendText($data[0], $data[1], $data[2], $data[3], $data[4]);
    }
}
<?php

use Swlib\Saber;

class taskProcessor
{
    public bool $loop;
    public array $commandList = [];
    public $dbConn;
    public Saber $httpConn;

    public function __construct($dbConn, $httpConn)
    {
        $this->dbConn = $dbConn;
        $this->httpConn = $httpConn;
    }

    function run($command): void
    {
        $this->commandSplit($command);
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
            echo '错误指令';
        }
    }

    function questSearch(): void
    {
        if (count($this->commandList) !== 2) {
            echo '指令参数有误，请确认后重新输入';
        } else {
            $searchSQL = 'select * from questlist where questlist.quest = \'' . $this->commandList[1] . '\'';
            $result = pg_query($this->dbConn, $searchSQL);
            if (!$result) {
                echo '数据库出错';
            }
            $questList = pg_fetch_assoc($result);
            if (!$questList) {
                echo '没有结果，请确保输入的任务名正确！';
            } else {
                echo '没写呢';
            }
        }
    }
}
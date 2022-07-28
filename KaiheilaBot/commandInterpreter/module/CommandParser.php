<?php

namespace KaiheilaBot\commandInterpreter\module;

class CommandParser
{
    //指令列表，对每条指令进行划分
    protected array $commandList = [];
    //频道信息属性数组，其中包含了信息的发送人及频道id等信息
    protected array $messageInfo = [];
    //数据库连接对象
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    protected function getCommand($command, $messageInfo): void
    {
        $this->commandList = $command;
        $this->messageInfo = $messageInfo;
    }
}
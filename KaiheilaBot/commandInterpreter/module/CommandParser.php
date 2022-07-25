<?php

namespace KaiheilaBot\commandInterpreter\module;

use Swlib\Saber;

class CommandParser
{
    //指令列表，对每条指令进行划分
    protected array $commandList = [];
    //频道信息属性数组，其中包含了信息的发送人及频道id等信息
    protected array $messageInfo = [];
    //数据库连接对象
    protected $db;
    //XIV查询对象
    protected $XIVAPI;
    //XIV开发者Key,由于使用Cafemaker镜像，暂时无用
    protected $XIVAPIKey;

    public function __construct($db, $XIVAPIKey)
    {
        $this->db = $db;
        $this->XIVAPI = Saber::create(['base_uri' => 'https://cafemaker.wakingsands.com']);
        $this->XIVAPIKey = $XIVAPIKey;
    }

    protected function getCommand($command, $messageInfo): void
    {
        $this->commandList = $command;
        $this->messageInfo = $messageInfo;
    }
}
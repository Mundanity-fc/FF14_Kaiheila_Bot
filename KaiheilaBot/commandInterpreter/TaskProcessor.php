<?php

namespace KaiheilaBot\Interpreter;
require __DIR__ . '/../httpAPI/SendMessage.php';
require __DIR__ . '/../cardMessage/importAllCard.php';

use Kaiheila\httpAPI\SendMessage;
use KaiheilaBot\cardMessage\Card;
use KaiheilaBot\cardMessage\Divider;
use KaiheilaBot\cardMessage\Image;
use KaiheilaBot\cardMessage\ImageText;
use KaiheilaBot\cardMessage\MultiColumnText;
use Swlib\Saber;

class TaskProcessor
{
    public bool $loop;
    public array $commandList = [];
    public array $messageInfo = [];
    public $dbConn;
    public SendMessage $httpAPI;
    public $XIVAPI;

    public function __construct($dbConn, $httpAPI)
    {
        $this->dbConn = $dbConn;
        $this->httpAPI = $httpAPI;
        $this->XIVAPI = Saber::create(['base_uri' => "https://cafemaker.wakingsands.com"]);
    }

    public function run($command, $messageInfo): void
    {
        $this->commandSplit($command);
        $this->messageInfo = $messageInfo;
        $this->taskChecker();
    }

    private function commandSplit($commandSplit): void
    {
        $this->commandList = explode(' ', $commandSplit, 2);
        $this->commandList[0] = substr($this->commandList[0], 1);
    }

    private function taskChecker(): void
    {
        if ($this->commandList[0] === '任务') {
            $this->questSearch();
        } else {
            $this->httpAPI->sendText('错误指令', $this->messageInfo['channelID'], true, $this->messageInfo['messageID']);
        }
    }

    private function questSearch(): void
    {
        if (count($this->commandList) !== 2) {
            $this->httpAPI->sendText('指令参数有误，请确认后重新输入', $this->messageInfo['channelID'], true, $this->messageInfo['messageID']);
        } else {
            $searchSQL = 'select questlist.id from questlist where questlist.quest = \'' . $this->commandList[1] . '\'';
            $result = pg_query($this->dbConn, $searchSQL);
            if (!$result) {
                $this->httpAPI->sendText('数据库出错', $this->messageInfo['channelID'], true, $this->messageInfo['messageID']);
            }
            $questList = pg_fetch_assoc($result);
            if (!$questList) {
                $this->httpAPI->sendText('没有结果，请确保输入的任务名正确！', $this->messageInfo['channelID'], true, $this->messageInfo['messageID']);
            } else {
                $msg = $this->processQuestInfo($this->getQuestInfo($questList['id']));
                $this->httpAPI->sendText($msg, $this->messageInfo['channelID'], true, $this->messageInfo['messageID'], 10);
            }
        }
    }

    private function getQuestInfo($questID): array
    {
        $data = $this->XIVAPI->get('/quest/' . $questID . '?columns=Name,Banner,TextData.ToDo,PlaceName.Name,GilReward,ExperiencePoints,ItemCountReward0,ItemCountReward1,ItemCountReward2,ItemCountReward3,ItemCountReward4,ItemCountReward5,ItemCountReward6,ItemReward0,ItemReward1,ItemReward2,ItemReward3,ItemReward4,ItemReward5,ItemReward6,Icon,IssuerStart.Name,TargetEnd.Name,ClassJobCategory0.Name,JournalGenre.JournalCategory.Name,JournalGenre.Name');
        $data = json_decode($data->body);
        $dataArray = array(
            'Name' => $data->Name,
            'Banner' => $data->Banner,
            'Icon' => $data->Icon,
            'Job' => $data->ClassJobCategory0->Name,
            'MainCategory' => $data->JournalGenre->JournalCategory->Name,
            'SubCategory' => $data->JournalGenre->Name,
            'StartPlace' => $data->PlaceName->Name,
            'StartNPC' => $data->IssuerStart->Name,
            'FinishNPC' => $data->TargetEnd->Name,
            'TodoList' => $data->TextData->ToDo,
            'Money' => $data->GilReward,
            'Exp' => $data->ExperiencePoints,
            'ItemNum0' => $data->ItemCountReward0,
            'ItemNum1' => $data->ItemCountReward1,
            'ItemNum2' => $data->ItemCountReward2,
            'ItemNum3' => $data->ItemCountReward3,
            'ItemNum4' => $data->ItemCountReward4,
            'ItemNum5' => $data->ItemCountReward5,
            'ItemNum6' => $data->ItemCountReward6,
            'Item0' => $data->ItemReward0,
            'Item1' => $data->ItemReward1,
            'Item2' => $data->ItemReward2,
            'Item3' => $data->ItemReward3,
            'Item4' => $data->ItemReward4,
            'Item5' => $data->ItemReward5,
            'Item6' => $data->ItemReward6,
        );
        return $dataArray;
    }

    private function processQuestInfo($questArray): string
    {
        $infoCard = new Card();
        $rewardCard = new Card();
        $todoCard = new Card();
        $questTitle = new ImageText('**[' . $questArray['Name'] . '](https://ff14.huijiwiki.com/wiki/任务:' . $questArray['Name'] . ')**', 'https://xivapi.com' . $questArray['Icon'], 'kmarkdown');
        $questBanner = new Image('https://xivapi.com' . $questArray['Banner']);
        $divider = new Divider();
        $detailInfo = new MultiColumnText();
        $detailInfo->insert("**主分类**\n[" . $questArray['MainCategory'] . '](https://ff14.huijiwiki.com/wiki/' . $questArray['MainCategory'] . ')', 'kmarkdown');
        $detailInfo->insert("**子分类**\n" . $questArray['SubCategory'], 'kmarkdown');
        $detailInfo->insert("**职业**\n" . $questArray['Job'], 'kmarkdown');
        $detailInfo->insert("**开始地区**\n" . $questArray['StartPlace'], 'kmarkdown');
        $detailInfo->insert("**开始NPC**\n" . $questArray['StartNPC'], 'kmarkdown');
        $detailInfo->insert("**结束NPC**\n" . $questArray['FinishNPC'], 'kmarkdown');
        $infoCard->insert($questTitle);
        $infoCard->insert($questBanner);
        $infoCard->insert($divider);
        $infoCard->insert($detailInfo);
        return json_encode(array($infoCard));
    }
}
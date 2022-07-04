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
use KaiheilaBot\cardMessage\PlainText;
use Swlib\Saber;

class TaskProcessor
{
    public bool $loop;
    public array $commandList = [];
    public array $messageInfo = [];
    public $dbConn;
    public SendMessage $httpAPI;
    public $XIVAPI;
    public $XIVAPIKey;

    public function __construct($dbConn, $httpAPI, $XIVAPIKey)
    {
        $this->dbConn = $dbConn;
        $this->httpAPI = $httpAPI;
        $this->XIVAPI = Saber::create(['base_uri' => 'https://cafemaker.wakingsands.com']);
        $this->XIVAPIKey = 'private_key=' . $XIVAPIKey;
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
        $questTitle = new ImageText('**[' . $questArray['Name'] . '](https://ff14.huijiwiki.com/wiki/任务:' . $questArray['Name'] . ')**', 'https://cafemaker.wakingsands.com' . $questArray['Icon'], 'kmarkdown');
        $infoCard->insert($questTitle);
        if ($questArray['Banner'] !== '') {
            $questBanner = new Image('https://xivapi.com' . $questArray['Banner']);
            $infoCard->insert($questBanner);
        }
        $divider = new Divider();
        $infoCard->insert($divider);
        $detailInfo = new MultiColumnText();
        if (is_null($questArray['MainCategory'])) {
            $detailInfo->insert("**主分类**\n", 'kmarkdown');
        } else {
            $detailInfo->insert("**主分类**\n[" . $questArray['MainCategory'] . '](https://ff14.huijiwiki.com/wiki/' . $questArray['MainCategory'] . ')', 'kmarkdown');
        }
        $detailInfo->insert("**子分类**\n" . $questArray['SubCategory'], 'kmarkdown');
        $detailInfo->insert("**职业**\n" . $questArray['Job'], 'kmarkdown');
        $detailInfo->insert("**开始地区**\n" . $questArray['StartPlace'], 'kmarkdown');
        $detailInfo->insert("**开始NPC**\n" . $questArray['StartNPC'], 'kmarkdown');
        $detailInfo->insert("**结束NPC**\n" . $questArray['FinishNPC'], 'kmarkdown');
        $infoCard->insert($detailInfo);
        $infoCard->insert($divider);
        $data = array($infoCard);
        if ($questArray['Money'] !== 0 || $questArray['Exp'] !== 0 || $questArray['ItemNum0'] !== 0) {
            $rewardCard = new Card();
            $rewardTitle = new PlainText('任务报酬', 'plain-text', 'header');
            $rewardCard->insert($rewardTitle);
            if ($questArray['Money']) {
                $moneyReward = new ImageText('x' . $questArray['Money'] . ' 金币', 'https://huiji-public.huijistatic.com/ff14/uploads/4/4a/065002.png');
                $rewardCard->insert($moneyReward);
            }
            if ($questArray['Exp']) {
                $expReward = new ImageText('x' . $questArray['Exp'] . ' 经验', 'https://huiji-public.huijistatic.com/ff14/uploads/b/b0/065001.png');
                $rewardCard->insert($expReward);
            }
            if ($questArray['ItemNum0']) {
                $Item0Name = $this->XIVAPI->get('/item/' . $questArray['Item0']->ID . '?columns=Name');
                $Item0Name = json_decode($Item0Name->body)->Name;
                $item0Reward = new ImageText('x' . $questArray['ItemNum0'] . ' [' . $Item0Name . '](https://ff14.huijiwiki.com/wiki/物品:' . $Item0Name . ')', 'https://cafemaker.wakingsands.com' . $questArray['Item0']->Icon, 'kmarkdown');
                $rewardCard->insert($item0Reward);
            }
            if ($questArray['ItemNum1']) {
                $Item1Name = $this->XIVAPI->get('/item/' . $questArray['Item1']->ID . '?columns=Name');
                $Item1Name = json_decode($Item1Name->body)->Name;
                $item1Reward = new ImageText('x' . $questArray['ItemNum1'] . ' [' . $Item1Name . '](https://ff14.huijiwiki.com/wiki/物品:' . $Item1Name . ')', 'https://cafemaker.wakingsands.com' . $questArray['Item1']->Icon, 'kmarkdown');
                $rewardCard->insert($item1Reward);
            }
            if ($questArray['ItemNum2']) {
                $Item2Name = $this->XIVAPI->get('/item/' . $questArray['Item2']->ID . '?columns=Name');
                $Item2Name = json_decode($Item2Name->body)->Name;
                $item2Reward = new ImageText('x' . $questArray['ItemNum2'] . ' [' . $Item2Name . '](https://ff14.huijiwiki.com/wiki/物品:' . $Item2Name . ')', 'https://cafemaker.wakingsands.com' . $questArray['Item2']->Icon, 'kmarkdown');
                $rewardCard->insert($item2Reward);
            }
            if ($questArray['ItemNum3']) {
                $Item3Name = $this->XIVAPI->get('/item/' . $questArray['Item3']->ID . '?columns=Name');
                $Item3Name = json_decode($Item3Name->body)->Name;
                $item3Reward = new ImageText('x' . $questArray['ItemNum3'] . ' [' . $Item3Name . '](https://ff14.huijiwiki.com/wiki/物品:' . $Item3Name . ')', 'https://cafemaker.wakingsands.com' . $questArray['Item3']->Icon, 'kmarkdown');
                $rewardCard->insert($item3Reward);
            }
            if ($questArray['ItemNum4']) {
                $Item4Name = $this->XIVAPI->get('/item/' . $questArray['Item4']->ID . '?columns=Name');
                $Item4Name = json_decode($Item4Name->body)->Name;
                $item4Reward = new ImageText('x' . $questArray['ItemNum4'] . ' [' . $Item4Name . '](https://ff14.huijiwiki.com/wiki/物品:' . $Item4Name . ')', 'https://cafemaker.wakingsands.com' . $questArray['Item4']->Icon, 'kmarkdown');
                $rewardCard->insert($item4Reward);
            }
            if ($questArray['ItemNum5']) {
                $Item5Name = $this->XIVAPI->get('/item/' . $questArray['Item5']->ID . '?columns=Name');
                $Item5Name = json_decode($Item5Name->body)->Name;
                $item5Reward = new ImageText('x' . $questArray['ItemNum5'] . ' [' . $Item5Name . '](https://ff14.huijiwiki.com/wiki/物品:' . $Item5Name . ')', 'https://cafemaker.wakingsands.com' . $questArray['Item5']->Icon, 'kmarkdown');
                $rewardCard->insert($item5Reward);
            }
            if ($questArray['ItemNum6']) {
                $Item6Name = $this->XIVAPI->get('/item/' . $questArray['Item6']->ID . '?columns=Name');
                $Item6Name = json_decode($Item6Name->body)->Name;
                $item6Reward = new ImageText('x' . $questArray['ItemNum6'] . ' [' . $Item6Name . '](https://ff14.huijiwiki.com/wiki/物品:' . $Item6Name . ')', 'https://cafemaker.wakingsands.com' . $questArray['Item6']->Icon, 'kmarkdown');
                $rewardCard->insert($item6Reward);
            }
            array_push($data, $rewardCard);
        }
        if (!is_null($questArray['TodoList'])) {
            $todoCard = new Card();
            $todoTitle = new PlainText('任务目的', 'plain-text', 'header');
            $todoCard->insert($todoTitle);
            foreach ($questArray['TodoList'] as $todo) {
                $TodoText = new PlainText('(spl)' . $todo->Text . '(spl)', 'kmarkdown');
                $todoCard->insert($TodoText);
            }
            array_push($data, $todoCard);
        }
        return json_encode($data);
    }
}
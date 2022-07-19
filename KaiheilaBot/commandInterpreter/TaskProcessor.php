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
    /*
     * 类属性
     * 包含总体需调用到的对象等
     * */

    //循环判定，目前未想好功能，可能最后利用循环指令栈代替
    public bool $loop;
    //指令列表，对每条指令进行划分
    public array $commandList = [];
    //频道信息属性数组，其中包含了信息的发送人及频道id等信息
    public array $messageInfo = [];
    //数据库连接对象
    public $dbConn;
    //Kook服务器通讯对象
    public SendMessage $httpAPI;
    //XIV查询对象
    public $XIVAPI;
    //XIV开发者Key,由于使用Cafemaker镜像，暂时无用
    public $XIVAPIKey;

    /*
     * 构造函数
     * 传递数据库对象、Kook通讯对象与XIV开发者密钥
     * */
    public function __construct($dbConn, $httpAPI, $XIVAPIKey)
    {
        $this->dbConn = $dbConn;
        $this->httpAPI = $httpAPI;
        $this->XIVAPI = Saber::create(['base_uri' => 'https://cafemaker.wakingsands.com']);
        $this->XIVAPIKey = 'private_key=' . $XIVAPIKey;
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
    }

    /*
     * 指令检测函数
     * 负责对指令进行循论查找，找不到则返回错误指令报告
     * */
    private function taskChecker(): void
    {
        if ($this->commandList[0] === '任务') {
            $this->questSearch();
        } else {
            $this->httpAPI->sendText('错误指令', $this->messageInfo['channelID'], true, $this->messageInfo['messageID']);
        }
    }

    /*
     * 查询任务指令处理函数
     * 负责处理获取的任务查询指令，包括参数分析与数据库的查询
     * */
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

    /*
     * 查询任务指令任务信息构造函数
     * 负责从 XIVAPI 获取指定任务的信息，并将对应 JSON 信息转换为数组，供 processQuestInfo 函数进行信息构造
     * */
    private function getQuestInfo($questID): array
    {
        //任务具体信息筛选
        $searchCondition = "?columns=Name,
        Banner,
        TextData.ToDo,
        PlaceName.Name,
        GilReward,
        ExperiencePoints,
        ItemCountReward0,
        ItemCountReward1,
        ItemCountReward2,
        ItemCountReward3,
        ItemCountReward4,
        ItemCountReward5,
        ItemCountReward6,
        ItemReward0,
        ItemReward1,
        ItemReward2,
        ItemReward3,
        ItemReward4,
        ItemReward5,
        ItemReward6,
        OptionalItemCountReward0,
        OptionalItemCountReward1,
        OptionalItemCountReward2,
        OptionalItemCountReward3,
        OptionalItemCountReward4,
        OptionalItemReward0,
        OptionalItemReward1,
        OptionalItemReward2,
        OptionalItemReward3,
        OptionalItemReward4,
        Icon,
        IssuerStart.Name,
        TargetEnd.Name,
        ClassJobCategory0.Name,
        JournalGenre.JournalCategory.Name,
        JournalGenre.Name";
        //字符串格式化
        $searchCondition = str_replace(array("\r", "\n", " "), "", $searchCondition);
        $data = $this->XIVAPI->get('/quest/' . $questID . $searchCondition);
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
            'OptionNum0' => $data->OptionalItemCountReward0,
            'OptionNum1' => $data->OptionalItemCountReward1,
            'OptionNum2' => $data->OptionalItemCountReward2,
            'OptionNum3' => $data->OptionalItemCountReward3,
            'OptionNum4' => $data->OptionalItemCountReward4,
            'Option0' => $data->OptionalItemReward0,
            'Option1' => $data->OptionalItemReward1,
            'Option2' => $data->OptionalItemReward2,
            'Option3' => $data->OptionalItemReward3,
            'Option4' => $data->OptionalItemReward4,
        );
        return $dataArray;
    }

    /*
     * 查询任务指令频道信息构造函数
     * 利用 getQuestInfo 函数返回的信息数组进行频道卡片信息的合成，并返回给 questSearch 函数
     * */
    private function processQuestInfo($questArray): string
    {
        //卡片总框架
        $infoCard = new Card();

        //任务标题信息框架
        $questTitle = new ImageText('**[' . $questArray['Name'] . '](https://ff14.huijiwiki.com/wiki/任务:' . $questArray['Name'] . ')**', 'https://cafemaker.wakingsands.com' . $questArray['Icon'], 'kmarkdown');
        $infoCard->insert($questTitle);

        //任务图片框架
        if ($questArray['Banner'] !== '') {
            $questBanner = new Image('https://xivapi.com' . $questArray['Banner']);
            $infoCard->insert($questBanner);
        }

        //分割线
        $divider = new Divider();
        $infoCard->insert($divider);

        //任务详情信息框架
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

        //报酬框架
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

        //任务目标框架
        if (!is_null($questArray['TodoList'])) {
            $todoCard = new Card();
            $todoTitle = new PlainText('任务目的', 'plain-text', 'header');
            $todoCard->insert($todoTitle);
            foreach ($questArray['TodoList'] as $todo) {
                //跳过空内容
                if ($todo->Text === "空") {
                    continue;
                }
                $TodoText = new PlainText('(spl)' . $todo->Text . '(spl)', 'kmarkdown');
                $todoCard->insert($TodoText);
            }
            array_push($data, $todoCard);
        }
        return json_encode($data);
    }
}
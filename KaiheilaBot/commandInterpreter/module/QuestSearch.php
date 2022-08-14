<?php

namespace KaiheilaBot\commandInterpreter\module;
require __DIR__ . '/../../cardMessage/importAllCard.php';
require __DIR__ . '/CommandParser.php';

use KaiheilaBot\cardMessage\Card;
use KaiheilaBot\cardMessage\Divider;
use KaiheilaBot\cardMessage\Image;
use KaiheilaBot\cardMessage\ImageText;
use KaiheilaBot\cardMessage\MultiColumnText;
use KaiheilaBot\cardMessage\PlainText;
use Swlib\Saber;

class QuestSearch extends CommandParser
{
    //XIVAPI查询对象
    protected $XIVAPI;
    //XIVAPI开发者Key
    protected $XIVAPIKey;

    /*
     * 类属性初始化函数
     * 负责初始化非标准类属性
     * */
    public function getConfig($XIVAPIKey): void
    {
        $this->XIVAPI = Saber::create(['base_uri' => 'https://xivapi.com']);
        $this->XIVAPIKey = $XIVAPIKey;
    }

    /*
     * 查询任务指令处理函数
     * 负责处理获取的任务查询指令，包括参数分析与数据库的查询
     * */
    public function run($command, $msgInfo): array
    {
        $this->getCommand($command, $msgInfo);
        return $this->questSearch();
    }

    /*
     * 查询任务指令任务信息构造函数
     * 负责从 XIVAPI 获取指定任务的信息，并将对应 JSON 信息转换为数组，供 processQuestInfo 函数进行信息构造
     * */
    private function questSearch(): array
    {
        $data = array();
        if (count($this->commandList) !== 2) {
            $msg = '指令缺少参数，请查看指令使用帮助（/帮助 任务）';
            $target_id = $this->messageInfo['channelID'];
            $is_quote = true;
            $quote = $this->messageInfo['messageID'];
            $type = 1;
            $data = array($msg, $target_id, $is_quote, $quote, $type);
        } else {
            $search = $this->db->getQuestID($this->commandList[1]);

            //数据库查询失败
            if ($search[0] === 0) {
                $msg = '查询出错，请检查 sql 语句或数据库状态！（联系开发者或机器人所有者）';
                $target_id = $this->messageInfo['channelID'];
                $is_quote = true;
                $quote = $this->messageInfo['messageID'];
                $type = 1;
                $data = array($msg, $target_id, $is_quote, $quote, $type);
            }

            //查询成功
            if ($search[0] === 1) {
                //无结果
                if ($search[1] === 0) {
                    $msg = '没有结果，请确保输入的任务名正确！（英文文本区分大小写）';
                    $target_id = $this->messageInfo['channelID'];
                    $is_quote = true;
                    $quote = $this->messageInfo['messageID'];
                    $type = 1;
                    $data = array($msg, $target_id, $is_quote, $quote, $type);
                } //有结果
                else {
                    //发送 API 请求
                    $api = $this->getQuestInfo($search[1]);

                    //出现错误状态码时
                    if ($api[0] === 0) {
                        $msg = $api[1];
                        $target_id = $this->messageInfo['channelID'];
                        $is_quote = true;
                        $quote = $this->messageInfo['messageID'];
                        $type = 1;
                        return array($msg, $target_id, $is_quote, $quote, $type);
                    }

                    //正常返回 200 状态码时
                    $msg = $this->processQuestInfo($api[1]);
                    $target_id = $this->messageInfo['channelID'];
                    $is_quote = true;
                    $quote = $this->messageInfo['messageID'];
                    $type = 10;
                    $data = array($msg, $target_id, $is_quote, $quote, $type);
                }
            }
        }
        return $data;
    }

    /*
     * 查询任务指令频道信息构造函数
     * 利用 getQuestInfo 函数返回的信息数组进行频道卡片信息的合成，并返回给 questSearch 函数
     * */
    private function processQuestInfo($questArray): string
    {
        //任务信息框架
        $infoCard = new Card();

        //任务标题信息框架
        $questName = $this->db->getQuestName($questArray['ID']);
        $questTitle = new ImageText('**[' . $questName[1][0] . '](https://ff14.huijiwiki.com/wiki/任务:' . $questName[1][0] . ')**', 'https://xivapi.com' . $questArray['Icon'], 'kmarkdown');
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
            $MainCategory = $this->db->getJournalCategoryName($questArray['MainCategory']);
            $detailInfo->insert("**主分类**\n[" . $MainCategory[1][0] . '](https://ff14.huijiwiki.com/wiki/' . $MainCategory[1][0] . ')', 'kmarkdown');
        }
        $SubCategory = $this->db->getJournalGenreName($questArray['SubCategory']);
        $detailInfo->insert("**子分类**\n" . $SubCategory[1][0], 'kmarkdown');

        $Job = $this->db->getClassJobCategoryName($questArray['Job']);
        $detailInfo->insert("**职业**\n" . $Job[1][0], 'kmarkdown');

        $StartPlace = $this->db->getQuestPlaceName($questArray['StartPlace']);
        $detailInfo->insert("**开始地区**\n" . $StartPlace[1][0], 'kmarkdown');

        $StartNPC = $this->db->getQuestNPCName($questArray['StartNPC']);
        $detailInfo->insert("**开始NPC**\n" . $StartNPC[1][0], 'kmarkdown');

        $EndNPC = $this->db->getQuestNPCName($questArray['FinishNPC']);
        $detailInfo->insert("**结束NPC**\n" . $EndNPC[1][0], 'kmarkdown');
        $infoCard->insert($detailInfo);
        $infoCard->insert($divider);
        $data = array($infoCard);

        //报酬框架
        if ($questArray['Money'] !== 0 || $questArray['Exp'] !== 0 || $questArray['ItemNum0'] !== 0 || $questArray['CatalystNum0'] !== 0) {
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
            if ($questArray['CatalystNum0']) {
                $Catalyst0Name = $this->db->getItemName($questArray['Catalyst0']->ID)[1][0];
                $Catalyst0Reward = new ImageText('x' . $questArray['CatalystNum0'] . ' [' . $Catalyst0Name . '](https://ff14.huijiwiki.com/wiki/物品:' . $Catalyst0Name . ')', 'https://xivapi.com' . $questArray['Catalyst0']->Icon, 'kmarkdown');
                $rewardCard->insert($Catalyst0Reward);
            }
            if ($questArray['CatalystNum1']) {
                $Catalyst1Name = $this->db->getItemName($questArray['Catalyst1']->ID)[1][0];
                $Catalyst1Reward = new ImageText('x' . $questArray['CatalystNum1'] . ' [' . $Catalyst1Name . '](https://ff14.huijiwiki.com/wiki/物品:' . $Catalyst1Name . ')', 'https://xivapi.com' . $questArray['Catalyst1']->Icon, 'kmarkdown');
                $rewardCard->insert($Catalyst1Reward);
            }
            if ($questArray['CatalystNum2']) {
                $Catalyst2Name = $this->db->getItemName($questArray['Catalyst2']->ID)[1][0];
                $Catalyst2Reward = new ImageText('x' . $questArray['CatalystNum2'] . ' [' . $Catalyst2Name . '](https://ff14.huijiwiki.com/wiki/物品:' . $Catalyst2Name . ')', 'https://xivapi.com' . $questArray['Catalyst2']->Icon, 'kmarkdown');
                $rewardCard->insert($Catalyst2Reward);
            }
            if ($questArray['ItemNum0']) {

                $Item0Name = $this->db->getItemName($questArray['Item0']->ID)[1][0];
                $item0Reward = new ImageText('x' . $questArray['ItemNum0'] . ' [' . $Item0Name . '](https://ff14.huijiwiki.com/wiki/物品:' . $Item0Name . ')', 'https://xivapi.com' . $questArray['Item0']->Icon, 'kmarkdown');
                $rewardCard->insert($item0Reward);
            }
            if ($questArray['ItemNum1']) {
                $Item1Name = $this->db->getItemName($questArray['Item1']->ID)[1][0];
                $item1Reward = new ImageText('x' . $questArray['ItemNum1'] . ' [' . $Item1Name . '](https://ff14.huijiwiki.com/wiki/物品:' . $Item1Name . ')', 'https://xivapi.com' . $questArray['Item1']->Icon, 'kmarkdown');
                $rewardCard->insert($item1Reward);
            }
            if ($questArray['ItemNum2']) {
                $Item2Name = $this->db->getItemName($questArray['Item2']->ID)[1][0];
                $item2Reward = new ImageText('x' . $questArray['ItemNum2'] . ' [' . $Item2Name . '](https://ff14.huijiwiki.com/wiki/物品:' . $Item2Name . ')', 'https://xivapi.com' . $questArray['Item2']->Icon, 'kmarkdown');
                $rewardCard->insert($item2Reward);
            }
            if ($questArray['ItemNum3']) {
                $Item3Name = $this->db->getItemName($questArray['Item3']->ID)[1][0];
                $item3Reward = new ImageText('x' . $questArray['ItemNum3'] . ' [' . $Item3Name . '](https://ff14.huijiwiki.com/wiki/物品:' . $Item3Name . ')', 'https://xivapi.com' . $questArray['Item3']->Icon, 'kmarkdown');
                $rewardCard->insert($item3Reward);
            }
            if ($questArray['ItemNum4']) {
                $Item4Name = $this->db->getItemName($questArray['Item4']->ID)[1][0];
                $item4Reward = new ImageText('x' . $questArray['ItemNum4'] . ' [' . $Item4Name . '](https://ff14.huijiwiki.com/wiki/物品:' . $Item4Name . ')', 'https://xivapi.com' . $questArray['Item4']->Icon, 'kmarkdown');
                $rewardCard->insert($item4Reward);
            }
            if ($questArray['ItemNum5']) {
                $Item5Name = $this->db->getItemName($questArray['Item5']->ID)[1][0];
                $item5Reward = new ImageText('x' . $questArray['ItemNum5'] . ' [' . $Item5Name . '](https://ff14.huijiwiki.com/wiki/物品:' . $Item5Name . ')', 'https://xivapi.com' . $questArray['Item5']->Icon, 'kmarkdown');
                $rewardCard->insert($item5Reward);
            }
            if ($questArray['ItemNum6']) {
                $Item6Name = $this->db->getItemName($questArray['Item6']->ID)[1][0];
                $item6Reward = new ImageText('x' . $questArray['ItemNum6'] . ' [' . $Item6Name . '](https://ff14.huijiwiki.com/wiki/物品:' . $Item6Name . ')', 'https://xivapi.com' . $questArray['Item6']->Icon, 'kmarkdown');
                $rewardCard->insert($item6Reward);
            }
            $data[] = $rewardCard;
        }

        //可选报酬框架
        if ($questArray['OptionNum0'] !== 0) {
            $OptionCard = new Card();
            $OptionTitle = new PlainText('可选报酬', 'plain-text', 'header');
            $OptionCard->insert($OptionTitle);
            if ($questArray['OptionNum0']) {
                $Option0Name = $this->db->getItemName($questArray['Option0']->ID)[1][0];
                $Option0 = new ImageText('x' . $questArray['OptionNum0'] . ' [' . $Option0Name . '](https://ff14.huijiwiki.com/wiki/物品:' . $Option0Name . ')', 'https://xivapi.com' . $questArray['Option0']->Icon, 'kmarkdown');
                $OptionCard->insert($Option0);
            }
            if ($questArray['OptionNum1']) {
                $Option1Name = $this->db->getItemName($questArray['Option1']->ID)[1][0];
                $Option1 = new ImageText('x' . $questArray['OptionNum1'] . ' [' . $Option1Name . '](https://ff14.huijiwiki.com/wiki/物品:' . $Option1Name . ')', 'https://xivapi.com' . $questArray['Option1']->Icon, 'kmarkdown');
                $OptionCard->insert($Option1);
            }
            if ($questArray['OptionNum2']) {
                $Option2Name = $this->db->getItemName($questArray['Option2']->ID)[1][0];
                $Option2 = new ImageText('x' . $questArray['OptionNum2'] . ' [' . $Option2Name . '](https://ff14.huijiwiki.com/wiki/物品:' . $Option2Name . ')', 'https://xivapi.com' . $questArray['Option2']->Icon, 'kmarkdown');
                $OptionCard->insert($Option2);
            }
            if ($questArray['OptionNum3']) {
                $Option3Name = $this->db->getItemName($questArray['Option3']->ID)[1][0];
                $Option3 = new ImageText('x' . $questArray['OptionNum3'] . ' [' . $Option3Name . '](https://ff14.huijiwiki.com/wiki/物品:' . $Option3Name . ')', 'https://xivapi.com' . $questArray['Option3']->Icon, 'kmarkdown');
                $OptionCard->insert($Option3);
            }
            if ($questArray['OptionNum4']) {
                $Option4Name = $this->db->getItemName($questArray['Option4']->ID)[1][0];
                $Option4 = new ImageText('x' . $questArray['OptionNum4'] . ' [' . $Option4Name . '](https://ff14.huijiwiki.com/wiki/物品:' . $Option4Name . ')', 'https://xivapi.com' . $questArray['Option4']->Icon, 'kmarkdown');
                $OptionCard->insert($Option4);
            }
            $data[] = $OptionCard;
        }

        //技能报酬框架
        if (!is_null($questArray['ActionRewardID'])) {
            $ActionCard = new Card();
            $ActionTitle = new PlainText('技能习得', 'plain-text', 'header');
            $ActionCard->insert($ActionTitle);
            $ActionName = $this->db->getActionName($questArray['ActionRewardID'])[1][0];
            $ActionIcon = $questArray['ActionRewardIcon'];
            $Action = new ImageText($ActionName, 'https://xivapi.com' . $ActionIcon, 'kmarkdown');
            $ActionCard->insert($Action);
            $data[] = $ActionCard;
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
            $data[] = $todoCard;
        }
        return json_encode($data);
    }

    /*
     * XIVAPI 查询函数
     * 向 XIVAPI 发起 HTTP 通讯，查询任务信息
     * */
    private function getQuestInfo($questID): array
    {
        //TextData.ToDo
        //任务具体信息筛选
        $searchCondition = "?columns=ID,
        Banner,
        PlaceName.ID,
        GilReward,
        ExperiencePoints,
        ItemCountReward0,
        ItemCountReward1,
        ItemCountReward2,
        ItemCountReward3,
        ItemCountReward4,
        ItemCountReward5,
        ItemCountReward6,
        ItemReward0.ID,
        ItemReward0.Icon,
        ItemReward1.ID,
        ItemReward1.Icon,
        ItemReward2.ID,
        ItemReward2.Icon,
        ItemReward3.ID,
        ItemReward3.Icon,
        ItemReward4.ID,
        ItemReward4.Icon,
        ItemReward5.ID,
        ItemReward5.Icon,
        ItemReward6.ID,
        ItemReward6.Icon,
        OptionalItemCountReward0,
        OptionalItemCountReward1,
        OptionalItemCountReward2,
        OptionalItemCountReward3,
        OptionalItemCountReward4,
        OptionalItemReward0.ID,
        OptionalItemReward0.Icon,
        OptionalItemReward1.ID,
        OptionalItemReward1.Icon,
        OptionalItemReward2.ID,
        OptionalItemReward2.Icon,
        OptionalItemReward3.ID,
        OptionalItemReward3.Icon,
        OptionalItemReward4.ID,
        OptionalItemReward4.Icon,
        ItemCountCatalyst0,
        ItemCountCatalyst1,
        ItemCountCatalyst2,
        ItemCatalyst0,
        ItemCatalyst1,
        ItemCatalyst2,
        Icon,
        IssuerStart.ID,
        TargetEnd.ID,
        ClassJobCategory0.ID,
        JournalGenre.JournalCategory.ID,
        JournalGenre.ID,
        ActionReward.ID,
        ActionReward.IconHD";
        //字符串格式化
        $searchCondition = str_replace(array("\r", "\n", " "), "", $searchCondition);
        $searchCondition .= '&private_key=' . $this->XIVAPIKey;

        //发送 Get 请求
        try {
            $data = $this->XIVAPI->get('/quest/' . $questID . $searchCondition);
        } catch (\Swlib\Http\Exception\ClientException $e) {
            $data = '错误的 URL 地址，请检查访问连接';
            return array(0, $data);
        } catch (\Swlib\Http\Exception\ConnectException $e) {
            $data = '无法与服务器建立连接，请重试（可能达到每分钟访问限制）';
            return array(0, $data);
        } catch (\Swlib\Http\Exception\ServerException $e) {
            $data = 'XIVAPI 服务器出错，返回了 50X 状态码';
            return array(0, $data);
        }

        $data = json_decode($data->body);
        //'TodoList' => $data->TextData->ToDo,
        return array(1, array(
            'ID' => $data->ID,
            'Banner' => $data->Banner,
            'Icon' => $data->Icon,
            'Job' => $data->ClassJobCategory0->ID,
            'TodoList' => null,
            'MainCategory' => $data->JournalGenre->JournalCategory->ID,
            'SubCategory' => $data->JournalGenre->ID,
            'StartPlace' => $data->PlaceName->ID,
            'StartNPC' => $data->IssuerStart->ID,
            'FinishNPC' => $data->TargetEnd->ID,
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
            'CatalystNum0' => $data->ItemCountCatalyst0,
            'CatalystNum1' => $data->ItemCountCatalyst1,
            'CatalystNum2' => $data->ItemCountCatalyst2,
            'Catalyst0' => $data->ItemCatalyst0,
            'Catalyst1' => $data->ItemCatalyst1,
            'Catalyst2' => $data->ItemCatalyst2,
            'ActionRewardID' => $data->ActionReward->ID,
            'ActionRewardIcon' => $data->ActionReward->IconHD
        ));
    }


}
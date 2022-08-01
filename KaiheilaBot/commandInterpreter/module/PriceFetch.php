<?php

namespace KaiheilaBot\commandInterpreter\module;

use KaiheilaBot\cardMessage\Card;
use KaiheilaBot\cardMessage\Divider;
use KaiheilaBot\cardMessage\ImageText;
use KaiheilaBot\cardMessage\MultiColumnText;
use KaiheilaBot\cardMessage\PlainText;
use Swlib\Saber;

class PriceFetch extends CommandParser
{
    private $UniversalisAPI;
    private $XIVAPI;
    private $XIVAPIKey;
    private array $args;


    /*
     * 查询价格指令处理函数
     * 调用内部成员函数，完成价格查询
     * */
    public function run($command, $msgInfo): array
    {
        $this->commandList = $command;
        $this->messageInfo = $msgInfo;
        $argCheck = $this->splitArgs($this->commandList);
        //参数出错则返回出错消息提醒
        if ($argCheck[0] === 0) {
            return $argCheck[1];
        }
        $url = $this->getPriceURL();
        if ($url[0] === 0) {
            return $url[1];
        }
        return $this->getPrice($url[1]);
    }

    /*
 * 类属性初始化函数
 * 负责初始化非标准类属性
 * */

    private function splitArgs($command): array
    {
        //无参数情况
        if (count($command) === 1) {
            $msg = '指令缺少参数，请查看指令使用帮助（/帮助 价格）';
            $target_id = $this->messageInfo['channelID'];
            $is_quote = true;
            $quote = $this->messageInfo['messageID'];
            $type = 1;
            $data = array($msg, $target_id, $is_quote, $quote, $type);
            return array(0, $data);
        }

        //以 - 为界分割参数
        $args = explode('-', $command[1]);
        //去除数组头部空元素
        array_shift($args);
        //将参数变为可处理数组
        $temp = array();
        foreach ($args as $i) {
            $t = explode('=', rtrim($i), 2);
            if (count($t) === 1) {
                break;
            }
            $temp[] = array($t[0] => $t[1]);
        }
        if (empty($temp)) {
            foreach ($args as $i) {
                $t = explode(' ', rtrim($i), 2);
                if (count($t) === 1) {
                    break;
                }
                $temp[] = array($t[0] => $t[1]);
            }
        }
        $this->args = array(
            'region' => array_column($temp, 'w'),
            'item' => array_column($temp, 'i'),
            'time' => array_column($temp, 'd'),
            'hq' => array_column($temp, 'h'),
            'count' => array_column($temp, 'c'),
            'noGst' => array_column($temp, 'g'),
            'type' => array_column($temp, 't')
        );
        if (empty($this->args['region']) || empty($this->args['item'])) {
            $msg = '指令缺少参数，请查看指令使用帮助（/帮助 价格）';
            $target_id = $this->messageInfo['channelID'];
            $is_quote = true;
            $quote = $this->messageInfo['messageID'];
            $type = 1;
            $data = array($msg, $target_id, $is_quote, $quote, $type);
            return array(0, $data);
        }
        return array(1);
    }

    /*
     * 参数分割函数
     * 将任务指令后所跟参数进行再次分割，当不满足要求格式时进行报错
     * */

    private function getPriceURL(): array
    {
        $id = 0;
        //检索中文物品列表
        $search = $this->db->search('itemlist.id', 'itemlist', 'itemlist.item', $this->args['item'][0], '=');
        //查询中文列表返回结果为空时，检索英文列表
        if ($search[0] === 1) {
            if (!$search[1]) {
                $search = $this->db->search('itemlist.id', 'itemlist', 'itemlist.item_en', $this->args['item'][0], '=');
            }
        }
        //查询英文列表返回结果为空时，检索日文列表
        if ($search[0] === 1) {
            if (!$search[1]) {
                $search = $this->db->search('itemlist.id', 'itemlist', 'itemlist.item_jp', $this->args['item'][0], '=');
            }
        }
        //无法执行 sql 语句时
        if ($search[0] === 0) {
            $msg = '查询出错，请检查 sql 语句或数据库状态！（联系开发者或机器人所有者）';
            $target_id = $this->messageInfo['channelID'];
            $is_quote = true;
            $quote = $this->messageInfo['messageID'];
            $type = 1;
            $data = array($msg, $target_id, $is_quote, $quote, $type);
            return array(0, $data);
        }
        //三次全部查完
        if ($search[0] === 1) {
            //最终结果为空时
            if (!$search[1]) {
                $msg = '没有结果，请确保输入的物品名正确！（英文文本区分大小写）';
                $target_id = $this->messageInfo['channelID'];
                $is_quote = true;
                $quote = $this->messageInfo['messageID'];
                $type = 1;
                $data = array($msg, $target_id, $is_quote, $quote, $type);
                return array(0, $data);
            }
            //正常检索到结果
            $id = $search[1]['id'];
        }

        //当查询类型为空时，默认查询当前出售价格和出售记录（买模式+卖模式）
        if (empty($this->args['type'])) {
            $itemURL = '/item/' . $id . '?columns=Icon,Name';
            $dataURL = '/api/v2/' . $this->args['region'][0] . '/' . $id . '?';
            //查询时长
            if (!empty($this->args['time'])) {
                $dataURL .= 'statsWithin=' . $this->args['time'][0] . '&';
                $dataURL .= 'entriesWithin=' . $this->args['time'][0] . '&';
            }
            //查询个数
            if (!empty($this->args['count'])) {
                $dataURL .= 'listings=' . $this->args['count'][0] . '&';
                $dataURL .= 'entries=' . $this->args['count'][0] . '&';
            } else {
                $dataURL .= 'listings=10&';
                $dataURL .= 'entries=10&';
            }
            //是否查询hq
            if (!empty($this->args['hq'])) {
                $dataURL .= 'hq=' . $this->args['hq'][0] . '&';
            }
            //是否屏蔽税款
            if (!empty($this->args['noGst'])) {
                $dataURL .= 'hq=' . $this->args['noGst'][0] . '&';
            }
            return array(1, array($dataURL, $itemURL));
        }

        //买模式查询（查询指定时间范围内的出售价格）
        if ($this->args['type'][0] === '买' || $this->args['type'][0] === 'B' || $this->args['type'][0] === 'b') {
            $itemURL = '/item/' . $id . '?columns=Icon,Name';
            $dataURL = '/api/v2/' . $this->args['region'][0] . '/' . $id . '?';
            $dataURL .= 'entries=0&';
            //查询时长
            if (!empty($this->args['time'])) {
                $dataURL .= 'statsWithin=' . $this->args['time'][0] . '&';
            }
            //查询个数
            if (!empty($this->args['count'])) {
                $dataURL .= 'listings=' . $this->args['count'][0] . '&';
            } else {
                $dataURL .= 'listings=10&';
            }
            //是否查询hq
            if (!empty($this->args['hq'])) {
                $dataURL .= 'hq=' . $this->args['hq'][0] . '&';
            }
            //是否屏蔽税款
            if (!empty($this->args['noGst'])) {
                $dataURL .= 'hq=' . $this->args['noGst'][0] . '&';
            }
            return array(1, array($dataURL, $itemURL));
        }

        //卖模式查询（查询指定时间范围内的出售记录）
        if ($this->args['type'][0] === '卖' || $this->args['type'][0] === 'S' || $this->args['type'][0] === 's') {
            $itemURL = '/item/' . $id . '?columns=Icon,Name';
            $dataURL = '/api/v2/' . $this->args['region'][0] . '/' . $id . '?';
            $dataURL .= 'listings=0&';
            //查询时长
            if (!empty($this->args['time'])) {
                $dataURL .= 'entriesWithin=' . $this->args['time'][0] . '&';
            }
            //查询个数
            if (!empty($this->args['count'])) {
                $dataURL .= 'entries=' . $this->args['count'][0] . '&';
            } else {
                $dataURL .= 'entries=10&';
            }
            //是否查询hq
            if (!empty($this->args['hq'])) {
                $dataURL .= 'hq=' . $this->args['hq'][0] . '&';
            }
            //是否屏蔽税款
            if (!empty($this->args['noGst'])) {
                $dataURL .= 'hq=' . $this->args['noGst'][0] . '&';
            }
            return array(1, array($dataURL, $itemURL));
        }

        //查询类型非空，但是为错误内容
        $msg = '查询类型参数有误，请检查后重试！';
        $target_id = $this->messageInfo['channelID'];
        $is_quote = true;
        $quote = $this->messageInfo['messageID'];
        $type = 1;
        $data = array($msg, $target_id, $is_quote, $quote, $type);
        return array(0, $data);
    }

    /*
     * Universalis URL 合成函数
     * 根据参数合成指定的 URL
     * */

    private function getPrice($urlList): array
    {
        try {
            $data = $this->UniversalisAPI->get($urlList[0]);
        } catch (\Swlib\Http\Exception\ClientException $e) {
            $msg = '与 Universalis 通讯时出错，请检查是否填写了正确的服务器名称，或者稍后再试（需要全称）';
            $target_id = $this->messageInfo['channelID'];
            $is_quote = true;
            $quote = $this->messageInfo['messageID'];
            $type = 1;
            return array($msg, $target_id, $is_quote, $quote, $type);
        } catch (\Swlib\Http\Exception\ConnectException $e) {
            $msg = '无法与服务器建立连接，请重试';
            $target_id = $this->messageInfo['channelID'];
            $is_quote = true;
            $quote = $this->messageInfo['messageID'];
            $type = 1;
            return array($msg, $target_id, $is_quote, $quote, $type);
        } catch (\Swlib\Http\Exception\ServerException $e) {
            $msg = 'Universalis 服务器出错，返回了 50X 状态码';
            $target_id = $this->messageInfo['channelID'];
            $is_quote = true;
            $quote = $this->messageInfo['messageID'];
            $type = 1;
            return array($msg, $target_id, $is_quote, $quote, $type);
        }

        try {
            $item = $this->XIVAPI->get($urlList[1]);
        } catch (\Swlib\Http\Exception\ClientException $e) {
            $msg = '与 XIVAPI 通讯时出错，请稍后再试（40X）';
            $target_id = $this->messageInfo['channelID'];
            $is_quote = true;
            $quote = $this->messageInfo['messageID'];
            $type = 1;
            return array($msg, $target_id, $is_quote, $quote, $type);
        } catch (\Swlib\Http\Exception\ConnectException $e) {
            $msg = '无法与服务器建立连接，请重试（由于并非与 XIVAPI 直接通讯，而是与 FFCafe 的 API 建立连接，或者是达到每分钟访问限制）';
            $target_id = $this->messageInfo['channelID'];
            $is_quote = true;
            $quote = $this->messageInfo['messageID'];
            $type = 1;
            return array($msg, $target_id, $is_quote, $quote, $type);
        } catch (\Swlib\Http\Exception\ServerException $e) {
            $msg = 'FFCafe 服务器出错，返回了 50X 状态码';
            $target_id = $this->messageInfo['channelID'];
            $is_quote = true;
            $quote = $this->messageInfo['messageID'];
            $type = 1;
            return array($msg, $target_id, $is_quote, $quote, $type);
        }

        $data = json_decode($data->body);
        $item = json_decode($item->body);

        $msg = $this->processPriceInfo(array($data, $item));
        $target_id = $this->messageInfo['channelID'];
        $is_quote = true;
        $quote = $this->messageInfo['messageID'];
        $type = 10;
        return array($msg, $target_id, $is_quote, $quote, $type);
    }

    private function processPriceInfo($datalist): string
    {
        //物品信息框架
        $itemInfo = new Card();

        //物品标题
        $itemTitle = new ImageText('**[' . $datalist[1]->Name . '](https://ff14.huijiwiki.com/wiki/物品:' . $datalist[1]->Name . ')**', 'https://cafemaker.wakingsands.com' . $datalist[1]->Icon, 'kmarkdown');
        $itemInfo->insert($itemTitle);

        //分割线
        $divider = new Divider();
        $itemInfo->insert($divider);

        //价格信息
        $priceInfo = new MultiColumnText();
        $priceInfo->insert("**目前平均价格**\n" . $datalist[0]->currentAveragePrice, 'kmarkdown');
        $priceInfo->insert("**目前NQ(普通品质)平均价格**\n" . $datalist[0]->currentAveragePriceNQ, 'kmarkdown');
        $priceInfo->insert("**目前HQ(高品质)平均价格**\n" . $datalist[0]->currentAveragePriceHQ, 'kmarkdown');
        $priceInfo->insert("**历史平均价格**\n" . $datalist[0]->averagePrice, 'kmarkdown');
        $priceInfo->insert("**历史NQ(普通品质)平均价格**\n" . $datalist[0]->averagePriceNQ, 'kmarkdown');
        $priceInfo->insert("**历史HQ(高品质)平均价格**\n" . $datalist[0]->averagePriceHQ, 'kmarkdown');
        $priceInfo->insert("**历史最低价格**\n" . $datalist[0]->minPrice, 'kmarkdown');
        $priceInfo->insert("**历史NQ(普通品质)最低价格**\n" . $datalist[0]->minPriceNQ, 'kmarkdown');
        $priceInfo->insert("**历史HQ(高品质)最低价格**\n" . $datalist[0]->minPriceHQ, 'kmarkdown');
        $priceInfo->insert("**历史最高价格**\n" . $datalist[0]->maxPrice, 'kmarkdown');
        $priceInfo->insert("**历史NQ(普通品质)最高价格**\n" . $datalist[0]->maxPriceNQ, 'kmarkdown');
        $priceInfo->insert("**历史HQ(高品质)最高价格**\n" . $datalist[0]->maxPriceHQ, 'kmarkdown');

        $itemInfo->insert($priceInfo);
        $card = array($itemInfo);

        //出售列表
        if (!empty($datalist[0]->listings)) {
            $buyInfo = new Card();
            $buyingTitle = new PlainText('出售列表', 'plain-text', 'header');
            $buyInfo->insert($buyingTitle);
            $content = "";
            foreach ($datalist[0]->listings as $x) {
                $content .= '查询时间：' . date('Y-m-d H:i:s', $x->lastReviewTime) . "\n";
                $content .= '物品单价：' . $x->pricePerUnit . "\n";
                $content .= '出售数量：' . $x->quantity . "\n";
                $content .= '花费总价：' . $x->total . "\n";
                $judgement = $x->hq ? "是" : "否";
                $content .= '为高品质：' . $judgement . "\n";
                $content .= '雇员姓名：' . $x->retainerName . "\n";
                if (property_exists($x, 'worldName')) {
                    $content .= '所在服务器：' . $x->worldName . "\n";
                }
                $content .= "---\n";
            }
            $list = new PlainText($content, 'kmarkdown');
            $buyInfo->insert($list);
            $card[] = $buyInfo;
        }

        //售出列表
        if (!empty($datalist[0]->recentHistory)) {
            $sellInfo = new Card();
            $sellingTitle = new PlainText('售出列表', 'plain-text', 'header');
            $sellInfo->insert($sellingTitle);
            $content = "";
            foreach ($datalist[0]->recentHistory as $x) {
                $content .= '出售时间：' . date('Y-m-d H:i:s', $x->timestamp) . "\n";
                $content .= '物品单价：' . $x->pricePerUnit . "\n";
                $content .= '出售数量：' . $x->quantity . "\n";
                $content .= '花费总价：' . $x->total . "\n";
                $judgement = $x->hq ? "是" : "否";
                $content .= '为高品质：' . $judgement . "\n";
                $content .= '买家姓名：' . $x->buyerName . "\n";
                if (property_exists($x, 'worldName')) {
                    $content .= '所在服务器：' . $x->worldName . "\n";
                }
                $content .= "---\n";
            }
            $list = new PlainText($content, 'kmarkdown');
            $sellInfo->insert($list);
            $card[] = $sellInfo;

        }
        return json_encode($card);
    }

    public function getConfig($XIVAPIKey): void
    {
        $this->UniversalisAPI = Saber::create(['base_uri' => 'https://universalis.app', 'timeout' => 30]);
        $this->XIVAPI = Saber::create(['base_uri' => 'https://cafemaker.wakingsands.com', 'timeout' => 30]);
        $this->XIVAPIKey = $XIVAPIKey;
    }
}
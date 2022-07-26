<?php

namespace KaiheilaBot\commandInterpreter\module;

class TextTrainslate extends CommandParser
{
    //参数数组，由传递来的 $command 进行再次划分得来，合法元素数量为 2
    private array $args;

    /*
     * 运行函数
     * 为该类的总函数，进行指令的处理
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

        return $this->searchTranslate();
    }

    /*
     * 参数分割函数
     * 将任务指令后所跟参数进行再次分割，当不满足要求格式时进行报错
     * */
    private function splitArgs($command): array
    {
        //无参数情况
        if (count($command) === 1) {
            $msg = '指令缺少参数，请查看指令使用帮助';
            $target_id = $this->messageInfo['channelID'];
            $is_quote = true;
            $quote = $this->messageInfo['messageID'];
            $type = 1;
            $data = array($msg, $target_id, $is_quote, $quote, $type);
            return array(0, $data);
        }

        //参数分割
        $this->args = explode(' ', $command[1], 2);

        //参数1后跟空格但参数2不存在情况，删去null的参数2,归并为只有一个参数情况
        if ((count($this->args) === 2) && $this->args[1] === "") {
            array_pop($this->args);
        }

        //只有一个参数情况
        if (count($this->args) === 1) {
            $msg = '指令参数不完整，请查看指令使用帮助';
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
     * 查询指定信息翻译函数
     * 从数据库中检索指定项目的 中/英/日 翻译
     * */
    private function searchTranslate(): array
    {
        if ($this->args[0] === '物品') {
            $table = 'itemlist';
            $col = 'item';
        } elseif ($this->args[0] === '任务') {
            $table = 'questlist';
            $col = 'quest';
        } else {
            $msg = '参数1错误，请查询指令使用方式';
            $target_id = $this->messageInfo['channelID'];
            $is_quote = true;
            $quote = $this->messageInfo['messageID'];
            $type = 1;
            return array($msg, $target_id, $is_quote, $quote, $type);
        }

        //检索中文列表
        $search = $this->db->search('*', $table, $table . '.' . $col, $this->args[1], '=');

        //查询中文列表返回结果为空时，检索英文列表
        if ($search[0] === 1) {
            if (!$search[1]) {
                $colEN = $col . '_en';
                $search = $this->db->search('*', $table, $table . '.' . $colEN, $this->args[1], '=');
            }
        }

        //查询英文列表返回结果为空时，检索日文列表
        if ($search[0] === 1) {
            if (!$search[1]) {
                $colJP = $col . '_jp';
                $search = $this->db->search('*', $table, $table . '.' . $colJP, $this->args[1], '=');
            }
        }

        //无法执行 sql 语句时
        if ($search[0] === 0) {
            $msg = '查询出错，请检查 sql 语句或数据库状态！';
            $target_id = $this->messageInfo['channelID'];
            $is_quote = true;
            $quote = $this->messageInfo['messageID'];
            $type = 1;
            $data = array($msg, $target_id, $is_quote, $quote, $type);
        }

        //三次全部查完
        if ($search[0] === 1) {
            //最终结果为空时
            if (!$search[1]) {
                $msg = '没有结果，请检查参数2是否输入正确！';
                $target_id = $this->messageInfo['channelID'];
                $is_quote = true;
                $quote = $this->messageInfo['messageID'];
                $type = 1;
                $data = array($msg, $target_id, $is_quote, $quote, $type);
            } else {
                //正常检索到结果
                //$msg = $this->processQuestInfo($this->getQuestInfo($search[1]['id']));
                if ($table === 'itemlist') {
                    $msg = '中：' . $search[1]['item'] . "\n英：" . $search[1]['item_en'] . "\n日：" . $search[1]['item_jp'];
                }
                if ($table === 'questlist') {
                    $msg = '中：' . $search[1]['quest'] . "\n英：" . $search[1]['quest_en'] . "\n日：" . $search[1]['quest_jp'];
                }
                $target_id = $this->messageInfo['channelID'];
                $is_quote = true;
                $quote = $this->messageInfo['messageID'];
                $type = 1;
                $data = array($msg, $target_id, $is_quote, $quote, $type);
            }
        }
        return $data;
    }
}
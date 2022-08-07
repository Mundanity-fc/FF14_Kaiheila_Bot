<?php

namespace KaiheilaBot\commandInterpreter\module;

class TextTranslate extends CommandParser
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
            $msg = '指令缺少参数，请查看指令使用帮助（/帮助 翻译）';
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
            $msg = '指令参数不完整，请查看指令使用帮助（/帮助 翻译）';
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
        $data = array();
        $target_id = $this->messageInfo['channelID'];
        $is_quote = true;
        $quote = $this->messageInfo['messageID'];
        $type = 1;
        switch ($this->args[0]) {
            case '物品':
                $col = 'item';
                break;
            case '任务':
                $col = 'quest';
                break;
            case '技能':
                $col = 'action';
                break;
            default:
                $msg = '参数1错误，请查询指令使用方式（/帮助 翻译）';
                $target_id = $this->messageInfo['channelID'];
                $is_quote = true;
                $quote = $this->messageInfo['messageID'];
                $type = 1;
                return array($msg, $target_id, $is_quote, $quote, $type);
        }

        switch ($col) {
            case 'item':
                $id = $this->db->getItemID($this->args[1]);
                if ($id[0] === 0) {
                    $msg = $id[1];
                    return array($msg, $target_id, $is_quote, $quote, $type);
                }
                if ($id[1] === 0) {
                    $msg = '没有结果，请检查参数2是否输入正确！（英文文本区分大小写）';
                    return array($msg, $target_id, $is_quote, $quote, $type);
                }
                $name = $this->db->getItemName($id[1]);
                if ($name[0] === 0) {
                    $msg = $name[1];
                    return array($msg, $target_id, $is_quote, $quote, $type);
                }
                if ($name[1] === 0) {
                    $msg = '没有结果，请检查参数2是否输入正确！（英文文本区分大小写）';
                    return array($msg, $target_id, $is_quote, $quote, $type);
                }
                $msg = '中：' . $name[1][0] . "\n英：" . $name[1][1] . "\n日：" . $name[1][2];
                return array($msg, $target_id, $is_quote, $quote, $type);
            case 'quest':
                $id = $this->db->getQuestID($this->args[1]);
                if ($id[0] === 0) {
                    $msg = $id[1];
                    return array($msg, $target_id, $is_quote, $quote, $type);
                }
                if ($id[1] === 0) {
                    $msg = '没有结果，请检查参数2是否输入正确！（英文文本区分大小写）';
                    return array($msg, $target_id, $is_quote, $quote, $type);
                }
                $name = $this->db->getQuestName($id[1]);
                if ($name[0] === 0) {
                    $msg = $name[1];
                    return array($msg, $target_id, $is_quote, $quote, $type);
                }
                if ($name[1] === 0) {
                    $msg = '没有结果，请检查参数2是否输入正确！（英文文本区分大小写）';
                    return array($msg, $target_id, $is_quote, $quote, $type);
                }
                $msg = '中：' . $name[1][0] . "\n英：" . $name[1][1] . "\n日：" . $name[1][2];
                return array($msg, $target_id, $is_quote, $quote, $type);
            case 'action':
                $id = $this->db->getActionID($this->args[1]);
                if ($id[0] === 0) {
                    $msg = $id[1];
                    return array($msg, $target_id, $is_quote, $quote, $type);
                }
                if ($id[1] === 0) {
                    $msg = '没有结果，请检查参数2是否输入正确！（英文文本区分大小写）';
                    return array($msg, $target_id, $is_quote, $quote, $type);
                }
                $name = $this->db->getActionName($id[1]);
                if ($name[0] === 0) {
                    $msg = $name[1];
                    return array($msg, $target_id, $is_quote, $quote, $type);
                }
                if ($name[1] === 0) {
                    $msg = '没有结果，请检查参数2是否输入正确！（英文文本区分大小写）';
                    return array($msg, $target_id, $is_quote, $quote, $type);
                }
                $msg = '中：' . $name[1][0] . "\n英：" . $name[1][1] . "\n日：" . $name[1][2];
                return array($msg, $target_id, $is_quote, $quote, $type);
        }

        $msg = '意料外的情况，请联系开发者，并反馈错误代码：TTE01';
        return array($msg, $target_id, $is_quote, $quote, $type);
    }
}
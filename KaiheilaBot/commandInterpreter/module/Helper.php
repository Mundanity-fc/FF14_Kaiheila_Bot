<?php

namespace KaiheilaBot\commandInterpreter\module;

class Helper extends CommandParser
{
    public function run($command, $msgInfo): array
    {
        $this->commandList = $command;
        $this->messageInfo = $msgInfo;

        return $this->getHelper();
    }

    private function getHelper(): array
    {
        if (count($this->commandList) === 1) {
            $msg = "FF14 Bot 使用帮助（命令列表）\n";
            $msg .= "/任务 [参数(必须)]  ---  查询指定的任务信息。参数内容为欲查询的任务名\n";
            $msg .= "/翻译 [参数1(必须)] [参数2(必须)]  ---  显示指定内容对应的中/英/日文翻译。\n";
            $msg .= "/状态  ---  显示机器人的运行状态。\n";
            $msg .= "/帮助 [参数(可选)]  ---  显示本条帮助。参数可以为其他指令，此时则显示对应指令的使用帮助，如：/帮助 任务";
            $target_id = $this->messageInfo['channelID'];
            $is_quote = true;
            $quote = $this->messageInfo['messageID'];
            $type = 1;
            return array($msg, $target_id, $is_quote, $quote, $type);
        }
        $target_id = $this->messageInfo['channelID'];
        $is_quote = true;
        $quote = $this->messageInfo['messageID'];
        $type = 1;
        switch ($this->commandList[1]) {
            case '任务':
                $msg = "/任务 [参数(必须)]  ---  查询指定的任务信息。\n";
                $msg .= "参数内容为欲查询的任务名，可以为中文、英文和日文\n";
                $msg .= "当仅想查询翻译时，推荐使用翻译命令，可以减少机器人网络开销\n";
                $msg .= "例：/任务 冒险者入门\n";
                $msg .= "例：/任务 Close to Home\n";
                $msg .= '例：/任务 冒険者への手引き';
                break;
            case '翻译':
                $msg = "/翻译 [参数1(必须)] [参数2(必须)]  ---  显示指定内容对应的中/英/日文翻译。\n";
                $msg .= "参数1为文本类型，可选内容为“物品”或“任务”\n";
                $msg .= "参数2为对应物品或任务的名称，可以为中文、英文和日文\n";
                $msg .= "例：/翻译 物品 金币\n";
                $msg .= "例：/翻译 物品 gil\n";
                $msg .= "例：/翻译 物品 ギル\n";
                $msg .= "例：/翻译 任务 冒险者入门\n";
                $msg .= "例：/翻译 任务 Close to Home\n";
                $msg .= '例：/翻译 任务 冒険者への手引き';
                break;
            case '状态':
                $msg = "/状态  ---  显示机器人的运行状态。\n";
                $msg .= "包含机器人的运行时长及指令处理状态等\n";
                $msg .= '可以作为机器人卡死检测';
                break;
            default:
                $msg = "参数错误，请检查！";
                break;
        }
        return array($msg, $target_id, $is_quote, $quote, $type);
    }
}
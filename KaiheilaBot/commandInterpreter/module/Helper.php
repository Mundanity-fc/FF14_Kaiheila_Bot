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
            $msg = "**FF14 Bot 使用帮助（命令列表）**\n";
            $msg .= "\n";
            $msg .= "/任务 [参数**(必须)**]  ---  查询指定的任务信息。参数内容为欲查询的任务名\n---\n";
            $msg .= "/翻译 [参数1**(必须)**] [参数2**(必须)**]  ---  显示指定内容对应的中/英/日文翻译。\n---\n";
            $msg .= "/价格 [-i=参数1**(必须)**] [-w=参数2**(必须)**] [-c=参数3*(可选)*] [-h=参数4*(可选)*] [-t=参数5*(可选)*] [-d=参数6*(可选)*]  ---  查询指定物品的价格。\n---\n";
            $msg .= "/状态  ---  显示机器人的运行状态。\n---\n";
            $msg .= "/时尚  ---  返回游玩C哩酱的时尚品鉴文档链接。\n---\n";
            $msg .= "/帮助 [参数*(可选)*]  ---  显示本条帮助。参数可以为其他指令，此时则显示对应指令的使用帮助，如：/帮助 任务\n---\n";
            $target_id = $this->messageInfo['channelID'];
            $is_quote = true;
            $quote = $this->messageInfo['messageID'];
            $type = 9;
            return array($msg, $target_id, $is_quote, $quote, $type);
        }
        $target_id = $this->messageInfo['channelID'];
        $is_quote = true;
        $quote = $this->messageInfo['messageID'];
        $type = 9;
        switch ($this->commandList[1]) {
            case '任务':
                $msg = "**任务查询**\n";
                $msg .= "/任务 [参数(必须)]  ---  查询指定的任务信息。\n---\n";
                $msg .= "参数内容为欲查询的任务名，可以为**中文、英文和日文**\n";
                $msg .= "当仅想查询翻译时，推荐使用**翻译**命令，可以减少机器人网络开销\n---\n";
                $msg .= "> 例：/任务 冒险者入门\n";
                $msg .= "> 例：/任务 Close to Home\n";
                $msg .= '> 例：/任务 冒険者への手引き';
                break;
            case '翻译':
                $msg = "**翻译查询**\n";
                $msg .= "/翻译 [参数1(必须)] [参数2(必须)]  ---  显示指定内容对应的**中/英/日文**翻译。\n---\n";
                $msg .= "参数1为文本类型，可选内容为(ins)**“物品”**、**“任务”**或**“技能”**(ins)\n";
                $msg .= "参数2为对应物品或任务的名称，可以为**中文、英文和日文**\n---\n";
                $msg .= "> 例：/翻译 物品 金币\n";
                $msg .= "> 例：/翻译 物品 gil\n";
                $msg .= "> 例：/翻译 物品 ギル\n";
                $msg .= "> 例：/翻译 任务 冒险者入门\n";
                $msg .= "> 例：/翻译 任务 Close to Home\n";
                $msg .= "> 例：/翻译 任务 冒険者への手引き\n";
                $msg .= "> 例：/翻译 技能 任务指令\n";
                $msg .= "> 例：/翻译 技能 Interaction\n";
                $msg .= "> 例：/翻译 技能 イベントアクション\n";
                break;
            case '价格':
                $msg = "**价格查询**\n";
                $msg .= "/价格 [-i=参数1(必须)] [-w=参数2(必须)] [-c=参数3(可选)] [-h=参数4(可选)] [-t=参数5(可选)] [-d=参数6(可选)] [-g=参数7(可选)]  ---  查询指定物品的价格。\n---\n";
                $msg .= "参数1为查询的物品名称（中/英/日文皆可），例如：“**-i=火之碎晶**”\n";
                $msg .= "\n";
                $msg .= "参数2为查询的服务器范围，可以是区域名，如：“**-w=中国**”或：“**-w=Japan**”，也可以是大区名，如：“**-w=陆行鸟**”或：“**-w=Mana**”，还可以是服务器名，如：“**-w=晨曦王座**”或：“**-w=Titan**”\n";
                $msg .= "\n";
                $msg .= "参数3为查询的记录数量，同时对出售列表和售出列表生效（不填写时，默认为10）。如：“**-c=5**”，表示出售列表和售出列表都最多显示5条记录\n";
                $msg .= "\n";
                $msg .= "参数4为是否包括hq物品（不填写时，默认包含hq和nq）。当参数填写为“**-h=1**”时，表示仅查询hq价格；当参数填写为“**-h=0**”时，表示仅查询nq价格。\n";
                $msg .= "\n";
                $msg .= "参数5为查询的类型（不填写时，默认返回出售列表和售出列表）。当参数填写为“**-t=B**”时，表示仅查询出售列表（买模式）；当参数填写为“**-t=S**”时，表示仅查询售出列表（卖模式）。\n";
                $msg .= "\n";
                $msg .= "参数6为指定查询距今多少时间前的记录（单位为毫秒，不填写时，默认为7天）\n";
                $msg .= "\n";
                $msg .= "参数7为指定查询结果是否包括税收（不填写时，默认包含税收，即和游戏内数值保持一致），当携带参数 **-g=1** 时，返回的结果将移除税收价格，即显示出售者实际能获得的金币数值\n";
                $msg .= "\n---\n";
                $msg .= "**本指令不允许对服务器名进行缩写**，即如果想查询鸟区，则服务器必须填写陆行鸟，同时，**外服的服务器名必须是英文**，即如果向查询日服的元素大区，则服务器必须填写为Elemental\n";
                $msg .= "**本指令的参数顺序不固定**，即“**/价格 -i=火之碎晶 -w=陆行鸟**” 与 “**/价格 -w=陆行鸟 -i=火之碎晶**” 的作用相同\n";
                $msg .= "**本指令必须携带 *-i参数* 和 *-w参数*，即想要查询价格必须指定服务器范围和物品名称**\n---\n";
                $msg .= "由于本指令参数较多，为了便于理解，给出以下样例及其说明：\n";
                $msg .= "例：/价格 -i=火之碎晶 -w=中国  ---  查询7天内**国服所有服务器**内的火之碎晶售出情况和目前的出售列表（各显示10条）\n";
                $msg .= "例：/价格 -i=火之碎晶 -w=陆行鸟  ---  查询7天内**鸟区**的火之碎晶售出情况和目前的出售列表（各显示10条）\n";
                $msg .= "例：/价格 -i=火之碎晶 -w=晨曦王座  ---  查询7天内**鸟区的晨曦服**里的火之碎晶售出情况和目前的出售列表（各显示10条）\n";
                $msg .= "例：/价格 -i=麻布 -w=陆行鸟 -h=1  ---  查询7天内**鸟区**里的**高品质**麻布的售出情况和目前的出售列表（各显示10条）\n";
                $msg .= "例：/价格 -i=麻布 -w=陆行鸟 -t=B  ---  查询**鸟区**里麻布**目前的出售列表（显示10条）**\n";
                $msg .= "例：/价格 -i=麻布 -w=陆行鸟 -t=S  ---  查询7天内**鸟区**里麻布**的售出情况（显示最近10条）**\n";
                $msg .= "例：/价格 -i=麻布 -w=陆行鸟 -t=B -c=20  ---  查询**鸟区**里麻布**目前的出售列表（显示20条）**\n";
                break;
            case '状态':
                $msg = "**状态查询**\n";
                $msg .= "/状态  ---  显示机器人的运行状态。\n---\n";
                $msg .= "包含机器人的运行时长及指令处理状态等\n";
                $msg .= '可以作为机器人卡死检测';
                break;
            case '时尚':
                $msg = "**时尚品鉴攻略**\n";
                $msg .= "/时尚  ---  返回游玩C哩酱的时尚品鉴文档链接。\n---\n";
                $msg .= "无特殊说明\n";
                break;
            default:
                $msg = "参数错误，请检查！";
                break;
        }
        return array($msg, $target_id, $is_quote, $quote, $type);
    }
}

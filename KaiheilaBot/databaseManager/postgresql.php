<?php

namespace Kaiheila\databaseManager;

class postgresql
{
    private $dbConn;

    public function __construct($dbConn_opts)
    {
        $this->dbConn = pg_connect($dbConn_opts);
    }

    //搜索函数，暂时公开化，未来将私有化，并用具体类型搜索函数调用
    private function search($target, $table, $where1, $where2, $opt)
    {
        //当查询的条件包含 ' 时的操作
        $leng = strlen($where2);
        $quote = strcspn($where2, "'");
        if ($leng !== $quote) {
            $array = str_split($where2);
            $arr_len = count($array);
            for ($i = 0; $i < $arr_len; $i++) {
                if ($array[$i] !== "'") {
                    continue;
                }
                $temp = array_chunk($array, $i + 1);
                $temp[0][] = "'";
                $array = array();
                foreach ($temp as $x) {
                    foreach ($x as $j) {
                        $array[] = $j;
                    }
                }
                $i++;
                $arr_len++;
            }
            $where2 = implode($array);
        }

        $sql = 'select ' . $target . ' from "' . $table . '" where ' . $where1 . ' ' . $opt . ' \'' . $where2 . '\';';
        $result = pg_query($this->dbConn, $sql);
        if (!$result) {
            return array(0, 'error');
        }
        return array(1, pg_fetch_assoc($result));
    }

    //查询指定服务器id是否存在于本地数据库中
    public function isExistServer($id): bool
    {
        $search = $this->search('*', 'serverlist', 'serverlist.server_id', $id, '=');
        if ($search[0] === 0) {
            return false;
        } else if (!$search[1]) {
            return false;
        } else {
            return true;
        }
    }

    public function insertServer($id): bool
    {
        $sql = 'insert into serverlist (server_id) values (' . $id . ');';
        $result = pg_query($this->dbConn, $sql);
        if (!$result) {
            return false;
        }
        return true;
    }

    public function getServerCount(): int
    {
        $sql = "select count(*) from serverlist;";
        $result = pg_query($this->dbConn, $sql);
        if (!$result) {
            return 0;
        }
        $array = pg_fetch_assoc($result);
        return (int)$array['count'];
    }

    //搜索指定任务名的 ID
    public function getQuestID($quest): array
    {
        $data = '数据库出错或 SQL 语句出错，请联系开发者';
        $result = 0;
        //检索中文列表
        $search = $this->search('questlist.id', 'questlist', 'quest', $quest, '=');

        //查询中文列表返回结果为空时，检索英文列表
        if (($search[0] === 1) && !$search[1]) {
            $search = $this->search('questlist.id', 'questlist', 'quest_en', $quest, '=');
        }

        //查询英文列表返回结果为空时，检索日文列表
        if (($search[0] === 1) && !$search[1]) {
            $search = $this->search('questlist.id', 'questlist', 'quest_jp', $quest, '=');
        }

        //无法执行 sql 语句时
        if ($search[0] === 0) {
            return array($result, $data);
        }

        //三次全部查完
        if ($search[0] === 1) {
            $result = 1;
            //最终结果为空时
            if (!$search[1]) {
                $data = 0;
            } else {
                //正常检索到结果
                $data = $search[1]['id'];
            }
        }
        return array($result, $data);
    }

    //搜索指定 ID 的任务名（返回中/英/日）
    public function getQuestName($id)
    {
        $data = '数据库出错或 SQL 语句出错，请联系开发者';
        $result = 0;
        $search = $this->search('*', 'questlist', 'id', $id, '=');

        //无法执行 sql 语句时
        if ($search[0] === 0) {
            return array($result, $data);
        }

        //正常执行结束后
        if ($search[0] === 1) {
            $result = 1;
            //无结果时
            if (!$search[1]) {
                $data = 0;
            } else {
                //正常检索到结果
                $data = array($search[1]['quest'], $search[1]['quest_en'], $search[1]['quest_jp']);
            }
        }
        return array($result, $data);
    }

    //搜索指定道具名的 ID
    public function getItemID($item)
    {
        $data = '数据库出错或 SQL 语句出错，请联系开发者';
        $result = 0;
        //检索中文列表
        $search = $this->search('itemlist.id', 'itemlist', 'item', $item, '=');

        //查询中文列表返回结果为空时，检索英文列表
        if (($search[0] === 1) && !$search[1]) {
            $search = $this->search('itemlist.id', 'itemlist', 'item_en', $item, '=');
        }

        //查询英文列表返回结果为空时，检索日文列表
        if (($search[0] === 1) && !$search[1]) {
            $search = $this->search('itemlist.id', 'itemlist', 'item_jp', $item, '=');
        }

        //无法执行 sql 语句时
        if ($search[0] === 0) {
            return array($result, $data);
        }

        //三次全部查完
        if ($search[0] === 1) {
            $result = 1;
            //最终结果为空时
            if (!$search[1]) {
                $data = 0;
            } else {
                //正常检索到结果
                $data = $search[1]['id'];
            }
        }
        return array($result, $data);
    }

    //搜索指定 ID 的道具名（返回中/英/日）
    public function getItemName($id): array
    {
        $data = '数据库出错或 SQL 语句出错，请联系开发者';
        $result = 0;
        $search = $this->search('*', 'itemlist', 'id', $id, '=');

        //无法执行 sql 语句时
        if ($search[0] === 0) {
            return array($result, $data);
        }

        //正常执行结束后
        if ($search[0] === 1) {
            $result = 1;
            //无结果时
            if (!$search[1]) {
                $data = 0;
            } else {
                //正常检索到结果
                $data = array($search[1]['item'], $search[1]['item_en'], $search[1]['item_jp']);
            }
        }
        return array($result, $data);
    }

    //搜索指定技能名的 ID
    public function getActionID($quest): array
    {
        $data = '数据库出错或 SQL 语句出错，请联系开发者';
        $result = 0;
        //检索中文列表
        $search = $this->search('actionlist.id', 'actionlist', 'action', $quest, '=');

        //查询中文列表返回结果为空时，检索英文列表
        if (($search[0] === 1) && !$search[1]) {
            $search = $this->search('actionlist.id', 'actionlist', 'action_en', $quest, '=');
        }

        //查询英文列表返回结果为空时，检索日文列表
        if (($search[0] === 1) && !$search[1]) {
            $search = $this->search('actionlist.id', 'actionlist', 'action_jp', $quest, '=');
        }

        //无法执行 sql 语句时
        if ($search[0] === 0) {
            return array($result, $data);
        }

        //三次全部查完
        if ($search[0] === 1) {
            $result = 1;
            //最终结果为空时
            if (!$search[1]) {
                $data = 0;
            } else {
                //正常检索到结果
                $data = $search[1]['id'];
            }
        }
        return array($result, $data);
    }

    //搜索指定 ID 的技能名（返回中/英/日）
    public function getActionName($id)
    {
        $data = '数据库出错或 SQL 语句出错，请联系开发者';
        $result = 0;
        $search = $this->search('*', 'actionlist', 'id', $id, '=');

        //无法执行 sql 语句时
        if ($search[0] === 0) {
            return array($result, $data);
        }

        //正常执行结束后
        if ($search[0] === 1) {
            $result = 1;
            //无结果时
            if (!$search[1]) {
                $data = 0;
            } else {
                //正常检索到结果
                $data = array($search[1]['action'], $search[1]['action_en'], $search[1]['action_jp']);
            }
        }
        return array($result, $data);
    }

    //
    public function getJournalCategoryName($id)
    {
        $data = '数据库出错或 SQL 语句出错，请联系开发者';
        $result = 0;
        $search = $this->search('*', 'JournalCategory', 'id', $id, '=');

        //无法执行 sql 语句时
        if ($search[0] === 0) {
            return array($result, $data);
        }

        //正常执行结束后
        if ($search[0] === 1) {
            $result = 1;
            //无结果时
            if (!$search[1]) {
                $data = array('', '', '');
            } else {
                //正常检索到结果
                $data = array($search[1]['cn'], $search[1]['en'], $search[1]['jp']);
            }
        }
        return array($result, $data);
    }

    //
    public function getJournalGenreName($id)
    {
        $data = '数据库出错或 SQL 语句出错，请联系开发者';
        $result = 0;
        $search = $this->search('*', 'JournalGenre', 'id', $id, '=');

        //无法执行 sql 语句时
        if ($search[0] === 0) {
            return array($result, $data);
        }

        //正常执行结束后
        if ($search[0] === 1) {
            $result = 1;
            //无结果时
            if (!$search[1]) {
                $data = array('', '', '');
            } else {
                //正常检索到结果
                $data = array($search[1]['cn'], $search[1]['en'], $search[1]['jp']);
            }
        }
        return array($result, $data);
    }

    //
    public function getQuestNPCName($id)
    {
        $data = '数据库出错或 SQL 语句出错，请联系开发者';
        $result = 0;
        $search = $this->search('*', 'ENpcResident', 'id', $id, '=');

        //无法执行 sql 语句时
        if ($search[0] === 0) {
            return array($result, $data);
        }

        //正常执行结束后
        if ($search[0] === 1) {
            $result = 1;
            //无结果时
            if (!$search[1]) {
                $data = array('', '', '');
            } else {
                //正常检索到结果
                $data = array($search[1]['cn'], $search[1]['en'], $search[1]['jp']);
            }
        }
        return array($result, $data);
    }

    //
    public function getQuestPlaceName($id)
    {
        $data = '数据库出错或 SQL 语句出错，请联系开发者';
        $result = 0;
        $search = $this->search('*', 'PlaceName', 'id', $id, '=');

        //无法执行 sql 语句时
        if ($search[0] === 0) {
            return array($result, $data);
        }

        //正常执行结束后
        if ($search[0] === 1) {
            $result = 1;
            //无结果时
            if (!$search[1]) {
                $data = array('', '', '');
            } else {
                //正常检索到结果
                $data = array($search[1]['cn'], $search[1]['en'], $search[1]['jp']);
            }
        }
        return array($result, $data);
    }

    //
    public function getClassJobCategoryName($id)
    {
        $data = '数据库出错或 SQL 语句出错，请联系开发者';
        $result = 0;
        $search = $this->search('*', 'ClassJobCategory', 'id', $id, '=');

        //无法执行 sql 语句时
        if ($search[0] === 0) {
            return array($result, $data);
        }

        //正常执行结束后
        if ($search[0] === 1) {
            $result = 1;
            //无结果时
            if (!$search[1]) {
                $data = array('', '', '');
            } else {
                //正常检索到结果
                $data = array($search[1]['cn'], $search[1]['en'], $search[1]['jp']);
            }
        }
        return array($result, $data);
    }
}
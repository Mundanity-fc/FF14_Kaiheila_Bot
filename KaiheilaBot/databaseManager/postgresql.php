<?php

namespace Kaiheila\databaseManager;

class postgresql
{
    private $dbConn;

    public function __construct($dbConn_opts)
    {
        $this->dbConn = pg_connect($dbConn_opts);
    }

    public function search($target, $table, $where1, $where2, $opt)
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

        $sql = 'select ' . $target . ' from ' . $table . ' where ' . $where1 . ' ' . $opt . ' \'' . $where2 . '\';';
        $result = pg_query($this->dbConn, $sql);
        if (!$result) {
            return array(0, 'error');
        }
        return array(1, pg_fetch_assoc($result));
    }
}
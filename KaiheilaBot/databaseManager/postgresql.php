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
        $sql = 'select ' . $target . ' from ' . $table . ' where ' . $where1 . ' ' . $opt . ' \'' . $where2 . '\';';
        $result = pg_query($this->dbConn, $sql);
        if (!$result) {
            return array(0, 'error');
        }
        return array(1, pg_fetch_assoc($result));
    }
}
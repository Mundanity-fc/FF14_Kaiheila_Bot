<?php

namespace KaiheilaBot\cardMessage;

class MultiColumnText
{
    public $type = 'paragarhp';
    public $cols;
    public array $fields = [];

    public function __construct($cols = 3)
    {
        $this->cols = $cols;
    }

    public function insert($text, $type = 'plain-text'): void
    {
        array_push($this->fields, json_decode(json_encode(array('type' => $type, 'content' => $text))));
    }
}
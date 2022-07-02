<?php

namespace KaiheilaBot\cardMessage;

class MultiColumnText
{
    public $type = 'section';
    public $text;

    public function __construct($cols = 3)
    {
        $this->text = json_decode(json_encode(array('type' => 'paragraph', 'cols' => $cols, 'fields' => [])));
    }

    public function insert($text, $type = 'plain-text'): void
    {
        array_push($this->text->fields, json_decode(json_encode(array('type' => $type, 'content' => $text))));
    }
}
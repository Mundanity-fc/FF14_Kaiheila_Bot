<?php

namespace KaiheilaBot\cardMessage;

class PlainText
{
    public $type;
    public $text;

    public function __construct($text, $text_type = 'plain-text', $content_type = 'section')
    {
        $this->text = json_decode(json_encode(array('type' => $text_type, 'content' => $text)));
        $this->type = $content_type;
    }
}
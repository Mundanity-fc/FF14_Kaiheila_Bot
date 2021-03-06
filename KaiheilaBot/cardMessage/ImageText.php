<?php

namespace KaiheilaBot\cardMessage;

class ImageText
{
    public $type = 'section';
    public $text;
    public $mode;
    public $accessory;

    public function __construct($text, $img, $text_type = 'plain-text', $mode = 'left', $size = 'sm')
    {
        $this->text = json_decode(json_encode(array('type' => $text_type, 'content' => $text)));
        $this->mode = $mode;
        $this->accessory = json_decode(json_encode(array(
            'type' => 'image',
            'src' => $img,
            'size' => $size
        )));
    }
}
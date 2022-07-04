<?php

namespace KaiheilaBot\cardMessage;

class Image
{
    public $type = 'container';
    public $elements = [];

    public function __construct($img)
    {
        array_push($this->elements, json_decode(json_encode(array('type' => 'image', 'src' => $img))));
    }
}
<?php

namespace KaiheilaBot\cardMessage;

class Image
{
    public $type = 'container';
    public $elements;

    public function __construct($img)
    {
        $this->elements = array('type' => 'image', 'src' => $img);
    }
}
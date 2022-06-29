<?php

namespace KaiheilaBot\cardMessage;

class Card
{
    public $type = 'card';
    public $theme;
    public $size;
    public array $modules = [];

    public function __construct($theme = 'primary', $size = 'lg')
    {
        $this->theme = $theme;
        $this->size = $size;
    }

    public function insert($target)
    {
        array_push($this->modules, $target);
    }
}
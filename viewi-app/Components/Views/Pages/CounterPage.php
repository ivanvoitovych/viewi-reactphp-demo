<?php

namespace Components\Views\Pages;

use Viewi\BaseComponent;

class CounterPage extends BaseComponent
{
    public int $page;
    
    public function __init(int $page = 0)
    {
        $this->page = $page;
    }
}

<?php

namespace Components\Views\Pages;

use Viewi\Components\BaseComponent;

class CounterPage extends BaseComponent
{
    public int $page;
    
    public function __construct(int $page = 0)
    {
        $this->page = $page;
    }
}

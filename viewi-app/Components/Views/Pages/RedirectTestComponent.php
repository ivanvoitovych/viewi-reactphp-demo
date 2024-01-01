<?php

namespace Components\Views\Pages;

use Viewi\Components\BaseComponent;
use Viewi\Components\Routing\ClientRoute;

class RedirectTestComponent extends BaseComponent
{
    public function __construct(private ClientRoute $router)
    {
    }

    public function init()
    {
        $this->router->navigate('/');
    }
}

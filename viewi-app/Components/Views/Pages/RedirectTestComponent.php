<?php

namespace Components\Views\Pages;

use Viewi\BaseComponent;
use Viewi\Common\ClientRouter;

class RedirectTestComponent extends BaseComponent
{
    public function __init(ClientRouter $router)
    {
        $router->navigate('/');
    }
}

<?php

namespace Components\Views\Pages;

use Viewi\BaseComponent;
use Viewi\Common\ClientRouter;

class CurrentUrlTestPage extends BaseComponent
{
    public ?string $currentUrl = null;

    public function __init(ClientRouter $router)
    {
        $this->currentUrl = $router->getUrl();
    }
}

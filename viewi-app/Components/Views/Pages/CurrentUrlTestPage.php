<?php

namespace Components\Views\Pages;

use Viewi\Components\BaseComponent;
use Viewi\Components\Routing\ClientRoute;

class CurrentUrlTestPage extends BaseComponent
{
    public ?string $currentUrl = null;

    public function __construct(ClientRoute $router)
    {
        $this->currentUrl = $router->getUrl();
    }
}

<?php

namespace Components\Views\TodoApp;

use Viewi\Components\BaseComponent;
use Viewi\Components\DOM\DomEvent;

class TodoApp extends BaseComponent
{
    public string $text = '';
    public array $items = [];

    public function handleSubmit(DomEvent $event)
    {
        $event->preventDefault();
        if (strlen($this->text) == 0) {
            return;
        }
        $this->items = [...$this->items, $this->text];
        $this->text = '';
    }
}

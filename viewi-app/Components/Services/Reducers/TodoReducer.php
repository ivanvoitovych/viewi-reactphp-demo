<?php

namespace Components\Services\Reducers;

use Viewi\DI\Singleton;

#[Singleton]
class TodoReducer
{
    public array $items = [];

    public function addNewItem(string $text)
    {
        $this->items = [...$this->items, $text];
    }
}

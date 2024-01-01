<?php

namespace Components\Services\Reducers;

use Viewi\DI\Singleton;

#[Singleton]
class CounterReducer
{
    public int $count = 0;

    public function increment()
    {
        $this->count++;
    }

    public function decrement()
    {
        $this->count--;
    }
}

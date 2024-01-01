<?php

namespace Components\Views\StatefulCounter;

use Components\Services\Reducers\CounterReducer;
use Viewi\Components\BaseComponent;

class StatefulCounter extends BaseComponent
{
    public function __construct(public CounterReducer $counter)
    {
    }
}

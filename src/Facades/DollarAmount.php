<?php

namespace Knighttower\Toolbox\Facades;

use Illuminate\Support\Facades\Facade;
use Knighttower\Toolbox\Helpers\DollarAmountHelper;

/**
 * @see \Knighttower\Toolbox\Helpers\DollarAmountHelper
 */
class DollarAmount extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return new DollarAmountHelper();
    }
}
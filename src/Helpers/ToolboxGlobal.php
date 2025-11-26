<?php

use Knighttower\Toolbox\Helpers\DateHelper;

if (!function_exists('emptyOrValue')) {
    /**
     * Global function to handle if comparison of empty values
     * Pass this wrapped like: emptyOrValue(($value ?? null), $default),
     * so that it wont fail on unset props when working with objects or arrays
     *
     * @param mixed $value
     * @param mixed $default Value that should be used in case of Empty
     * @return mixed Returns the Original value, default or just null.
     */
    function emptyOrValue($value, $default = null)
    {
        return !empty($value) ? $value : $default;
    }
}

if (!function_exists('dateHelper')) {
    /**
     * Global function to expose the dateHelper static class and its methods
     *
     * @see \Knighttower\Toolbox\Helpers\DateHelper
     * @return mixed
     */
    function dateHelper()
    {
        return app(DateHelper::class);
    }
}


if (!function_exists('proxy')) {
    /**
     * Global function to expose the proxy static class and its methods
     *
     * @see \Knighttower\Toolbox\Helpers\ProxyHelper
     * @return mixed
     */
    function proxy()
    {
        return app('ProxyHelper');
    }
}

if (!function_exists('user')) {
    /**
     * Global function to expose the current authenticated user
     *
     * @return mixed
     */
    function user()
    {
        if (!empty(auth()->user())) {
            return auth()->user();
        };
    }
}
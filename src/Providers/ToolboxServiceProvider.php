<?php

namespace Knighttower\Toolbox\Providers;

// Framework
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

// Package
use Knighttower\Toolbox\Helpers\DateHelper;
use Knighttower\Toolbox\Helpers\DBHelper;
use Knighttower\Toolbox\Facades\DollarAmount;
use Knighttower\Toolbox\Helpers\LogInfo;
use Knighttower\Toolbox\Facades\RequestHelper;
use Knighttower\Toolbox\Helpers\StringHelper;
use Knighttower\Toolbox\Helpers\UrlHelper;
use Knighttower\Toolbox\Facades\LocalApiPort;
use Knighttower\Toolbox\Helpers\ProxyHelper;

class ToolboxServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->booting(function () {
            $loader = AliasLoader::getInstance();
            $loader->alias('DateHelper', DateHelper::class);
            $loader->alias('DBHelper', DBHelper::class);
            $loader->alias('DollarAmount', DollarAmount::class);
            $loader->alias('LogInfo', LogInfo::class);
        });
        $this->app->bind('ProxyHelper', function ($app) {
            return new ProxyHelper();
        });
    }
}
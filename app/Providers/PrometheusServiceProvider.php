<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Prometheus\Storage\Redis as PrometheusRedis;

class PrometheusServiceProvider extends ServiceProvider
{
    public function boot()
    {
        PrometheusRedis::setDefaultOptions([
            'host' => 'redis-pay-service',
            'port' => 6379,
            'password' => null,
            'timeout' => 0.1, 
            'read_timeout' => '10', 
            'persistent_connections' => false
        ]);
    }

    public function register()
    {
        //
    }
}

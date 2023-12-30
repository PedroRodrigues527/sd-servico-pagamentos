<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Prometheus\RenderTextFormat;

class StatController extends Controller
{

    public function metric()
    {
        
        \Prometheus\Storage\Redis::setDefaultOptions(
            [
                'host' => 'redis-service',
                'port' => 6379,
                'password' => null,
                'timeout' => 0.1, 
                'read_timeout' => '10', 
                'persistent_connections' => false
            ]
        );


        $registry = \Prometheus\CollectorRegistry::getDefault();

        $renderer = new RenderTextFormat();
        $result = $renderer->render($registry->getMetricFamilySamples());

        header('Content-type: ' . RenderTextFormat::MIME_TYPE);
        echo $result;
    }
}

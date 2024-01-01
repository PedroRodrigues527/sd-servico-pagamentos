<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Prometheus\RenderTextFormat;

class StatController extends Controller
{

    public function metric()
    {
        
        $registry = \Prometheus\CollectorRegistry::getDefault();

        $renderer = new RenderTextFormat();
        $result = $renderer->render($registry->getMetricFamilySamples());

        header('Content-type: ' . RenderTextFormat::MIME_TYPE);
        echo $result;
    }
}

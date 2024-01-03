<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CronJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        
    }

    public function handle()
    {
        \Prometheus\CollectorRegistry::getDefault()
        ->getOrRegisterCounter('', 'cron_job', 'Number of Times the cron job Has Been Called')
        ->inc();

    }
}

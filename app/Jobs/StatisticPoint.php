<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\Point\Types\StatisticPoint as StatisticPointService;

class StatisticPoint implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $daily;

    protected $staffsn;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($daily, $staffsn)
    {
        $this->daily = $daily;
        $this->staffsn = $staffsn;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(StatisticPointService $service)
    {
        $service->statisticDaily($this->daily, $this->staffsn);
    }
}

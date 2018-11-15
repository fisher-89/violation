<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\Point\Types\StatisticPoint as StatisticPointService;

class StatisticLogPoint implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $monthly;

    protected $staffsn;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($monthly, $staffsn)
    {
        $this->monthly = $monthly;
        $this->staffsn = $staffsn;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(StatisticPointService $service)
    {
        $service->statisticMonthly($this->monthly, $this->staffsn);
    }
}

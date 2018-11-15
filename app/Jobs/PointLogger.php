<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\PointLogger as PointLoggerService;

class PointLogger implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $staff_sn;

    protected $title;

    protected $eventlog;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($staff_sn, $title, $eventlog)
    {
        $this->staff_sn = $staff_sn;
        $this->title = $title;
        $this->eventlog = $eventlog;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(PointLoggerService $logservice)
    {
        $logservice->createPointLog($this->staff_sn, $this->title, $this->eventlog);
    }
}

<?php

namespace Knighttower\Toolbox\Jobs;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Bus\Queueable;
use Storage;
use Carbon\Carbon;

class LogInfoLogger implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @var mixed
     */
    public $msg;

    /**
     * @var string
     */
    public $doc;

    /**
     * Create a new job instance.
     *
     * @param mixed $msg
     * @param mixed $doc
     * @return void
     */
    public function __construct($msg, $doc)
    {
        $this->msg = $msg;
        $this->doc = $doc;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $date = Carbon::now()->toDateString();
        $timestamp = Carbon::now()->toDateTimeString();

        // Log to specific document file
        $dailyLogEntry = "[{$timestamp}] {$this->msg}" . PHP_EOL;
        Storage::disk('log')->append("{$date}/{$this->doc}.txt", $dailyLogEntry);

        // Log to hourly summary file
        $separator = str_repeat('=', 50);
        $hourlyLogEntry = PHP_EOL . $separator . PHP_EOL
            . "[{$timestamp}] {$this->doc}: {$this->msg}" . PHP_EOL
            . $separator . PHP_EOL;

        Storage::disk('log')->append("{$date}--Hourly--Log.txt", $hourlyLogEntry);
    }
}
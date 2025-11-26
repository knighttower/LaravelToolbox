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
        $msg = $this->msg;
        $doc = $this->doc;
        $date = Carbon::now()->toDateString();

        Storage::disk('log')->prepend("{$date}/{$doc}.txt", $msg);
        $sep = str_repeat('=', 50);
        $formattedMsg  = PHP_EOL . PHP_EOL . $sep . PHP_EOL . PHP_EOL;
        $formattedMsg .= '----> ' . (string)$doc . ' ' . (string)$msg . PHP_EOL . PHP_EOL . $sep . PHP_EOL . PHP_EOL;
        Storage::disk('log')->prepend("{$date}--Hourly--Log.txt", $formattedMsg);
    }
}
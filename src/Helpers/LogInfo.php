<?php

namespace Knighttower\Toolbox\Helpers;

use Carbon\Carbon;
use Knighttower\Toolbox\Jobs\LogInfoLogger;

class LogInfo
{
    /**
     * write the message
     *
     * @param string $msg
     * @param array $context
     * @param string|null $doc
     * @return void
     */
    public static function write(string $msg, array $context = [], ?string $doc = 'info'): void
    {
        if (!empty($context)) {
            $msg .= ' | Context: ' . print_r($context, true);
        }
        $formatMsg = self::setMsg($msg);
        self::save($formatMsg, $doc);
    }

    /**
     * Alias to write
     *
     * @param string $msg
     * @param array $context
     * @param string|null $doc
     * @return void
     */
    public static function info(string $msg, array $context = [], ?string $doc = 'info'): void
    {

        self::write($msg, $context, $doc);
    }


    /**
     * write the error
     *
     * @param string|\Exception $exception
     * @param array $context the context of the error
     * @param bool $throw whether to re-throw the exception after logging
     * @param string|null $doc
     * @return void
     */
    public static function error(string|\Exception $exception, array $context = [], ?string $doc = 'errors', ?bool $throw = false): void
    {
        $context = !empty($context) ? ' | Context: ' . print_r($context, true) : '';
        if (! $exception instanceof \Exception) {
            $exception = new \Exception($exception . $context);
        }
        $formatMsg = self::setError($exception);
        self::save($formatMsg, $doc);
        if ($throw) {
            throw $exception;
        }
    }


    /**
     * save the message
     *
     * @param string $msg
     * @param string $doc
     * @return void
     */
    private static function save($msg, $doc): void
    {
        LogInfoLogger::dispatchSync($msg, $doc);
    }


    /**
     * format the message
     *
     * @param string $msg
     * @return string
     */
    private static function setMsg($msg)
    {
        $data = '----------------------------------------------' . "\n\r";
        $data .= '--Info: ' . $msg . "\n\r";
        $data .= '--Date: ' . Carbon::now()->toRfc850String() . "\n\r";
        $data .= '---------------------------------------------' . "\n\r" . "\n\r";

        return $data;
    }


    /**
     * format the error
     *
     * @param object $exception
     * @return string
     */
    private static function setError($exception)
    {
        $data = '----------------------------------------------' . "\n\r";
        $data .= '--Date: ' . Carbon::now()->toRfc850String() . "\n\r";
        $data .= '--Info: ' . $exception->getMessage() . "\n\r";
        $data .= '--IN Line: ' . $exception->getLine() . "\n\r";
        $data .= '--WITH Exception type: ' . get_class($exception) . "\n\r";
        $data .= '--AND Code #: ' . $exception->getCode() . "\n\r";
        $data .= '--IN File: ' . $exception->getFile() . "\n\r";
        $data .= '---------------------------------------------' . "\n\r" . "\n\r";

        return $data;
    }
}

<?php

namespace common\traits;

trait SseTrait
{
    protected function setupSseHeaders()
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('Content-Encoding: none');
        @ini_set('zlib.output_compression', 0);
        @ini_set('output_buffering', 'off');
        @ini_set('implicit_flush', 1);
        while (ob_get_level() > 0) ob_end_flush();
        ob_implicit_flush(true);
    }
}
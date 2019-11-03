<?php

namespace GTrader;

class DevUtil
{
    /**
     * jsonPrint
     * @param  string $json
     * @return string
     */
    public static function jsonPrint(string $json): string
    {
        $printer = new \Localheinz\Json\Printer\Printer();
        return $printer->print($json, '  ');
    }


    /**
     * backtrace
     * @return string
     */
    public static function backtrace(bool $log = false)
    {
        $e = new \Exception();
        $trace = explode("\n", $e->getTraceAsString());
        $trace = array_reverse($trace);
        array_shift($trace);
        array_pop($trace);
        $length = count($trace);
        $result = [];
        for ($i = 0; $i < $length; $i++) {
            $result[] = ($i + 1).')'.substr($trace[$i], strpos($trace[$i], ' '));
        }
        if ($log) {
            foreach ($result as $line) {
                Log::sparse($line);
            }
            return true;
        }
        return implode("\n", $result);
    }


    /**
     * fdump
     * @param  mixed $content
     * @param  string $path
     * @return
     */
    public static function fdump($content, string $path = '/tmp/dump')
    {
        $dir = pathinfo($path, PATHINFO_DIRNAME);
        if (!file_exists($dir)) {
            $oldumask = umask(0);
            mkdir($dir, 0775, true);
            umask($oldumask);
        }
        fwrite(
            $fd = fopen($path, 'w'),
            $content
        );
        fclose($fd);
        return;
    }


    /**
     * memdump
     * @param  string $path
     * @return
     */
    public static function memdump(string $path = '/tmp/memdump.json')
    {
        if (!function_exists('meminfo_dump')) {
            Log::debug('php_meminfo extension not installed');
            return;
        }
        $fd = fopen($path, 'w');
        meminfo_dump($fd);
        fclose($fd);
    }


    public static function eloquentSql($qbuilder)
    {
        return vsprintf(str_replace(['?'], ['\'%s\''], $qbuilder->toSql()), $qbuilder->getBindings());
    }

}

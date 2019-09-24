<?php

namespace GTrader;

use Jfcherng\Diff\Differ;
use Jfcherng\Diff\DiffHelper;
use Jfcherng\Diff\Factory\RendererFactory;

class DevUtil
{
    /**
     * diff
     * @param  mixed $old
     * @param  mixed $new
     * @return string
     */
    public static function diff($old, $new): string
    {
        return DiffHelper::calculate(
            print_r($old, true),
            print_r($new, true),
            'SideBySide',
            [],
            ['detailLevel' => 'char', 'spacesToNbsp' => true]
        );
    }


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
    public static function backtrace()
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
        fwrite(
            $fd = fopen($path, 'w'),
            $content
        );
        fclose($fd);
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

}

<?php

namespace GTrader;

class Page
{
    use Skeleton;

    public function __construct()
    {
        $this->setParam('scripts_top', [])
                ->setParam('scripts_bottom', [])
                ->setParam('stylesheets', []);
    }


    public static function get(string $element)
    {
        $singleton = self::singleton();
        $elements = $singleton->getParam($element);
        return (is_array($elements)) ? join("\n", $elements) : $elements;
    }


    public static function add(string $element, string $content)
    {
        $singleton = self::singleton();
        $elements = $singleton->getParam($element);
        if (!is_array($elements)) {
            return false;
        }
        if (in_array($content, $elements)) {
            return true;
        }
        $elements[] = $content;
        $singleton->setParam($element, $elements);
        return true;
    }
}

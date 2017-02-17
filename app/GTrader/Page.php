<?php

namespace GTrader;

class Page extends Skeleton
{

    public function __construct()
    {
        $this->setParam('scripts_top', [])
                ->setParam('scripts_bottom', [])
                ->setParam('stylesheets', []);
    }


    public static function getElements(string $element)
    {
        $singleton = self::singleton();
        $elements = $singleton->getParam($element);
        return (is_array($elements)) ? join("\n", $elements) : $elements;
    }


    public static function addElement(string $element, string $content, bool $single_instance = false)
    {
        $singleton = self::singleton();
        $elements = $singleton->getParam($element);
        if (!is_array($elements))
            return false;
        if ($single_instance)
            foreach ($elements as $existing_element)
                if ($content === $existing_element)
                    return true;
        $elements[] = $content;
        $singleton->setParam($element, $elements);
        return true;
    }
}

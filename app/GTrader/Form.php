<?php

namespace GTrader;

class Form extends Base
{
    protected $uid;
    protected $elements = [];

    public function __construct(array $elements = [], array $defaults = [])
    {
        parent::__construct();
        $this->uid = Rand::uniqId();
        foreach ($elements as $key => $elem) {
            if (!isset($elem['value'])) {
                if (isset($defaults[$key])) {
                    $elem['value'] = $defaults[$key];
                }
            }
            $this->elements[$key] = $elem;
        }
    }

    public function __set(string $key, $value)
    {
        return $this->setParam($key, $value);
    }

    public function __get(string $key)
    {
        return $this->getParam($key);
    }

    public function toHtml()
    {
        $html = '';
        foreach ($this->elements as $key => $elem) {
            $html .= view(
                ($this->path).ucfirst($elem['type']),
                array_merge(
                    [
                        'key' => $key,
                        'uid' => $this->uid,
                        'class' => $this->classes
                    ],
                    $elem
                )
            )->render();
        }
        return $html;
    }
}

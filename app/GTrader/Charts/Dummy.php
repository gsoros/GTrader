<?php

namespace GTrader\Charts;

use GTrader\Chart;

class Dummy extends Chart {

    public function toHTML(string $content = '')
    {
        $html = parent::toHTML();

        $this->addPageElement('scripts_bottom',
                    '<script src="'.mix('/js/Dummy.js').'"></script>', true);

        return $html;
    }


    public function toJSON($options = 0)
    {
        $o = json_decode(parent::toJSON($options));
        $o->dummy = 'Dummy';

        return json_encode($o, $options);
    }
}

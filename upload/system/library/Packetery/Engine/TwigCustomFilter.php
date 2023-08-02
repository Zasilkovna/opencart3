<?php

namespace Packetery\Engine;

use \Language;

class TwigCustomFilter {

    /** @var \Language */
    private $language;

    /**
     * @param Language $language
     */
    public function __construct(Language $language) {
        $this->language = $language;
    }

    /**
     * @param string $key
     * @return string
     */
    public function translate($key) {
        return $this->language->get($key);
    }
}

<?php

namespace Packetery\Tools;

class Tools {

    // TODO: udělat nějakou konfiguraci a přesunout do ní
    const MODULE_VERSION = '2.2.0';
    /**
     * @return string generated token
     */
    public static function generateCronToken() {
        return sha1(microtime());
    }

    /**
     * @return string
     */
    public static function getAppIdentity() {
        // TODO: verze OC3 získávat dynamicky
        return 'opencart-3.0-packeta-' . self::MODULE_VERSION;
    }

    /**
     * @param string $text
     * @param string $type
     *
     * @return array
     */
    public static function flashMessage($text, $type = 'success') {

        if ($text === '') {
            return [];
        }
        $class = 'alert-success';
        $iconClass = 'fa-check-circle';

        if ($type === 'error_warning') {
            $class = 'alert-danger';
            $iconClass = 'fa-exclamation-circle';
        }

        return [
            'text'=> $text,
            'class'=> $class,
            'icon'=> $iconClass,
        ];
    }
}

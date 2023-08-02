<?php

namespace Packetery\Engine;

class FlashMessage {

    const SUCCESS = 'success';
    const WARNING = 'warning';
    const INFO = 'info';
    const DANGER = 'danger';

    const TYPES = [
        self::SUCCESS => [
            'class' => 'alert-success',
            'icon' => 'fa-check-circle',
        ],
        self::WARNING => [
            'class' => 'alert-warning',
            'icon' => 'fa-exclamation-triangle',
        ],
        self::DANGER => [
            'class' => 'alert-danger',
            'icon' => 'fa-exclamation-circle',
        ],
        self::INFO => [
            'class' => 'alert-info',
            'icon' => 'fa-info-circle',
        ],
    ];
}

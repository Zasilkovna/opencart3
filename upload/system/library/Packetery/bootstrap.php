<?php
use Tracy\Debugger;

require_once DIR_SYSTEM . 'library/Packetery/deps/autoload.php';

Debugger::enable();

// PhpStorm - macOS: https://tracy.nette.org/cs/open-files-in-ide
Tracy\Debugger::$editor = 'phpstorm://open?file=%file&line=%line';

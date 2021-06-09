<?php

spl_autoload_register(
	static function ($class) {
		$filePath = __DIR__ . '/' . str_replace(['\\', 'Packetery'], ['/', ''], $class) . '.php';
		if (is_file($filePath)) {
			require_once $filePath;
		}
	}
);

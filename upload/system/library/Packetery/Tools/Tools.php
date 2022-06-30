<?php

namespace Packetery\Tools;

class Tools
{
	/**
	 * @return string generated token
	 */
	public function generateToken()
	{
		return sha1(microtime());
	}

	/**
	 * @param array $array
	 * @param string[] $keys
	 *
	 * @return bool
	 */
	public static function issetAll($array, $keys)
	{
		foreach ($keys as $key) {
			if (!isset($array[$key])) {
				return false;
			}
		}

		return true;
	}
}

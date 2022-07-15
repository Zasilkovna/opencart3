<?php

namespace Packetery\Tools;

class Tools
{
	const MODULE_VERSION = '2.2.0';
	/**
	 * @return string generated token
	 */
	public function generateCronToken()
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

	/**
	 * @return string
	 */
	public static function getAppIdentity()
	{
		return 'opencart-3.0-packeta-' . self::MODULE_VERSION;
	}

}

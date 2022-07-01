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

	/**
	 * @param string $version
	 *
	 * @return string
	 */
	public static function getAppIdentity($version = '')
	{
		$prefix = 'opencart-3.0-packeta-';

		if ($version !== '') {
			return $prefix . $version;
		}

		require_once DIR_APPLICATION . '../admin/controller/extension/shipping/zasilkovna.php';

		return $prefix . \ControllerExtensionShippingZasilkovna::VERSION;
	}
}

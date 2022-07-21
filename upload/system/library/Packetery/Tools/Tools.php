<?php

namespace Packetery\Tools;

class Tools
{
	const MODULE_VERSION = '2.2.0';
	/**
	 * @return string generated token
	 */
	public static function generateCronToken()
	{
		return sha1(microtime());
	}

	/**
	 * @return string
	 */
	public static function getAppIdentity()
	{
		return 'opencart-3.0-packeta-' . self::MODULE_VERSION;
	}

}

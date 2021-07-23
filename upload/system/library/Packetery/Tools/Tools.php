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
	 * @param array $data
	 * @param string $key
	 * @return mixed|null
	 */
	public function getIfSet(array $data, $key)
	{
		if (isset($data[$key])) {
			return $data[$key];
		}

		return null;
	}
}

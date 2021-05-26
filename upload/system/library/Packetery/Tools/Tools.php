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
}

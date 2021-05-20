<?php

namespace Packetery\Tools;

class Tools
{
	/**
	 * @return string generated token
	 */
	public function generateCronToken()
	{
		return sha1(microtime());
	}
}

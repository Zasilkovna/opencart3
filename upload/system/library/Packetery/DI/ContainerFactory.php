<?php

namespace Packetery\DI;

class ContainerFactory
{
	/**
	 * @return Container
	 */
	public static function create(\Registry $registry)
	{
		return new Container($registry);
	}
}

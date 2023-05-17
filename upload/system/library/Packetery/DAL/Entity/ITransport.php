<?php

namespace Packetery\DAL\Entity;

interface ITransport {
	/**
	 * @return string
	 */
	public function getId();

	/**
	 * @return string
	 */
	public function getName();

	/**
	 * @return string
	 */
	public function getCountry();
}
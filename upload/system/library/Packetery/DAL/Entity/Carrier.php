<?php

namespace Packetery\DAL\Entity;

class Carrier implements ITransport {
	/** @var int */
	private $carrierId;

	/** @var string */
	private $name;

	/** @var bool */
	private $hasPickupPoints;

	/** @var string */
	private $country;

	public function __construct($carrierId, $name, $hasPickupPoints, $country) {
		$this->carrierId = $carrierId;
		$this->name = $name;
		$this->hasPickupPoints = $hasPickupPoints;
		$this->country = $country;
	}

	/**
	 * @return int
	 */
	public function getId() {
		return $this->carrierId;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getCountry() {
		return $this->country;
	}

	/**
	 * @return bool
	 */
	public function isHasPickupPoints() {
		return $this->hasPickupPoints;
	}
}

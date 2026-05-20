<?php

namespace Packetery\Carrier;

class Carrier
{
	/** @var int|null Packeta feed branch ID, not table primary key */
	private $id;

	/** @var int Table primary key with auto-increment */
	private $idRecord;

	/** @var string */
	private $name;

	/** @var string */
	private $country;

	/** @var string */
	private $currency;

	/** @var float */
	private $maxWeight;

	/** @var bool */
	private $isPickupPoints;

	/** @var bool */
	private $hasCarrierDirectLabel;

	/** @var bool */
	private $customsDeclarations;

	/** @var bool */
	private $available;

	/** @var bool */
	private $deleted;

	/** @var string[]|null */
	private $vendorGroups;

	/**
	 * @param int $idRecord Table primary key with auto-increment
	 * @param int|null $id Packeta feed branch ID, not table primary key
	 * @param string $name
	 * @param string $country
	 * @param string $currency
	 * @param float $maxWeight
	 * @param bool $isPickupPoints
	 * @param bool $hasCarrierDirectLabel
	 * @param bool $customsDeclarations
	 * @param bool $available
	 * @param bool $deleted
	 * @param string[]|null $vendorGroups
	 */
	public function __construct(
		$idRecord,
		$id,
		$name,
		$country,
		$currency,
		$maxWeight,
		$isPickupPoints,
		$hasCarrierDirectLabel,
		$customsDeclarations,
		$available,
		$deleted,
		$vendorGroups
	) {
		$this->idRecord = $idRecord;
		$this->id = $id;
		$this->name = $name;
		$this->country = $country;
		$this->currency = $currency;
		$this->maxWeight = round((float)$maxWeight, 3);
		$this->isPickupPoints = (bool)$isPickupPoints;
		$this->hasCarrierDirectLabel = (bool)$hasCarrierDirectLabel;
		$this->customsDeclarations = (bool)$customsDeclarations;
		$this->available = (bool)$available;
		$this->deleted = (bool)$deleted;
		$this->vendorGroups = $vendorGroups;
	}

	/**
	 * @return int|null
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return int
	 */
	public function getIdRecord()
	{
		return $this->idRecord;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getCountry()
	{
		return $this->country;
	}

	/**
	 * @return bool
	 */
	public function isPickupPoints()
	{
		return $this->isPickupPoints;
	}

	/**
	 * @return string[]|null
	 */
	public function getVendorGroups()
	{
		return $this->vendorGroups;
	}
}

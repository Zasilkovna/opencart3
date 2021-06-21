<?php

namespace Packetery\Carrier;

use DB;

class CarrierRepository
{
	/** @var DB */
	private $db;

	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * @return stdClass
	 */
	public function getCarrierIds()
	{
		return $this->db->query('SELECT `id` FROM `' . DB_PREFIX . 'zasilkovna_carrier`');
	}

	/**
	 * Set those not in feed as deleted.
	 * @param array $carriersInFeed
	 */
	public function setOthersAsDeleted($carriersInFeed)
	{
		$this->db->query(sprintf('UPDATE `' . DB_PREFIX . 'zasilkovna_carrier` SET `deleted` = 1 WHERE `id` NOT IN (%s)', implode(',', $carriersInFeed)));
	}

	/**
	 * @return \Packetery\Carrier\CarrierQuery
	 */
	public function createCarrierQuery() {
		return new CarrierQuery($this->db);
	}
}

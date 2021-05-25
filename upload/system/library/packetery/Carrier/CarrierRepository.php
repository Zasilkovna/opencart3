<?php

namespace Packetery\Carrier;

use \DB;

class CarrierRepository
{
	/** @var DB */
	private $db;

	public function __construct($db)
	{
		$this->db = $db;
	}

	public function getCarrierIds()
	{
		return $this->db->query('SELECT `id` FROM `' . DB_PREFIX . 'zasilkovna_carrier`');
	}

	/**
	 * Set those not in feed as deleted.
	 * @param $carriersInFeed
	 */
	public function setOthersAsDeleted($carriersInFeed)
	{
		$this->db->query(sprintf('UPDATE `' . DB_PREFIX . 'zasilkovna_carrier` SET `deleted` = 1 WHERE `id` NOT IN (%s)', implode(',', $carriersInFeed)));
	}

	/**
	 * @return string
	 */
	public function getCreateTableSQL() {
		return 'CREATE TABLE `' . DB_PREFIX . 'zasilkovna_carrier` (
			`id` int NOT NULL,
			`name` varchar(255) NOT NULL,
			`is_pickup_points` boolean NOT NULL,
			`has_carrier_direct_label` boolean NOT NULL,
			`separate_house_number` boolean NOT NULL,
			`customs_declarations` boolean NOT NULL,
			`requires_email` boolean NOT NULL,
			`requires_phone` boolean NOT NULL,
			`requires_size` boolean NOT NULL,
			`disallows_cod` boolean NOT NULL,
			`country` varchar(255) NOT NULL,
			`currency` varchar(255) NOT NULL,
			`max_weight` float NOT NULL,
			`deleted` boolean NOT NULL,
			UNIQUE (`id`)
		) ENGINE=MyISAM;';
	}
}

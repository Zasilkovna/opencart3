<?php

namespace Packetery\DAL\Mapper;

use Packetery\DAL\Entity\Carrier;

class CarrierMapper {
	public function createFromData(array $carrierData) {
		return new Carrier(
			(int)$carrierData['id'],
			$carrierData['name'],
			$carrierData['is_pickup_points'],
			$carrierData['country']
		);
	}
}

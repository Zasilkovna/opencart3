<?php

namespace Packetery\Carrier;

use Packetery\Db\BaseRepository;

class CarrierUpdater
{
	/** @var BaseRepository */
	private $baseRepository;

	/** @var CarrierRepository */
	private $carrierRepository;

	public function __construct(BaseRepository $baseRepository, CarrierRepository $carrierRepository)
	{
		$this->baseRepository = $baseRepository;
		$this->carrierRepository = $carrierRepository;
	}

	/**
	 * @param array $carriers data retrieved from API
	 * @return bool
	 */
	public function validateCarrierData($carriers)
	{
		foreach ($carriers as $carrier) {
			if (!isset(
				$carrier['id'],
				$carrier['name'],
				$carrier['country'],
				$carrier['currency'],
				$carrier['pickupPoints'],
				$carrier['apiAllowed'],
				$carrier['separateHouseNumber'],
				$carrier['customsDeclarations'],
				$carrier['requiresEmail'],
				$carrier['requiresPhone'],
				$carrier['requiresSize'],
				$carrier['disallowsCod'],
				$carrier['maxWeight']
			)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * @param array $carriers validated data retrieved from API
	 * @return array data to store in db
	 */
	private function carriersMapper($carriers)
	{
		$mappedData = [];

		$carrierBooleanParams = [
			'is_pickup_points' => 'pickupPoints',
			'has_carrier_direct_label' => 'apiAllowed',
			'separate_house_number' => 'separateHouseNumber',
			'customs_declarations' => 'customsDeclarations',
			'requires_email' => 'requiresEmail',
			'requires_phone' => 'requiresPhone',
			'requires_size' => 'requiresSize',
			'disallows_cod' => 'disallowsCod',
		];

		foreach ($carriers as $carrier) {
			$carrierId = (int)$carrier['id'];
			$carrierData = [
				'name' => $carrier['name'],
				'country' => $carrier['country'],
				'currency' => $carrier['currency'],
				'max_weight' => (float)$carrier['maxWeight'],
				'deleted' => false,
			];
			foreach ($carrierBooleanParams as $columnName => $paramName) {
				$carrierData[$columnName] = ($carrier[$paramName] === 'true');
			}
			$mappedData[$carrierId] = $carrierData;
		}
		return $mappedData;
	}

	/**
	 * @param array $carriers validated data retrieved from API
	 */
	public function saveCarriers($carriers)
	{
		$mappedData = $this->carriersMapper($carriers);
		$carriersInFeed = [];

		$carrierCheck = $this->carrierRepository->getCarrierIds();
		$carriersInDb = array_column($carrierCheck->rows, 'id');

		foreach ($mappedData as $carrierId => $carrier) {
			$carriersInFeed[] = $carrierId;
			if (in_array($carrierId, $carriersInDb)) {
				$this->baseRepository->update('zasilkovna_carrier', $carrier, '`id` = ' . $carrierId);
			} else {
				$carrier['id'] = $carrierId;
				$this->baseRepository->insert('zasilkovna_carrier', $carrier);
			}
		}

		$this->carrierRepository->setOthersAsDeleted($carriersInFeed);
	}
}

<?php

namespace Packetery\Widget;

use Packetery\Carrier\Carrier;
use Packetery\Carrier\VendorGroup;

class WidgetOptionsBuilder
{
	/**
	 * @param string $language
	 * @param string $appIdentity
	 * @return array{
	 *     country: string,
	 *     language: string,
	 *     appIdentity: string,
	 *     vendors?: array<int, array{
	 *         selected: bool,
	 *         carrierId?: int,
	 *         country?: string,
	 *         group?: string
	 *     }>
	 * }
	 */
	public function createPickupPointForCheckout(Carrier $carrier, $language, $appIdentity)
	{
		$widgetOptions = [
			'country' => $carrier->getCountry(),
			'language' => $language,
			'appIdentity' => $appIdentity,
		];

		$vendors = $this->getWidgetVendorsParam($carrier);
		if ($vendors !== []) {
			$widgetOptions['vendors'] = $vendors;
		}

		return $widgetOptions;
	}

	/**
	 * @return array<int, array{
	 *     selected: bool,
	 *     carrierId?: int,
	 *     country?: string,
	 *     group?: string
	 * }>
	 */
	private function getWidgetVendorsParam(Carrier $carrier)
	{
		if ($carrier->getId() !== null) {
			return [
				[
					'carrierId' => (int)$carrier->getId(),
					'selected' => true,
				],
			];
		}

		$vendorGroups = $carrier->getVendorGroups();
		if ($vendorGroups === null) {
			return [];
		}

		$vendors = [];
		foreach ($vendorGroups as $group) {
			if ($group === VendorGroup::ZBOX) {
				$vendors[] = $this->createZBoxVendor($carrier->getCountry());
			} else if ($group === VendorGroup::ZPOINT) {
				$vendors[] = $this->createZPointVendor($carrier->getCountry());
			}
		}

		return $vendors;
	}

	/**
	 * @param string $country
	 * @return array{country: string, selected: bool}
	 */
	private function createZPointVendor($country)
	{
		return [
			'country' => $country,
			'selected' => true,
		];
	}

	/**
	 * @param string $country
	 * @return array{country: string, group: string, selected: bool}
	 */
	private function createZBoxVendor($country)
	{
		return [
			'country' => $country,
			'group' => VendorGroup::ZBOX,
			'selected' => true,
		];
	}
}

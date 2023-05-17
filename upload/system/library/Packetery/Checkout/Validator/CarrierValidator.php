<?php

namespace Packetery\Checkout\Validator;

use Packetery\Checkout\Address;
use Packetery\Vendor\VendorService;

class CarrierValidator implements ValidatorStrategy {

	/** @var VendorService */
	private $vendorService;

	public function __construct(VendorService $vendorService) {
		$this->vendorService = $vendorService;
	}

	/**
	 * @param Address $address
	 * @param $cartTotalWeight
	 * @return bool
	 */
    public function validate(Address $address, $cartTotalWeight) {
		// validace specifických pravidel pro dopravce
		$vendors = $this->vendorService->fetchVendorsWithTransportByCountry($address->getCountryIsoCode2(), true);
		if (empty($vendors)) {
			return false;
		}
		$vendorPrices = $this->vendorService->getVendorsPrices($vendors, $cartTotalWeight);

		return $this->hasAnyVendorPrice($vendorPrices);
    }

	/**
	 * @param array $vendorPrices
	 * @return bool
	 */
	private function hasAnyVendorPrice(array $vendorPrices) {
		$vendorsWithPrice = array_filter($vendorPrices, function($price) {
			return $price !== null;
		});

		return !empty($vendorsWithPrice);
	}
}

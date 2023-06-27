<?php

namespace Packetery\Vendor;

use Packetery\Carrier\CarrierRepository;
use Packetery\Tools\Tools;

class Page {

	/** @var VendorRepository */
	private $vendorRepository;

	/** @var CarrierRepository */
	private $carrierRepository;

	/** @var \Language */
	private $language;

	/**
	 * @param VendorRepository  $vendorRepository
	 * @param CarrierRepository $carrierRepository
	 * @param \Language         $language
	 */
	public function __construct(
		VendorRepository  $vendorRepository,
		CarrierRepository $carrierRepository,
		\Language         $language
	) {
		$this->vendorRepository = $vendorRepository;
		$this->carrierRepository = $carrierRepository;
		$this->language = $language;
	}

	/**
	 * @param array $weightRules
	 *
	 * @return array
	 */
	public function removeEmptyWeightRules(array $weightRules) {
		return array_filter($weightRules, static function ($rule) {
			return !(empty($rule['max_weight']) && empty($rule['price']));
		});
	}

	/**
	 * @param array $formData
	 *
	 * @return array
	 */
	public function validate(array $formData) {
		$errors = [];

		if (empty($formData['vendor'])) {
			$errors['vendor'] = $this->language->get('vendor_add_error_required_vendor');
		}

		if (empty($formData['weight_rules'])) {
			$errors['weight_rules_missing'] = $this->language->get('vendor_add_error_weight_rules_missing');
		} else {
			$weightRulesErrors = $this->validateWeightRules($formData['weight_rules']);
			if (!empty($weightRulesErrors)) {
				$errors['weight_rules'] = $weightRulesErrors;
			}
		}

		return $errors;
	}

	/**
	 * @param array $weightRules
	 *
	 * @return array
	 */
	private function validateWeightRules(array $weightRules) {
		$errors = [];
		$weights = [];

		foreach ($weightRules as $index => $rule) {
			if (!is_numeric($rule['max_weight']) || $rule['max_weight'] <= 0) {
				$errors[$index]['max_weight'] = $this->language->get('vendor_add_error_rule_max_weight_invalid');
			} else {
				if (in_array($rule['max_weight'], $weights, true)) {
					$errors[$index]['max_weight'] = $this->language->get('vendor_add_error_rule_duplicate_weights');
				}
				$weights[] = $rule['max_weight'];
			}

			if (!is_numeric($rule['price']) || $rule['price'] <= 0) {
				$errors[$index]['price'] = $this->language->get('vendor_add_error_rule_price_invalid');
			}
		}

		return $errors;
	}

	/**
	 * @param array $formData
	 *
	 * @return void
	 */
	public function saveVendor(array $formData) {
		$isCarrier = is_numeric($formData['vendor']);
		$cartName = trim($formData['cart_name']);
		$vendor = [
			'carrier_id' => $isCarrier ? (int)$formData['vendor'] : null,
			'cart_name' => $cartName ?: null,
			'country' => $isCarrier ? null : $formData['country'],
			'group' => $isCarrier ? null : $formData['vendor'],
			'free_shipping_limit' => (float)$formData['free_shipping_limit'] ?: null,
			'is_enabled' => (int)$formData['is_enabled'],
		];

		$vendorId = $this->vendorRepository->insert('zasilkovna_vendor', $vendor);
		$this->vendorRepository->insertVendorPrices($vendorId, $formData['weight_rules']);
	}

	/**
	 * @param string $countryCode
	 * @return array
	 */
	public function getUnusedCarriersList($countryCode) {
		$carriers = $this->carrierRepository->getCarriersByCountry($countryCode);
		$existingVendors = $this->vendorRepository->getVendorsByCountry($countryCode);

		foreach ($existingVendors as $vendor) {
			if ($vendor['carrier_id'] !== null) {
				unset($carriers[$vendor['carrier_id']]);
			}
		}

		return $carriers;
	}

	/**
	 * @param $countryCode
	 * @return array
	 */
	public function getUnusedPacketaVendorsList($countryCode) {
		$packetaVendors = $this->vendorRepository->getPacketaVendorsByCountry($countryCode);
		$existingVendors = $this->vendorRepository->getVendorsByCountry($countryCode);

		foreach ($existingVendors as $vendor) {
			if ($vendor['carrier_id'] === null) {
				$uniqueKey = Tools::getUniquePacketaVendor($vendor['country'], $vendor['group']);
				unset($packetaVendors[$uniqueKey]);
			}
		}

		array_walk_recursive($packetaVendors, function(&$item, $key) {
			if ($key === 'name') {
				$item = $this->language->get($item);
			}
		});

		return $packetaVendors;
	}
}

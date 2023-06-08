<?php

namespace Packetery\Vendor;

use Packetery\Carrier\CarrierRepository;

class AddEditPage {

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
	 * @param string $countryCode
	 *
	 * @return array
	 */
	public function getPacketaVendorsByCountry($countryCode) {
		$vendors = $this->vendorRepository->getInternalVendorsByCountry($countryCode);
		$usedVendorGroups = $this->vendorRepository->getUsedVendorGroupsByCountry($countryCode);

		$internalVendors = [];
		foreach ($vendors as $vendor) {
			if (in_array($vendor['id'], $usedVendorGroups, true) || ($vendor['id'] === 'zpoint' && in_array('', $usedVendorGroups, true))) {
				continue;
			}
			$internalVendors[] = [
				'vendor_id' => $vendor['id'],
				'name'      => $this->language->get($vendor['name']),
			];
		}

		return $internalVendors;
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

		foreach ($weightRules as $counter => $rule) {
			if (!is_numeric($rule['max_weight']) || $rule['max_weight'] <= 0) {
				$errors[$counter]['max_weight'] = $this->language->get('vendor_add_error_rule_max_weight_invalid');
			} else {
				if (in_array($rule['max_weight'], $weights, true)) {
					$errors[$counter]['max_weight'] = $this->language->get('vendor_add_error_rule_duplicate_weights');
				}
				$weights[] = $rule['max_weight'];
			}

			if (!is_numeric($rule['price']) || $rule['price'] <= 0) {
				$errors[$counter]['price'] = $this->language->get('vendor_add_error_rule_price_invalid');
			}
		}

		return $errors;
	}

	/**
	 * @param array $formData
	 *
	 * @return void
	 */
	public function saveVendorWithWeightRules(array $formData) {
		$isCarrier = is_numeric($formData['vendor']);

		if ($formData['vendor'] === 'zpoint'){
			$formData['vendor'] = '';
		}

		$vendor = [
			'id'                  => null,
			'carrier_id'          => $isCarrier ? (int)$formData['vendor'] : null,
			'carrier_name_cart'   => $formData['cart_name'],
			'country'             => $isCarrier ? null : $formData['country'],
			'group'               => $isCarrier ? null : $formData['vendor'],
			'free_shipping_limit' => $formData['free_shipping_limit'],
			'is_enabled'          => (int)$formData['is_enabled'],
		];

		$newVendorId = $this->vendorRepository->saveVendor($vendor);
		if (!$newVendorId) {
			return;
		}

		$vendorPrices = [];
		if ($formData['weight_rules'] && is_array($formData['weight_rules'])) {
			$formData['weight_rules'] = $this->removeEmptyWeightRules($formData['weight_rules']);
			foreach ($formData['weight_rules'] as $weightRule) {
				$vendorPrices[] = [
					'id'         => null,
					'vendor_id'  => $newVendorId,
					'max_weight' => (float)$weightRule['max_weight'],
					'price'      => (float)$weightRule['price'],
				];
			}
			if ($vendorPrices) {
				$this->vendorRepository->insertVendorPrices($vendorPrices);
			}
		}
	}

	/**
	 * @param string $countryCode
	 *
	 * @return array
	 */
	public function getCarriersByCountry($countryCode) {
		$vendors = $this->vendorRepository->getVendorsByCountry($countryCode);
		$carriers = $this->carrierRepository->getCarriersByCountry($countryCode);

		//filter out already used in vendors
		$usedCarrierIds = [];
		if (!empty($vendors)) {
			$usedCarrierIds = array_map(
				static function ($vendor) {
					return $vendor['carrier_id'];
				},
				$vendors);
		}

		return array_filter(
			$carriers,
			static function ($carrier) use ($usedCarrierIds) {
				return !in_array($carrier['id'], $usedCarrierIds, true);
			}
		);
	}

}

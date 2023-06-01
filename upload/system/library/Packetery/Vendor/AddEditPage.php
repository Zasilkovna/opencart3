<?php

namespace Packetery\Vendor;

use ControllerExtensionShippingZasilkovna;
use Packetery\Tools\Tools;
use Packetery\Carrier\CarrierRepository;

class AddEditPage {

	/** @var VendorRepository */
	private $vendorRepository;

	/** @var CarrierRepository */
	private $carrierRepository;

	/** @var string */
	private $country;

	/** @var \Language */
	private $language;

	/** @var \Session $session */
	private $session;

	/** @var array */
	private $vendors = [];

	/** @var array */
	public $hasErrors = false;

	/**
	 * @param VendorRepository  $vendorRepository
	 * @param CarrierRepository $carrierRepository
	 * @param string            $country
	 * @param \Language         $language
	 * @param \Session          $session
	 */
	public function __construct(
		VendorRepository  $vendorRepository,
		CarrierRepository $carrierRepository,
		                  $country,
		\Language         $language,
		\Session          $session
	) {
		$this->vendorRepository = $vendorRepository;
		$this->carrierRepository = $carrierRepository;
		$this->country = $country;
		$this->language = $language;
		$this->session = $session;
		$this->hasErrors = false;
		$this->init();
	}

	/**
	 * @return void
	 */
	public function init() {
		$vendors = $this->vendorRepository->getVendorsByCountry($this->country);
		$carriers = $this->carrierRepository->getCarriersByCountry($this->country);

		//filter out already used in vendors
		$usedCarrierIds = [];
		if (!empty($vendors)) {
			$usedCarrierIds = array_map(
				static function ($vendor) {
					return $vendor['carrier_id'];
				},
				$vendors);
		}
		$vendors = array_filter(
			$carriers,
			static function ($carrier) use ($usedCarrierIds) {
				return !in_array($carrier['id'], $usedCarrierIds, true);
			}
		);

		$this->vendors = $vendors;
	}

	/**
	 * @param array $weightRules
	 *
	 * @return array
	 */
	private function removeEmptyWeightRules(array $weightRules) {
		return array_filter($weightRules, static function ($rule) {
			return !(empty($rule['max_weight']) && empty($rule['price']));
		});
	}

	/**
	 * @param string $countryCode
	 *
	 * @return array
	 */
	public function getInternalVendorsByCountry($countryCode) {
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
	public function getAddVendorFormValidationErrors(array $formData) {
		$errors = [];
		$weightRulesErrors = [];

		if ($formData['action'] === ControllerExtensionShippingZasilkovna::ACTION_ADD_VENDOR) {
			if (empty($formData['vendor'])) {
				$errors[] = $this->language->get('vendor_add_error_required_vendor');
			}

			if (isset($formData['weight_rules'])) {
				$formData['weight_rules'] = $this->removeEmptyWeightRules($formData['weight_rules']);
			}

			if (empty($formData['weight_rules'])) {
				$errors[] = $this->language->get('vendor_add_error_weight_rules_missing');
			} else {
				$weightRulesErrors = $this->validateWeightRules($formData['weight_rules']);
			}
		}

		return array_merge($errors, $weightRulesErrors);
	}

	/**
	 * @param array $weightRules
	 *
	 * @return array
	 */
	private function validateWeightRules(array $weightRules) {
		$errors = [];

		foreach ($weightRules as $rule) {
			if (!isset($rule['max_weight']) || !is_numeric($rule['max_weight']) || $rule['max_weight'] <= 0) {
				$errors[] = 'vendor_add_error_rule_max_weight_invalid';
			}

			if (!isset($rule['price']) || !is_numeric($rule['price']) || $rule['price'] <= 0) {
				$errors[] = 'vendor_add_error_rule_price_invalid';
			}
		}

		if ($this->checkDuplicateWeightRules($weightRules)) {
			$errors[] = 'vendor_add_error_rule_duplicate_weights';
		}

		return $errors;
	}

	/**
	 * @param array $weightRules
	 *
	 * @return bool
	 */
	private function checkDuplicateWeightRules(array $weightRules) {
		$uniqueWeights = [];

		foreach ($weightRules as $rule) {
			$maxWeight = $rule['max_weight'];

			if (isset($uniqueWeights[$maxWeight])) {
				return true; // Duplicate weight found
			}

			$uniqueWeights[$maxWeight] = true;
		}

		return false; // No duplicate weights found
	}

	/**
	 * Session will contain invalid form data for form pre-filling
	 *
	 * @param array $formData
	 *
	 * @return void
	 */
	private function saveAddVendorFormDataToSession(array $formData) {
		$this->session->data['vendor_add_form_data'] = $formData;
	}

	/**
	 * @return array
	 */
	private function getAddVendorFormDataFromSession() {
		if (!isset($this->session->data['vendor_add_form_data'])) {
			return [];
		}

		$formData = $this->session->data['vendor_add_form_data'];
		unset($this->session->data['vendor_add_form_data']);

		return $formData;
	}

	/**
	 * @param array $formData
	 *
	 * @return array
	 */
	public function getFormDefaults(array $formData = []) {

		if (empty($formData)) {
			$formData = $this->getAddVendorFormDataFromSession();
		}
		$prefilledData = [];
		foreach ($formData as $key => $value) {
			if (in_array($key, ['vendor', 'country', 'vendor_group', 'cart_name', 'free_shipping_limit', 'is_enabled', 'weight_rules'])) {
				$prefilledData[$key] = $value;
			}
		}

		return $prefilledData;
	}

	/**
	 * @param array $weightRules
	 *
	 * @return array
	 */
	private function sortWeightRulesByMaxWeight(array $weightRules) {
		usort($weightRules, static function ($a, $b) {
			return (float)$a['max_weight'] - (float)$b['max_weight'];
		});

		return $weightRules;
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
	 * @return array
	 */
	public function getTranslations() {
		return [
			'new_vendor_text'           => $this->language->get('vendor_add_new_vendor_text'),
			'text_select_vendor'        => $this->language->get('vendor_add_select_vendor'),
			'entry_weight_to_kg'        => $this->language->get('vendor_add_entry_weight_to_kg'),
			'entry_weight_to'           => $this->language->get('vendor_add_entry_weight_to'),
			'text_weight_rules'         => $this->language->get('vendor_add_text_weight_rules'),
			'entry_shipping_price'      => $this->language->get('vendor_add_entry_shipping_price'),
			'carriers_optgroup'         => $this->language->get('vendor_add_carriers_optgroup'),
			'packeta_optgroup'          => $this->language->get('vendor_add_packeta_optgroup'),
			'vendor_label'              => $this->language->get('vendor_add_vendor_label'),
			'entry_cart_name'           => $this->language->get('vendor_add_entry_cart_name'),
			'help_cart_name'            => $this->language->get('vendor_add_help_cart_name'),
			'entry_free_shipping_limit' => $this->language->get('vendor_add_entry_free_shipping_limit'),
		];
	}

	/**
	 * @param array $postedData
	 *
	 * @return void
	 */
	public function processForm(array $postedData) {
		$validationErrors = $this->getAddVendorFormValidationErrors($postedData);
		if (!empty($validationErrors)) {
			foreach ($validationErrors as $error) {
				$allErrorsTranslated[] = $this->language->get($error);
			}
			$this->session->data['flashMessage'] = Tools::flashMessage(implode('<br>', $allErrorsTranslated), 'error_warning');

			if (isset($postedData['weight_rules'])) {
				$postedData['weight_rules'] = $this->removeEmptyWeightRules($postedData['weight_rules']);
				$postedData['weight_rules'] = $this->sortWeightRulesByMaxWeight($postedData['weight_rules']);
			}

			$this->saveAddVendorFormDataToSession($postedData);
			$this->hasErrors = true;
		}
	}

	/**
	 * @return array
	 */
	public function getVendors() {
		return $this->vendors;
	}

}

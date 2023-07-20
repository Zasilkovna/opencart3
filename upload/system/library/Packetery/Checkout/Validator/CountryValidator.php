<?php

namespace Packetery\Checkout\Validator;

use Packetery\Checkout\Address;

class CountryValidator implements ValidatorStrategy {

    /** @var \Config */
    private $config;

    /**
     * @param \Config $config
     */
    public function __construct(\Config $config) {
        $this->config = $config;
    }

    /**
     * @param Address $address
     * @param float $cartTotalWeight
     * @return bool
     */
    public function validate(Address $address, $cartTotalWeight) {
        if (!$this->checkConditionsByCountry($cartTotalWeight)) {
            return false;
        }
        // TODO: validace specifických pravidel pro zemi
        return true;
    }

    /**
     * @param float $totalWeight
     * @return bool
     */
    public function checkConditionsByCountry($totalWeight) {
        // check if total weight of order is lower than maximal allowed weight (if limit is defined)
        $maxWeight = (int)$this->config->get('shipping_zasilkovna_weight_max');
        if (!empty($maxWeight) && $totalWeight > $maxWeight) {
            return false;
        }

        return true;
    }
}

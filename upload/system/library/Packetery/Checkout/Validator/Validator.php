<?php

namespace Packetery\Checkout\Validator;

use Packetery\Checkout\Address;
use Packetery\Checkout\Repository;

class Validator {

    /** @var ValidatorStrategy */
    private $strategy;

    /** @var \Config */
    private $config;

    /** @var Repository */
    private $checkoutRepository;

    public function __construct(ValidatorStrategy $strategy, \Config $config, Repository $checkoutRepository) {
        $this->strategy = $strategy;
        $this->config = $config;
        $this->checkoutRepository = $checkoutRepository;
    }

    /**
     * @param Address $address
     * @param float $cartTotalWeight
     * @return bool
     */
    public function validate(Address $address, $cartTotalWeight) {
        if (!$this->checkBasicConditions($address)) {
            return false;
        }

        return $this->strategy->validate($address, $cartTotalWeight);
    }

    /**
     * @param Address $address
     * @return bool
     */
    private function checkBasicConditions(Address $address) {
        // check if module for Zasilkovna is enabled
        if (!(int)$this->config->get('shipping_zasilkovna_status')) {
            return false;
        }

        // check if target customer address is in allowed geo zone (if zone limitation is defined)
        if (!$this->canDeliverToZone($address)) {
            return false;
        }

        if (!$address->hasCountryIsoCode2()) {
            return false;
        }

        return true;
    }

    /**
     * @param Address $address
     * @return bool
     */
    private function canDeliverToZone(Address $address) {
        $geoZoneId = (int)$this->config->get('shipping_zasilkovna_geo_zone_id');
        if ($geoZoneId === 0) {
            return true;
        }

        return $this->checkoutRepository->existsForZoneAndCountry($geoZoneId, $address->getZoneId(), $address->getCountryId());
    }
}

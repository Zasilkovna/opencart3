<?php

namespace Packetery\Checkout;

class Address {

    /** @var int */
    private $countryId;

    /** @var int */
    private $zoneId;

    /** @var string */
    private $countryIsoCode2;

    /**
     * @param array $targetAddress
     */
    public function __construct(array $targetAddress) {
        $this->countryId = $targetAddress['country_id'];
        $this->zoneId = $targetAddress['zone_id'];
        $this->countryIsoCode2 = isset($targetAddress['iso_code_2']) ? strtolower($targetAddress['iso_code_2']) : null;
    }

    /**
     * @return integer
     */
    public function getCountryId() {
        return $this->countryId;
    }

    /**
     * @return integer
     */
    public function getZoneId() {
        return $this->zoneId;
    }

    /**
     * @return string|null
     */
    public function getCountryIsoCode2() {
        return $this->countryIsoCode2;
    }

    /**
     * @return bool
     */
    public function hasCountryIsoCode2() {
        return $this->countryIsoCode2 !== null;
    }
}

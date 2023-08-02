<?php

namespace Packetery\DAL\Entity;

class VendorPrice {

    /** @var float */
    public $maxWeight;

    /** @var float */
    public $price;

    /**
     * @param float $maxWeight
     * @param float $price
     */
    public function __construct($maxWeight, $price) {
        $this->maxWeight = $maxWeight;
        $this->price = $price;
    }

    /**
     * @return float
     */
    public function getMaxWeight() {
        return $this->maxWeight;
    }

    /**
     * @return float
     */
    public function getPrice() {
        return $this->price;
    }
}

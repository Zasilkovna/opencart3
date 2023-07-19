<?php

namespace Packetery\DAL\Entity;

class VendorPrice {
    /** @var int|null */
    private $id;

    /** @var int */
    private $vendorId;

    /** @var float */
    public $maxWeight;

    /** @var float  */
    public $price;

    /**
     * @param int $vendorId
     * @param float $maxWeight
     * @param float $price
     */
    public function __construct($id, $vendorId, $maxWeight, $price) {
        $this->id = $id;
        $this->vendorId = $vendorId;
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

    /**
     * @return int|null
     */
    public function getVendorId() {
        return $this->vendorId;
    }

    /**
     * @param int $vendorId
     * @return void
     */
    public function setVendorId($vendorId) {
        $this->vendorId = $vendorId;
    }

    /**
     * @return int|null
     */
    public function getId() {
        return $this->id;
    }
}

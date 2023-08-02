<?php

namespace Packetery\DAL\Entity;

class Vendor {

    /** @var int|null */
    private $id;

    /** @var ITransport */
    private $transport;

    /** @var string */
    private $cartName;

    /** @var float */
    public $freeShippingLimit;

    /** @var bool */
    public $enabled;

    /** @var VendorPrice[] */
    public $prices = [];

    /**
     * @param int|null $id
     * @param string|null $cartName
     * @param float $freeShippingLimit
     * @param bool $enabled
     */
    public function __construct($id, $cartName, $freeShippingLimit, $enabled) {
        $this->id = $id;
        $this->cartName = $cartName;
        $this->freeShippingLimit = $freeShippingLimit;
        $this->enabled = $enabled;
    }

    /**
     * @return int|null
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param int $id
     * @return void
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * @return ITransport
     */
    public function getTransport() {
        return $this->transport;
    }

    /**
     * @param ITransport $transport
     * @return void
     */
    public function setTransport(ITransport $transport) {
        $this->transport = $transport;
    }

    /**
     * @return string|null
     */
    public function getCartName() {
        return $this->cartName;
    }

    /**
     * @return string
     */
    public function getTitle() {
        return $this->getCartName() ?: $this->getTransport()->getName();
    }

    /**
     * @return float
     */
    public function getFreeShippingLimit() {
        return $this->freeShippingLimit;
    }

    /**
     * @return VendorPrice[]
     */
    public function getPrices() {
        return $this->prices;
    }

    /**
     * @param VendorPrice[] $vendorPrices
     * @return void
     */
    public function setPricing(array $vendorPrices) {
        $this->prices = $vendorPrices;
    }

    /**
     * @return bool
     */
    public function isHomeDelivery() {
        return $this->transport instanceof Carrier && !$this->transport->isHasPickupPoints();
    }

    /**
     * @return bool
     */
    public function hasId() {
        return $this->getId() !== null;
    }

    /**
     * @return bool
     */
    public function isEnabled() {
        return $this->enabled;
    }
}

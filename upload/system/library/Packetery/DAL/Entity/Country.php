<?php

namespace Packetery\DAL\Entity;

class Country {

    /** @var int */
    private $id;

    /** @var string */
    private $name;

    /** @var string */
    private $isoCode2;

    /** @var string */
    private $isoCode3;

    /** @var string */
    private $addressFormat;

    /** @var bool */
    private $postcodeRequired;

    /** @var bool */
    private $status;

    /**
     * @param int $id
     * @param string $name
     * @param string $isoCode2
     * @param string $isoCode3
     * @param string $addressFormat
     * @param bool $postcodeRequired
     * @param bool $status
     */
    public function __construct(
        $id,
        $name,
        $isoCode2,
        $isoCode3,
        $addressFormat,
        $postcodeRequired,
        $status
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->isoCode2 = $isoCode2;
        $this->isoCode3 = $isoCode3;
        $this->addressFormat = $addressFormat;
        $this->postcodeRequired = $postcodeRequired;
        $this->status = $status;
    }

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param string $name
     * @return void
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getIsoCode2() {
        return $this->isoCode2;
    }

    /**
     * @param string $isoCode2
     * @return void
     */
    public function setIsoCode2($isoCode2) {
        $this->isoCode2 = $isoCode2;
    }

    /**
     * @return string
     */
    public function getIsoCode3() {
        return $this->isoCode3;
    }

    /**
     * @param string $isoCode3
     * @return void
     */
    public function setIsoCode3($isoCode3) {
        $this->isoCode3 = $isoCode3;
    }

    /**
     * @return string
     */
    public function getAddressFormat() {
        return $this->addressFormat;
    }

    /**
     * @param string $addressFormat
     * @return void
     */
    public function setAddressFormat($addressFormat) {
        $this->addressFormat = $addressFormat;
    }

    /**
     * @return bool
     */
    public function isPostcodeRequired() {
        return $this->postcodeRequired;
    }

    /**
     * @param bool $postcodeRequired
     * @return void
     */
    public function setPostcodeRequired($postcodeRequired) {
        $this->postcodeRequired = $postcodeRequired;
    }

    /**
     * @return bool
     */
    public function isStatus() {
        return $this->status;
    }

    /**
     * @param bool $status
     * @return void
     */
    public function setStatus($status) {
        $this->status = $status;
    }
}

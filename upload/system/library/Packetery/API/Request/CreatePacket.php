<?php

namespace Packetery\API\Request;

class CreatePacket
{

    /** @var string $name */
    private $name;

    /** @var string */
    private $surname;

    /** @var string */
    private $company;

    /** @var string */
    private $number;

    /** @var int */
    private $addressId;

    /** @var float */
    private $cod;

    /** @var float $value */
    private $value;

    /** @var string */
    private $currency;

    /**
     * @var float $weight
     */
    private $weight;

    /** @var string */
    private $eshop;

    /** @var string */
    private $phone;

    /** @var string */
    private $carrierPickupPoint;

    /** @var string */
    private $email;

    /** @var string */
    private $street;

    /** @var string */
    private $city;

    /** @var string */
    private $zip;

    /**
     * @param int $pickupPoint
     * @return $this
     */
    public function setPickupPointOrCarrierId($pickupPoint)
    {
        $this->addressId = $pickupPoint;
        return $this;
    }

    /**
     * @param string $carrierPickupPoint
     * @return $this
     */
    public function setCarrierPickupPoint($carrierPickupPoint)
    {
        $this->carrierPickupPoint = $carrierPickupPoint;
        return $this;
    }

    /**
     * @param string $orderNumber
     * @return $this
     */
    public function setOrderNumber($orderNumber)
    {
        $this->number = $orderNumber;
        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @param string $surname
     * @return \Packetery\API\Request\CreatePacket
     */
    public function setSurname($surname){
        $this->surname = $surname;
        return $this;
    }

    /**
     * @param string $company
     * @return \Packetery\API\Request\CreatePacket
     */
    public function setCompany($company){
        $this->company= $company;
        return $this;
    }

    /**
     * @param string $eshop
     * @return \Packetery\API\Request\CreatePacket
     */
    public function setEshop($eshop)
    {
        $this->eshop = $eshop;
        return $this;
    }

    /**
     * @param float $value
     * @return \Packetery\API\Request\CreatePacket
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @param float $weight
     * @return \Packetery\API\Request\CreatePacket
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;
        return $this;
    }

    /**
     * @param string $email
     * @return \Packetery\API\Request\CreatePacket
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @param string $phone
     * @return \Packetery\API\Request\CreatePacket
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
        return $this;
    }

    /**
     * @param float $cod
     * @return \Packetery\API\Request\CreatePacket
     */
    public function setCod($cod) {
        $this->cod = $cod;
        return $this;
    }

    /**
     * @param string $currency
     * @return \Packetery\API\Request\CreatePacket
     */
    public function setCurrency($currency) {
        $this->currency = $currency;
        return $this;
    }

    /**
     * @param string $street
     * @return \Packetery\API\Request\CreatePacket
     */
    public function setStreet($street) {
        $this->street = $street;
        return $this;
    }

    /**
     * @param string $city
     * @return \Packetery\API\Request\CreatePacket
     */
    public function setCity($city) {
        $this->city = $city;
        return $this;
    }

    /**
     * @param string $zip
     * @return \Packetery\API\Request\CreatePacket
     */
    public function setZip($zip) {
        $this->zip = $zip;
        return $this;
    }
}
<?php

namespace Packetery\DAL\Entity;

class Packeta implements ITransport {
    /** @var string */
    public $id;

    /** @var string*/
    private $name;

    /** @var string */
    public $country;

    /** @var string*/
    public $group;

    /**
     * @param string $id
     * @param string $name
     * @param string $country
     * @param string $group
     */
    public function __construct($id, $name, $country, $group) {
        $this->id = $id;
        $this->name = $name;
        $this->country = $country;
        $this->group = $group;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getCountry() {
        return $this->country;
    }

    /**
     * @return string
     */
    public function getGroup() {
        return $this->group;
    }
}

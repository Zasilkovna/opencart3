<?php

namespace Packetery\DAL\Entity;

interface ITransport {
    /**
     * @return string|int
     */
    public function getId();

    /**
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getCountry();
}

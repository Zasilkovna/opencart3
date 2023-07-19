<?php

namespace Packetery\Checkout\Validator;

use Packetery\Checkout\Address;

interface ValidatorStrategy {
    /**
     * @param Address $address
     * @param float $cartTotalWeight
     * @return bool
     */
    public function validate(Address $address, $cartTotalWeight);
}

<?php

namespace Packetery\Checkout\Validator;

use Packetery\Checkout\Repository;
use Packetery\DI\Container;

class ValidatorFactory {

    /** @var Container */
    private $diContainer;

    /**
     * @param Container $diContainer
     */
    public function __construct(Container $diContainer) {
        $this->diContainer = $diContainer;
    }

    /**
     * @return Validator
     * @throws \ReflectionException
     */
    public function create() {
        /** @var \Config $config */
        $config = $this->diContainer->get(\Config::class);
        $method = $config->get('shipping_zasilkovna_pricing_by');

        $allowedMethods = ['country', 'carrier'];
        if (!in_array($method, $allowedMethods)) {
            throw new \InvalidArgumentException('Unknown validation method: ' . $method);
        }

        $checkoutRepository = $this->diContainer->get(Repository::class);
        if ($method === 'country') {
            $strategy = $this->diContainer->get(CountryValidator::class);
        } else {
            $strategy = $this->diContainer->get(CarrierValidator::class);
        }

        return new Validator($strategy, $config, $checkoutRepository);
    }
}

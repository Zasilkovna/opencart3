<?php

declare(strict_types=1);

namespace Packetery\Carrier;

class FinalCarrierNameResolver
{
	public function resolve(Carrier $carrier, ?ShippingRule $shippingRule = null): string
	{
		if ($shippingRule === null || $shippingRule->getCarrierName() === null) {
			return $carrier->getName();
		}
		if (trim($shippingRule->getCarrierName()) === '') {
			return $carrier->getName();
		}

		return $shippingRule->getCarrierName();
	}
}

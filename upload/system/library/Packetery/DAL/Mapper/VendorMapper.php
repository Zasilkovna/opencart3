<?php

namespace Packetery\DAL\Mapper;

use Packetery\DAL\Entity\Vendor;

class VendorMapper {

    /**
     * @param array $vendorData
     * @return Vendor
     */
    public function createFromData(array $vendorData) {
        return new Vendor(
            $vendorData['id'],
            $vendorData['cart_name'],
            $vendorData['free_shipping_limit'],
            (bool)$vendorData['is_enabled'],
            []
        );
    }
}
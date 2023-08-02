<?php

namespace Packetery\DAL\Mapper;

use Packetery\DAL\Entity\VendorPrice;

class VendorPriceMapper {

    /**
     * @param array $priceData
     * @return VendorPrice
     */
    public function createFromData(array $priceData) {
        return new VendorPrice($priceData['max_weight'], $priceData['price']);
    }
}

<?php

namespace Packetery\DAL\Mapper;

use Packetery\DAL\Entity\VendorPrice;

class VendorPriceMapper {

    /**
     * @param array $priceData
     * @return VendorPrice
     */
    public function createFromData(array $priceData) {
        $id = isset($priceData['id']) ? $priceData['id'] : null;

        return new VendorPrice($id, $priceData['vendor_id'], $priceData['max_weight'], $priceData['price']);
    }
}

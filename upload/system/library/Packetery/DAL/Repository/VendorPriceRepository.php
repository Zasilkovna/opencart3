<?php

namespace Packetery\DAL\Repository;

use Packetery\DAL\Entity\Vendor;
use Packetery\DAL\Entity\VendorPrice;
use Packetery\Db\BaseRepository;

class VendorPriceRepository extends BaseRepository {
    /**
     * @param int $vendorId
     * @return array
     */
    public function getByVendorId($vendorId) {
        return $this->db
            ->queryResult(
                'SELECT * FROM zasilkovna_vendor_price WHERE `vendor_id` = ? ORDER BY `max_weight` ASC',
                $vendorId
            )
            ->fetchAll();
    }

    /**
     * @return array
     */
    public function getAll() {
        return $this->db
            ->queryResult(
                'SELECT * FROM zasilkovna_vendor_price'
            )
            ->fetchAll();
    }

    /**
     * @param int $vendorId
     * @param VendorPrice $vendorPrice
     * @return void
     */
    public function save($vendorId, VendorPrice $vendorPrice) {
        $this->db->queryResult(
            'INSERT INTO zasilkovna_vendor_price SET vendor_id = ?, max_weight = ?, price = ?',
            $vendorId,
            $vendorPrice->getMaxWeight(),
            $vendorPrice->getPrice()
        );
    }

    /**
     * @param Vendor $vendor
     * @return void
     */
    public function deleteByVendor(Vendor $vendor) {
        $sql = 'DELETE FROM zasilkovna_vendor_price WHERE vendor_id = ?';
        $this->db->queryResult($sql, $vendor->getId());
    }
}

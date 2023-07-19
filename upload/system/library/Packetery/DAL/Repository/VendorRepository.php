<?php

namespace Packetery\DAL\Repository;

use Packetery\DAL\Entity\Carrier;
use Packetery\DAL\Entity\Vendor;
use Packetery\Db\BaseRepository;

class VendorRepository extends BaseRepository {

    const TABLE_NAME = 'zasilkovna_vendor';

    /**
     * @param bool $onlyEnabled
     * @return array
     */
    public function getAll($onlyEnabled) {
        $queryParts = ["SELECT * FROM zasilkovna_vendor"];

        if ($onlyEnabled) {
            $queryParts[] = "WHERE is_enabled = 1";
        }

        $query = implode(' ', $queryParts);

        return $this->db->queryResult($query)->fetchAll();
    }

    /***
     * @param int $id
     * @return array
     */
    public function byId($id) {
        $sql = 'SELECT * FROM zasilkovna_vendor WHERE id = ?';

        return $this->db->queryResult($sql, $id)->fetch();
    }

    /**
     * @param Vendor $vendor
     * @return int
     */
    public function insertVendor(Vendor $vendor) {
        $vendorData = [
            'cart_name' => $vendor->getCartName(),
            'free_shipping_limit' => $vendor->getFreeShippingLimit(),
            'is_enabled' => $vendor->isEnabled(),
        ];

        $transport = $vendor->getTransport();
        if ($transport instanceof Carrier) {
            $vendorData['carrier_id'] = $transport->getId();
            $vendorData['packeta_id'] = null;
        } else {
            $vendorData['carrier_id'] = null;
            $vendorData['packeta_id'] = $transport->getId();
        }

        return $this->insert(self::TABLE_NAME, $vendorData);
    }

    /**
     * @param Vendor $vendor
     * @return void
     */
    public function deleteVendor(Vendor $vendor) {
        $sql = 'DELETE FROM zasilkovna_vendor WHERE id = ?';
        $this->db->queryResult($sql, $vendor->getId());
    }
}

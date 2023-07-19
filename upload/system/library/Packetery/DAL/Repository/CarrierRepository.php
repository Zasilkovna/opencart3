<?php

namespace Packetery\DAL\Repository;

use Packetery\Db\BaseRepository;

class CarrierRepository extends BaseRepository {

    /**
     * @param int $carrierId
     * @return array|null
     */
    public function byId($carrierId) {
        $sql = "SELECT * FROM zasilkovna_carrier";
        $result = $this->db->queryResult($sql);

        static $CACHE;
        if (isset($CACHE[$carrierId])) {
            return $CACHE[$carrierId];
        }

        $CACHE = $result->fetchAssoc('id');

        return isset($CACHE[$carrierId]) ? $CACHE[$carrierId] : null;
    }
}

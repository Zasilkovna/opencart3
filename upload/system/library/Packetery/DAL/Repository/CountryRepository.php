<?php

namespace Packetery\DAL\Repository;

use Packetery\Db\BaseRepository;

class CountryRepository extends BaseRepository {

    /**
     * @param string $isoCode2
     * @return array
     */
    public function getByIsoCode2($isoCode2) {
        return $this->db->queryResult('SELECT * FROM country WHERE iso_code_2 = ?', $isoCode2)->fetch();
    }
}

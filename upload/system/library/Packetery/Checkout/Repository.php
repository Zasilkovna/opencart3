<?php

namespace Packetery\Checkout;

use Packetery\Db\BaseRepository;

class Repository extends BaseRepository {
    /**
     * @param int $geoZoneId
     * @param int $zoneId
     * @param int $countryId
     * @return bool
     */
    public function existsForZoneAndCountry($geoZoneId, $zoneId, $countryId) {
        // check if given zone or whole country is part of geo zone from configuration
        $sql = 'SELECT 1 FROM zone_to_geo_zone WHERE `geo_zone_id` = ? AND `country_id` = ? AND (`zone_id` = ? OR `zone_id` = 0)';

        return $this->db->queryResult($sql,$geoZoneId, $countryId, $zoneId)->fetchSingle() === '1';
    }
}
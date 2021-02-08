<?php

class ModelExtensionShippingZasilkovnaCountries extends Model
{
    /**
     * @param string $code
     * @return array|null
     */
    public function getCountryByCode($code) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "country WHERE iso_code_2 = '" . $this->db->escape($code) . "'");

        if (empty($query)) {
            return null;
        }

        return $query->row;
    }
}

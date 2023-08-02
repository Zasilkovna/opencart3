<?php

namespace Packetery\DAL\Mapper;

use Packetery\DAL\Entity\Country;

class CountryMapper {
    /**
     * @param array $countryData
     * @return Country
     */
    public function createFromData(array $countryData) {
        return new Country(
            (int)$countryData['country_id'],
            $countryData['name'],
            $countryData['iso_code_2'],
            $countryData['iso_code_3'],
            $countryData['address_format'],
            (bool)$countryData['postcode_required'],
            (bool)$countryData['status']
        );
    }
}

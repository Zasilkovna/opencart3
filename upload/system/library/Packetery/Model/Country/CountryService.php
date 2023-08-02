<?php

namespace Packetery\Model\Country;

use Packetery\DAL\Entity\Country;
use Packetery\DAL\Mapper\CountryMapper;
use Packetery\DAL\Repository\CountryRepository;

class CountryService {

    /** @var CountryRepository */
    private $countryRepository;

    /** @var CountryMapper */
    private $countryMapper;

    /**
     * @param CountryRepository $countryRepository
     * @param CountryMapper $countryMapper
     */
    public function __construct(CountryRepository $countryRepository, CountryMapper $countryMapper) {
        $this->countryRepository = $countryRepository;
        $this->countryMapper = $countryMapper;
    }

    /**
     * @param string $countryCode
     * @return Country
     */
    public function getByCountryCode($countryCode) {
        $country = $this->countryRepository->getByIsoCode2($countryCode);
        if (empty($country)) {
            throw new \InvalidArgumentException('Country with code ' . $countryCode . ' not found.');
        }

        return $this->countryMapper->createFromData($country);
    }
}
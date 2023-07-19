<?php

namespace Packetery\Carrier;

class CountryListingPage
{

    /** @var CarrierRepository */
    private $carrierRepository;

    /**
     * @param CarrierRepository $carrierRepository
     */
    public function __construct(CarrierRepository $carrierRepository)
    {
        $this->carrierRepository = $carrierRepository;
    }

    /**
     * @return array
     */
    public function getActiveCountries()
    {
        $countries = $this->carrierRepository->getCountries();
        $internalCountries = $this->carrierRepository->getZpointCountryCodes();
        $countries = array_unique(array_merge($internalCountries, $countries));
        $ocCountries = $this->carrierRepository->getOcCountries();

        $countriesFinal = [];

        foreach ($countries as $country) {
            $countriesFinal[] = [
                'code' => $country,
                'name' => isset($ocCountries[strtoupper($country)]) ? $ocCountries[strtoupper($country)] : '',
            ];
        }

        usort(
            $countriesFinal,
            static function ($a, $b) {
                if ($a['code'] === 'cz') {
                    return -1;
                }
                if ($b['code'] === 'cz') {
                    return 1;
                }
                if ($a['code'] === 'sk') {
                    return -1;
                }
                if ($b['code'] === 'sk') {
                    return 1;
                }

                return strnatcmp($a['name'], $b['name']);
            }
        );

        return $countriesFinal;
    }

    /**
     * @param string $countryCode
     *
     * @return bool
     */
    public function doesPacketaDeliverTo($countryCode)
    {
        $activeCountries = $this->getActiveCountries();
        foreach ($activeCountries as $country) {
            if ($country['code'] === $countryCode) {
                return true;
            }
        }

        return false;
    }

}

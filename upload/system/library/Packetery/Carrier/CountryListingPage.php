<?php

namespace Packetery\Carrier;

class CountryListingPage
{

	/** @var CarrierRepository */
	private $carrierRepository;

	public function __construct(CarrierRepository $carrierRepository)
	{
		$this->carrierRepository = $carrierRepository;
	}

	public function getActiveCountries()
	{
		$countries = $this->carrierRepository->getCountries();
		$internalCountries = $this->carrierRepository->getZpointCarriers();
		$countries = array_unique( array_merge($internalCountries, $countries));
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
			static function ( $a, $b ) {
				if ( 'cz' === $a['code'] ) {
					return - 1;
				}
				if ( 'cz' === $b['code'] ) {
					return 1;
				}
				if ( 'sk' === $a['code'] ) {
					return - 1;
				}
				if ( 'sk' === $b['code'] ) {
					return 1;
				}

				return strnatcmp( $a['name'], $b['name'] );
			}
		);

		return $countriesFinal;
	}

}

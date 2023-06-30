<?php

namespace Packetery\Vendor;

class VendorFacade {

	/** @var VendorRepository */
	private $vendorRepository;

	/** @var \Language */
	private $language;

	/**
	 * @param VendorRepository $vendorRepository
	 * @param \Language $language
	 */
	public function __construct(
		VendorRepository  $vendorRepository,
		\Language         $language
	) {
		$this->vendorRepository = $vendorRepository;
		$this->language = $language;
	}

	/**
	 * @param string $countryCode
	 * @return array
	 */
	public function getVendorsByCountry($countryCode) {
		$vendors = $this->vendorRepository->getVendorsByCountry($countryCode);
		foreach ($vendors as &$vendor) {
			if ($vendor['carrier_id'] === null) {
				$packetaVendor = $this->vendorRepository->getPacketaVendorByGroup($vendor['group']);
				if (isset($packetaVendor['name'])) {
					$vendor['name'] = $this->language->get($packetaVendor['name']);
				}
			}
		}

		return $vendors;
	}

    /**
     * @param int $vendorId
     *
     * @return void
     */
    public function deleteVendor($vendorId) {
        $this->vendorRepository->delete('zasilkovna_vendor', 'id', $vendorId);
        $this->vendorRepository->delete('zasilkovna_vendor_price', 'vendor_id', $vendorId);
    }
}

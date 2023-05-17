<?php

namespace Packetery\Vendor;

use Packetery\DAL\Entity\Vendor;
use Packetery\DAL\Mapper\CarrierMapper;
use Packetery\DAL\Mapper\PacketaMapper;
use Packetery\DAL\Mapper\VendorMapper;
use Packetery\DAL\Mapper\VendorPriceMapper;
use Packetery\DAL\Repository\CarrierRepository;
use Packetery\DAL\Repository\PacketaRepository;
use Packetery\DAL\Repository\VendorPriceRepository;

class VendorFactory {

    /** @var CarrierRepository */
    private $carrierRepository;

    /** @var PacketaRepository */
    private $packetaRepository;

	/** @var VendorPriceRepository */
	private $vendorPriceRepository;

	/** @var VendorMapper */
    private $vendorMapper;

	/** @var VendorPriceMapper */
	private $vendorPriceMapper;

	/** @var PacketaMapper */
    private $packetaMapper;

	/** @var CarrierMapper */
    private $carrierMapper;

	/** @var \Language  */
    private $language;

	public function __construct(
        CarrierRepository $carrierRepository,
        PacketaRepository $packetaRepository,
		VendorPriceRepository $vendorPriceRepository,
        VendorMapper     $vendorMapper,
		VendorPriceMapper $vendorPriceMapper,
        PacketaMapper    $packetaMapper,
        CarrierMapper    $carrierMapper,
        \Language $language
    ) {
        $this->carrierRepository = $carrierRepository;
        $this->packetaRepository = $packetaRepository;
		$this->vendorPriceRepository = $vendorPriceRepository;
		$this->vendorMapper = $vendorMapper;
		$this->vendorPriceMapper = $vendorPriceMapper;
		$this->packetaMapper = $packetaMapper;
		$this->carrierMapper = $carrierMapper;
		$this->language = $language;
	}

    /**
     * @param array $vendorData
     * @return Vendor
     */
    public function create(array $vendorData) {
        $vendor = $this->vendorMapper->createFromData($vendorData);

        if ($vendorData['carrier_id'] !== null) {
            //Transport is the Carrier object
            $carrierData = $this->carrierRepository->byId((int)$vendorData['carrier_id']);

            if ($carrierData === null) {
                throw new \InvalidArgumentException('Invalid carrier ID: ' . $vendorData['carrier_id']);
            }
            $transport = $this->carrierMapper->createFromData($carrierData);
        } else {
            //Transport is the Packeta object
            $packetaVendorData = $this->packetaRepository->byId($vendorData['packeta_id']);
            if ($packetaVendorData === null) {
                throw new \InvalidArgumentException('Invalid Packeta ID: ' . $vendorData['packeta_id']);
            }

            $packetaVendorData['name'] = $this->language->get($packetaVendorData['name']);
            $transport = $this->packetaMapper->createFromData($packetaVendorData);
        }
        $vendor->setTransport($transport);

		if ($vendor->hasId()) {
			$pricesData = $this->vendorPriceRepository->getByVendorId($vendor->getId());
		} else {
			$pricesData = isset($vendorData['weight_rules']) ? $vendorData['weight_rules'] : [];
		}

		$vendorPrices = [];
		foreach ($pricesData as $priceData) {
			$vendorPrices[] = $this->vendorPriceMapper->createFromData($priceData);
		}
		$vendor->setPricing($vendorPrices);

        return $vendor;
    }
}

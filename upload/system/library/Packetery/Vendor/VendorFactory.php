<?php

namespace Packetery\Vendor;

use http\Exception\InvalidArgumentException;
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

    /** @var \Language */
    private $language;

    /**
     * @param CarrierRepository $carrierRepository
     * @param PacketaRepository $packetaRepository
     * @param VendorPriceRepository $vendorPriceRepository
     * @param VendorMapper $vendorMapper
     * @param VendorPriceMapper $vendorPriceMapper
     * @param PacketaMapper $packetaMapper
     * @param CarrierMapper $carrierMapper
     * @param \Language $language
     */
    public function __construct(
        CarrierRepository $carrierRepository,
        PacketaRepository $packetaRepository,
        VendorPriceRepository $vendorPriceRepository,
        VendorMapper $vendorMapper,
        VendorPriceMapper $vendorPriceMapper,
        PacketaMapper $packetaMapper,
        CarrierMapper $carrierMapper,
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
     * @param array $vendorPricesData
     * @return Vendor
     */
    public function create(array $vendorData, array $vendorPricesData) {
        /**
         * TODO: co udělat ve vendor factory metodu validate, kde ověříme, že $vendorData má všechno.
         * Neměla by i tady být nějaká validace ? - data se netahají jen z formuláře, ale i z DB.
         */
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

        $vendorPrices = [];
        foreach ($vendorPricesData as $vendorPriceData) {
            $vendorPrices[] = $this->vendorPriceMapper->createFromData($vendorPriceData);
        }
        $vendor->setPricing($vendorPrices);

        return $vendor;
    }
}

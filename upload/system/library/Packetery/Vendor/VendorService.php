<?php

namespace Packetery\Vendor;

use Exception;
use Packetery\DAL\Entity\Vendor;
use Packetery\DAL\Repository\VendorPriceRepository;
use Packetery\DAL\Repository\VendorRepository;

class VendorService {

    /** @var VendorRepository */
    private $vendorRepository;

    /** @var VendorPriceRepository */
    private $vendorPriceRepository;

    /** @var VendorFactory */
    private $vendorFactory;

    public function __construct(
        VendorRepository $vendorRepository,
        VendorPriceRepository $vendorPriceRepository,
        VendorFactory $vendorFactory
    ) {
        $this->vendorRepository = $vendorRepository;
        $this->vendorPriceRepository = $vendorPriceRepository;
        $this->vendorFactory = $vendorFactory;
    }

    /**
     * @param string $countryCode
     * @param bool $onlyEnabled
     * @return Vendor[]
     * @throws Exception
     */
    public function fetchVendorsWithTransportByCountry($countryCode, $onlyEnabled = false) {
        $vendorsData = $this->vendorRepository->getAll($onlyEnabled);
        $vendors = [];

        foreach ($vendorsData as $vendorData) {
            $vendor = $this->vendorFactory->create($vendorData);

            if ($vendor->getTransport()->getCountry() === $countryCode) {
                $vendors[] = $vendor;
            }
        }

        return $vendors;
    }

    /**
     * @param int $id
     * @return Vendor|null
     */
    public function fetchVendorWithTransportById($id) {
        $vendorData = $this->vendorRepository->byId($id);

        if (!$vendorData) {
            return null;
        }

        return $this->vendorFactory->create($vendorData);
    }

    /**
     * @param Vendor[] $vendors
     * @param float $weight
     * @return array
     */
    public function getVendorsPrices(array $vendors, $weight) {
        $vendorPrices = [];

        foreach ($vendors as $vendor) {
            $priceForVendor = $this->getPriceForVendor($vendor, $weight);
            $vendorPrices[$vendor->getId()] = $priceForVendor;
        }

        return $vendorPrices;
    }

    /**
     * @param Vendor $vendor
     * @param float $weight
     * @return float|null
     */
    public function getPriceForVendor(Vendor $vendor, $weight) {
        $priceForVendor = null;

        foreach ($vendor->getPrices() as $price) {
            if ($weight <= $price->getMaxWeight()) {
                $priceForVendor = $price->getPrice();
                break;
            }
        }

        return $priceForVendor;
    }

    /**
     * @param Vendor $vendor
     * @return void
     */
    public function save(Vendor $vendor) {
        $insertedId = $this->vendorRepository->insertVendor($vendor);
        $vendor->setId($insertedId);

        $this->vendorPriceRepository->deleteByVendor($vendor);

        foreach ($vendor->getPrices() as $vendorPrice) {
            $vendorPrice->setVendorId($insertedId);
            $this->vendorPriceRepository->save($vendorPrice);
        }
    }

    /**
     * @param Vendor $vendor
     * @return void
     */
    public function delete(Vendor $vendor) {
        $this->vendorRepository->deleteVendor($vendor);
        $this->vendorPriceRepository->deleteByVendor($vendor);
    }

    /**
     * Dostává data z formuláře a převádí na data pro entitu
     * @param array $postedData
     * @return array
     */
    public function prepareFormData(array $postedData) {
        $vendor = $postedData['vendor'];
        $vendorData = [];

        if (isset($postedData['id'])) {
            $vendorData['id'] = $postedData['id'];
        } else {
            $vendorData['id'] = null;
        }

        if (is_numeric($vendor)) {
            $vendorData['carrier_id'] = (int)$vendor;
            $vendorData['packeta_id'] = null;
        } else {
            $vendorData['carrier_id'] = null;
            $vendorData['packeta_id'] = $vendor;
        }

        $cartName = trim($postedData['cart_name']);
        $vendorData['cart_name'] = $cartName ?: null;
        $vendorData['is_enabled'] = (bool)$postedData['is_enabled'];
        $vendorData['weight_rules'] = isset($postedData['weight_rules']) ? $postedData['weight_rules'] : [];

        $freeShippingLimit = trim($postedData['free_shipping_limit']);
        $vendorData['free_shipping_limit'] = $freeShippingLimit ? (float)$freeShippingLimit : null;

        return $vendorData;
    }
}

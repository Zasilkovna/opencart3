<?php

namespace Packetery\Vendor;

use Packetery\Carrier\CarrierRepository;
use Packetery\DAL\Entity\Carrier;
use Packetery\DAL\Entity\Packeta;
use Packetery\DAL\Mapper\CarrierMapper;
use Packetery\DAL\Repository\PacketaRepository;

class Page {

    /** @var PacketaRepository */
    private $packetaRepository;

    /** @var CarrierRepository */
    private $carrierRepository;

    /** @var CarrierMapper */
    private $carrierMapper;

    /** @var VendorService */

    private $vendorService;
    /** @var \Language */
    private $language;

    public function __construct(
        PacketaRepository $packetaRepository,
        CarrierRepository $carrierRepository,
        CarrierMapper     $carrierMapper,
        VendorService     $vendorService,
        \Language         $language
    ) {
        $this->packetaRepository = $packetaRepository;
        $this->carrierRepository = $carrierRepository;
        $this->carrierMapper = $carrierMapper;
        $this->language = $language;
        $this->vendorService = $vendorService;
    }

    /**
     * @param array $weightRules
     *
     * @return array
     */
    public function removeEmptyWeightRules(array $weightRules) {
        return array_filter($weightRules, static function ($rule) {
            return !(empty($rule['max_weight']) && empty($rule['price']));
        });
    }

    /**
     * @param array $formData
     *
     * @return array
     */
    public function validate(array $formData) {
        $errors = [];
        /** TODO: validovat zda $formData['vendor'] existuje - jde o id dopravce nebo vendora packety.
         * Seznam dopravců se aktualizuje cronem. Může nastat situace, že během vyplňování formuláře,
         * dojde k odstranění dopravce.
         */
        if (empty($formData['vendor'])) {
            $errors['vendor'] = $this->language->get('vendor_add_error_required_vendor');
        }

        if (empty($formData['weight_rules'])) {
            $errors['weight_rules_missing'] = $this->language->get('vendor_add_error_weight_rules_missing');
        } else {
            $weightRulesErrors = $this->validateWeightRules($formData['weight_rules']);
            if (!empty($weightRulesErrors)) {
                $errors['weight_rules'] = $weightRulesErrors;
            }
        }

        return $errors;
    }

    /**
     * @param array $weightRules
     *
     * @return array
     */
    private function validateWeightRules(array $weightRules) {
        $errors = [];
        $weights = [];

        foreach ($weightRules as $index => $rule) {
            if (!is_numeric($rule['max_weight']) || $rule['max_weight'] <= 0) {
                $errors[$index]['max_weight'] = $this->language->get('vendor_add_error_rule_max_weight_invalid');
            } else {
                if (in_array($rule['max_weight'], $weights, true)) {
                    $errors[$index]['max_weight'] = $this->language->get('vendor_add_error_rule_duplicate_weights');
                }
                $weights[] = $rule['max_weight'];
            }

            if (!is_numeric($rule['price']) || $rule['price'] <= 0) {
                $errors[$index]['price'] = $this->language->get('vendor_add_error_rule_price_invalid');
            }
        }

        return $errors;
    }

    /**
     * @param string $countryCode
     * @return array
     * @throws \Exception
     */
    public function getUnusedCarriersList($countryCode) {
        $carriers = $this->carrierRepository->getCarriersByCountry($countryCode);
        $carrierEntity = [];
        foreach ($carriers as $key => $carrier) {
            $carrierEntity[$key] = $this->carrierMapper->createFromData($carrier);
        }

        $existingVendors = $this->vendorService->fetchVendorsWithTransportByCountry($countryCode);

        foreach ($existingVendors as $vendor) {
            $transport = $vendor->getTransport();
            if ($transport instanceof Carrier) {
                unset($carrierEntity[$transport->getId()]);
            }
        }

        return $carrierEntity;
    }

    /**
     * @param $countryCode
     * @return array
     */
    public function getUnusedPacketaVendorsList($countryCode) {
        $packetaVendors = $this->packetaRepository->byCountry($countryCode);
        $existingVendors = $this->vendorService->fetchVendorsWithTransportByCountry($countryCode);

        foreach ($existingVendors as $vendor) {
            $transport = $vendor->getTransport();
            if ($transport instanceof Packeta) {
                unset($packetaVendors[$transport->getId()]);
            }
        }

        array_walk_recursive($packetaVendors, function(&$item, $key) {
            if ($key === 'name') {
                $item = $this->language->get($item);
            }
        });

        return $packetaVendors;
    }
}

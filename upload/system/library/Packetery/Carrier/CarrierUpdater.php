<?php

namespace Packetery\Carrier;

class CarrierUpdater {
    /** @var CarrierRepository */
    private $carrierRepository;

    /**
     * @param CarrierRepository $carrierRepository
     */
    public function __construct(CarrierRepository $carrierRepository) {
        $this->carrierRepository = $carrierRepository;
    }

    /**
     * @param array $carriers data retrieved from API
     * @return bool
     */
    public function validateCarrierData(array $carriers) {
        foreach ($carriers as $carrier) {
            if (!isset(
                $carrier['id'],
                $carrier['name'],
                $carrier['country'],
                $carrier['currency'],
                $carrier['pickupPoints'],
                $carrier['apiAllowed'],
                $carrier['separateHouseNumber'],
                $carrier['customsDeclarations'],
                $carrier['requiresEmail'],
                $carrier['requiresPhone'],
                $carrier['requiresSize'],
                $carrier['disallowsCod'],
                $carrier['maxWeight']
            )) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array $carriers validated data retrieved from API
     * @return array data to store in db
     */
    private function carriersMapper(array $carriers) {
        $mappedData = [];

        $carrierBooleanParams = [
            'is_pickup_points' => 'pickupPoints',
            'has_carrier_direct_label' => 'apiAllowed',
            'separate_house_number' => 'separateHouseNumber',
            'customs_declarations' => 'customsDeclarations',
            'requires_email' => 'requiresEmail',
            'requires_phone' => 'requiresPhone',
            'requires_size' => 'requiresSize',
            'disallows_cod' => 'disallowsCod',
        ];

        foreach ($carriers as $carrier) {
            $carrierId = (int)$carrier['id'];
            $carrierData = [
                'name' => $carrier['name'],
                'country' => $carrier['country'],
                'currency' => $carrier['currency'],
                'max_weight' => (float)$carrier['maxWeight'],
                'deleted' => false,
            ];
            foreach ($carrierBooleanParams as $columnName => $paramName) {
                $carrierData[$columnName] = ($carrier[$paramName] === 'true');
            }
            $mappedData[$carrierId] = $carrierData;
        }

        return $mappedData;
    }

    /**
     * @param array $carriers validated data retrieved from API
     * @return void
     */
    public function saveCarriers(array $carriers) {
        $mappedData = $this->carriersMapper($carriers);
        $carriersInFeed = [];

        $carrierCheck = $this->carrierRepository->getCarrierIds();
        $carriersInDb = array_column($carrierCheck->rows, 'id');

        foreach ($mappedData as $carrierId => $carrier) {
            $carriersInFeed[] = $carrierId;
            if (in_array($carrierId, $carriersInDb)) {
                $this->carrierRepository->update('zasilkovna_carrier', $carrier, ['id' => $carrierId]);
            } else {
                $carrier['id'] = $carrierId;
                $this->carrierRepository->insert('zasilkovna_carrier', $carrier);
            }
        }

        $this->carrierRepository->setOthersAsDeleted($carriersInFeed);
    }
}

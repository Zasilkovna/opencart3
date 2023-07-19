<?php

namespace Packetery\Carrier;

use Packetery\API\CarriersDownloader;
use Packetery\API\Exceptions\DownloadException;

class CarrierImporter {

    /** @var CarriersDownloader $carriersDownloader */
    private $carriersDownloader;

    /** @var CarrierUpdater $carrierUpdater */
    private $carrierUpdater;

    /**
     * @param CarriersDownloader $carriersDownloader
     * @param CarrierUpdater     $carrierUpdater
     */
    public function __construct(
        CarriersDownloader $carriersDownloader,
        CarrierUpdater     $carrierUpdater
    ) {
        $this->carriersDownloader = $carriersDownloader;
        $this->carrierUpdater = $carrierUpdater;
    }

    /**
     * @return string[]
     */
    public function run() {
        $result = [
            'status' => 'error',
            'message' => '',
        ];

        try {
            $carriers = $this->carriersDownloader->fetchAsArray();
        } catch (DownloadException $e) {
            $result['message'] = $e->getMessage();
            return $result;
        }
        if (!$carriers) {
            $result['message'] = 'cron_empty_carriers';
            return $result;
        }
        $validationResult = $this->carrierUpdater->validateCarrierData($carriers);
        if (!$validationResult) {
            $result['message'] = 'cron_invalid_carriers';
            return $result;
        }
        $this->carrierUpdater->saveCarriers($carriers);
        $result['status'] = 'success';
        $result['message'] = 'carriers_updated';

        return $result;
    }
}

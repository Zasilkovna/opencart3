<?php

namespace Packetery\Carrier;

use Packetery\API\CarriersDownloader;
use Packetery\API\Exceptions\DownloadException;

class CarrierImporter {
	/**
	 * @var CarriersDownloader $carriersDownloader
	 */
	private $carriersDownloader;

	/**
	 * @var CarrierUpdater $carrierUpdater
	 */
	private $carrierUpdater;

	/**
	 * @var CarrierRepository $carrierRepository
	 */
	private $carrierRepository;
	/**
	 * @var \Language $language
	 */
	private $language;

	/**
	 * @param CarriersDownloader $carriersDownloader
	 * @param CarrierUpdater     $carrierUpdater
	 * @param CarrierRepository  $carrierRepository
	 */
	public function __construct(
		CarriersDownloader $carriersDownloader,
		CarrierUpdater     $carrierUpdater,
		CarrierRepository  $carrierRepository
	) {
		$this->carriersDownloader = $carriersDownloader;
		$this->carrierUpdater = $carrierUpdater;
		$this->carrierRepository = $carrierRepository;
	}

	/**
	 * @return string[]
	 */
	public function import() {
		if (!$this->carrierRepository->isCarrierTableEmpty()) {
			return ['success' => 'already initialized'];
		}

		return $this->downloadUpdate();
	}

	/**
	 * @return string[]
	 */
	public function downloadUpdate() {

		try {
			$carriers = $this->carriersDownloader->fetchAsArray();
		} catch (DownloadException $e) {
			return ['error' => sprintf($this->language->get('cron_download_failed'), $e->getMessage())];
		}
		if (!$carriers) {
			return ['error' => sprintf($this->language->get('cron_download_failed'), $this->language->get('cron_empty_carriers'))];
		}
		$validationResult = $this->carrierUpdater->validateCarrierData($carriers);
		if (!$validationResult) {
			return ['error' => sprintf($this->language->get('cron_download_failed'), $this->language->get('cron_invalid_carriers'))];
		}
		$this->carrierUpdater->saveCarriers($carriers);

		return ['success' => $this->language->get('carriers_updated')];
	}

	/**
	 * @param \Language $language
	 *
	 * @return void
	 */
	public function setLanguage($language) {
		$this->language = $language;
	}
}

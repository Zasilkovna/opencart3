<?php

use Packetery\Db\BaseRepository;
use Packetery\Carrier\CarrierRepository;
use Packetery\Carrier\CarrierUpdater;
use Packetery\API\CarriersDownloader;
use Packetery\API\Exceptions\DownloadException;

require_once DIR_SYSTEM . 'library/Packetery/autoload.php';

/**
 * Controller for catalog part of extension for "zasilkovna" shipping module.
 *
 * @property Loader $load
 * @property \Document $document
 * @property Request $request
 * @property Response $response
 * @property Session $session
 */
class ControllerExtensionModuleZasilkovna extends Controller {

	/** @var BaseRepository */
	private $baseRepository;

	/** @var CarrierRepository */
	private $carrierRepository;

	/** @var CarrierUpdater */
	private $carriersUpdater;

	/** @var CarriersDownloader
	 */private $carriersDownloader;

	/**
	 * @param Registry $registry
	 */
	public function __construct($registry)
	{
		parent::__construct($registry);

		$this->baseRepository = new BaseRepository($this->db);
		$this->carrierRepository = new CarrierRepository($this->db);
		$this->carriersUpdater = new CarrierUpdater($this->baseRepository, $this->carrierRepository);
		$this->carriersDownloader = new CarriersDownloader($this->config->get('shipping_zasilkovna_api_key'), new \GuzzleHttp\Client());
	}

	/**
	 * Loads properties of selected branch from session.
	 *
	 * @throws Exception
	 */
	public function loadSelectedBranch() {
		$this->load->model('extension/shipping/zasilkovna');
		$defaults = $this->model_extension_shipping_zasilkovna->loadSelectedBranch();

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($defaults));
	}

	/**
	 * Loads properties of selected branch from session.
	 *
	 * @throws Exception
	 */
	public function saveSelectedBranch() {
		$this->load->model('extension/shipping/zasilkovna');
		$this->model_extension_shipping_zasilkovna->saveSelectedBranch();
	}

	/**
	 * Save additional order data to DB during "order confirm".
	 * All required records with order data are created in DB during this step.
	 * This method is called by "after" event on catalog/controller/checkout/confirm.
	 *
	 * @param string $route
	 * @param array $args
	 * @param int $output
	 * @throws Exception
	 */
	public function saveOrderData(&$route, &$args, &$output) {
		$this->load->model('extension/shipping/zasilkovna');
		$this->model_extension_shipping_zasilkovna->saveOrderData();
	}

	/**
	 * Clean-up of additional order data in session when order is finished.
	 * This method is called as "before" event on catalog/controller/checkout/success.
	 *
	 * @param string $route
	 * @param array $args
	 * @return void
	 * @throws Exception
	 */
	public function sessionCleanup(&$route, &$args)
	{
		// check if order is already completed
		// the same check is implemented in original method
		if (isset($this->session->data['order_id'])) {
			$this->load->model('extension/shipping/zasilkovna');
			$this->model_extension_shipping_zasilkovna->sessionCleanup();
		}
	}

	public function sessionCleanupAndSaveSelectedCountry($cartType, $newCountryId, $oldCountryId)
	{
		$this->load->model('extension/shipping/zasilkovna');

		if ($oldCountryId != $newCountryId) {
			$this->model_extension_shipping_zasilkovna->sessionCleanup();
		}
		$this->model_extension_shipping_zasilkovna->saveSelectedCountry($cartType);
	}

	public function sessionCheckOnShippingChange(&$route, &$args)
	{
		$oldAddressId = $this->session->data["shipping_address"]["address_id"];
		$newAddressId = $this->request->post["address_id"];
		if ($oldAddressId != $newAddressId) {
			$this->load->model('extension/shipping/zasilkovna');
			$this->model_extension_shipping_zasilkovna->sessionCleanup();
		}
	}

	public function sessionCheckOnShippingChangeGuest(&$route, &$args)
	{
		$newCountryId = $this->request->post['country_id'];
		$oldCountryId = $this->session->data['country_id'];
		$this->sessionCleanupAndSaveSelectedCountry('standard', $newCountryId, $oldCountryId);
	}

	/**
	 * Event for OPC Journal 3
	 * @param $route
	 * @param $args
	 */
	public function journal3CheckoutSave(&$route, &$args)
	{
		$newCountryId = $this->request->post['order_data']['shipping_country_id'];
		$oldCountryId = isset($this->session->data['country_id']) ? $this->session->data['country_id'] : NULL;
		$this->sessionCleanupAndSaveSelectedCountry('journal3', $newCountryId, $oldCountryId);
	}

	public function addStyleAndScript(&$route, &$args)
	{
		$this->document->addScript('https://widget.packeta.com/v6/www/js/library.js');
		$this->document->addScript('catalog/view/javascript/zasilkovna/shippingExtension.js');
		$this->document->addStyle('catalog/view/theme/zasilkovna/zasilkovna.css');
	}

	public function updateCarriers()
	{
		$this->load->language('extension/shipping/zasilkovna');
		if ($this->request->get['token'] !== $this->config->get('shipping_zasilkovna_cron_token')) {
			echo $this->language->get('please_provide_token');
			return;
		}
		// TODO: validate API key to display proper message
		try {
			$carriers = $this->carriersDownloader->fetchAsArray();
		} catch (DownloadException $e) {
			echo sprintf($this->language->get('cron_download_failed'), $e->getMessage());
			return;
		}
		if (!$carriers) {
			echo sprintf($this->language->get('cron_download_failed'), $this->language->get('cron_empty_carriers'));
			return;
		}
		$validationResult = $this->carriersUpdater->validateCarrierData($carriers);
		if (!$validationResult) {
			echo sprintf($this->language->get('cron_download_failed'), $this->language->get('cron_invalid_carriers'));
			return;
		}
		$this->carriersUpdater->saveCarriers($carriers);
		echo $this->language->get('carriers_updated');
	}

}

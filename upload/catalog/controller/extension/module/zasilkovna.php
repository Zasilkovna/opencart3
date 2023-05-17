<?php

use Packetery\API\CarriersDownloader;
use Packetery\DI\Container;
use Packetery\Carrier\CarrierImporter;

require_once DIR_SYSTEM . 'library/Packetery/deps/autoload.php';

/**
 * Controller for catalog part of extension for "zasilkovna" shipping module.
 *
 * @property Loader $load
 * @property \Document $document
 * @property Request $request
 * @property Response $response
 * @property Session $session
 * @property \ModelExtensionShippingZasilkovna $model_extension_shipping_zasilkovna
 * @property ModelAccountAddress $model_account_address
 */
class ControllerExtensionModuleZasilkovna extends Controller {

	/** @var \Packetery\Order\OrderFacade */
	private $orderFacade;

	/** @var Container */
	private $diContainer;

	/**
	 * @param Registry $registry
	 * @throws \ReflectionException
	 */
	public function __construct($registry)
	{
		parent::__construct($registry);

		$this->diContainer = \Packetery\DI\ContainerFactory::create($registry);
		$this->orderFacade = $this->diContainer->get(\Packetery\Order\OrderFacade::class);
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
	 * Save additional order data to DB during "order confirm". Only for journal3.
	 *
	 * @param string $route
	 * @param array $args
	 * @param int $output
	 * @throws Exception
	 */
	public function journal3SaveOrderData(&$route, &$args, &$output) {
		$this->load->model('extension/shipping/zasilkovna');
		$this->model_extension_shipping_zasilkovna->journal3SaveOrderData();
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
			$this->orderFacade->sessionCleanup();
		}
	}

	public function sessionCleanupAndSaveSelectedCountry($cartType, $newCountryId, $oldCountryId)
	{
		if ($oldCountryId != $newCountryId) {
			$this->orderFacade->sessionCleanup();
		}

		$this->load->model('extension/shipping/zasilkovna');
		$this->model_extension_shipping_zasilkovna->saveSelectedCountry($cartType);
	}

	public function sessionCheckOnShippingChange(&$route, &$args)
	{
		if (!isset($this->session->data['shipping_address'], $this->session->data['shipping_address']['address_id'])) {
			$this->log->write('Session shipping address is empty. For security reasons we reset Packeta Pickup Point data.');
			$this->orderFacade->sessionCleanup();
			return;
		}

		$this->load->model('account/address');

		if (isset($this->session->data['shipping_address']['country_id'])) {
			$oldCountryId =  (int) $this->session->data['shipping_address']['country_id'];
		} else {
			$oldAddressId = (int) $this->session->data['shipping_address']['address_id'];
			$oldAddress = $this->model_account_address->getAddress($oldAddressId);
			$oldCountryId = $oldAddress ? (int) $oldAddress['country_id'] : 0;
		}

		if ($this->request->post['shipping_address'] === 'new') {
			$newCountryId = (int) $this->request->post['country_id'];
		} else {
			$newAddress = $this->model_account_address->getAddress($this->request->post['address_id']);
			$newCountryId = $newAddress ? (int) $newAddress['country_id'] : 0;
		}

		if ($oldCountryId !== $newCountryId) {
			$this->orderFacade->sessionCleanup();
		}
	}

	public function sessionCheckOnShippingChangeGuest(&$route, &$args)
	{
		$newCountryId = $this->request->post['country_id'];
		$oldCountryId = null;
		if (isset($this->session->data['country_id'])) {
			$oldCountryId = $this->session->data['country_id'];
		}
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
		$this->document->addScript('catalog/view/javascript/zasilkovna/shippingExtension.js?v=2.16'); //TODO: nedávat číslo verze ručně
		$this->document->addStyle('catalog/view/theme/zasilkovna/zasilkovna.css');
	}

	/**
	 * @return void
	 * @throws ReflectionException
	 */
	public function updateCarriers()
	{
		$this->load->language('extension/shipping/zasilkovna');
		$apiKey = $this->config->get('shipping_zasilkovna_api_key');

		if ($this->request->get['token'] !== $this->config->get('shipping_zasilkovna_cron_token')) {
			echo $this->language->get('please_provide_token');
			return;
		}

		/** @var CarrierImporter $carrierImporter */
		$carrierImporter = $this->diContainer->get(CarrierImporter::class);
		$result = $carrierImporter->run();

		if ($result['status'] === 'success') {
			echo $this->language->get($result['message']);
		} else {
			echo sprintf($this->language->get('cron_download_failed'), $result['message']);
		}
	}

	/**
	 * Handler for catalog/controller/api/order/edit/after
	 * @param string $route
	 * @param array $args
	 * @param int $output
	 *
	 * @throws Exception
	 */
	public function handleApiOrderEditAfter(&$route, &$args, &$output)
	{
		$params = $this->request->request;
		if (
			(isset($params['shipping_method']) && strpos($params['shipping_method'], 'zasilkovna') !== false )
			|| !(isset($params['order_id']) && is_numeric($params['order_id']))
		) {
			return;
		}

		$this->load->model('extension/shipping/zasilkovna');
		$this->model_extension_shipping_zasilkovna->deleteIfOrderNotPacketaShipping((int) $params['order_id']);
	}
}

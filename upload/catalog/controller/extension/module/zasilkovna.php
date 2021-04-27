<?php
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

	public function cronExecute()
	{
		$this->load->language('extension/shipping/zasilkovna');
		if ($this->request->get['token'] !== $this->config->get('shipping_zasilkovna_cron_token')) {
			echo $this->language->get('please_provide_token');
			return '';
		}
		$packeteryCarriersManager = new PacketeryCarriersManager($this->config->get('shipping_zasilkovna_api_key'));
		$this->load->model('extension/shipping/zasilkovna');
		$this->model_extension_shipping_zasilkovna->updateCarriers($packeteryCarriersManager->getCarriers());
		echo $this->language->get('carriers_updated');
	}

}

class PacketeryCarriersManager
{
	CONST API_URL = 'https://www.zasilkovna.cz/api/v4/%s/branch.json?address-delivery';
	private $apiKey;

	/**
	 * @param string $apiKey
	 */
	public function __construct($apiKey)
	{
		$this->apiKey = $apiKey;
	}

	/**
	 * @return string|false
	 */
	public function getCarriersJSON()
	{
		$url = sprintf(self::API_URL, $this->apiKey);
		$client = new GuzzleHttp\Client();
		// TODO: Exception catching
		$res = $client->get($url);
		return $res->getBody();
	}

	/**
	 * @return array|false
	 */
	public function getCarriers()
	{
		$json = $this->getCarriersJSON();
		if ($json) {
			$carriersData = json_decode($json, true);
			if ($carriersData) {
				return $carriersData['carriers'];
			}
		}
		return false;
	}

}

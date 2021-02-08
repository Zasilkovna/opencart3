<?php
/**
 * Controller for catalog part of extension for "zasilkovna" shipping module.
 *
 * @property Loader $load
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
	public function sessionCleanup(&$route, &$args) {
		$this->load->model('extension/shipping/zasilkovna');
		$this->model_extension_shipping_zasilkovna->sessionCleanup();
	}
}

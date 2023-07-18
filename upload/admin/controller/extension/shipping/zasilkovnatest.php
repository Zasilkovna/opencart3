<?php

use Packetery\API\KeyValidator;
use Packetery\Carrier\CarrierRepository;

require_once DIR_SYSTEM . 'library/Packetery/autoload.php';
require_once DIR_APPLICATION . 'controller/extension/shipping/zasilkovna.php';
/**
 * Controller for test for "zasilkovna" shipping module.
 *
 * List of classes created and registered in "system registry" of e-shop
 * @property Config $config
 * @property \Cart\Currency $currency
 * @property Document $document
 * @property Language $language
 * @property Loader $load
 * @property ModelExtensionShippingZasilkovna $model_extension_shipping_zasilkovna
 * @property ModelLocalisationGeoZone $model_localisation_geo_zone
 * @property ModelLocalisationOrderStatus $model_localisation_order_status
 * @property ModelLocalisationTaxClass $model_localisation_tax_class
 * @property ModelLocalisationCountry $model_localisation_country
 * @property ModelSettingSetting model_setting_setting
 * @property ModelSettingStore model_setting_store
 * @property ModelSettingExtension model_setting_extension
 * @property \ModelExtensionShippingZasilkovnaCountries $model_extension_shipping_zasilkovna_countries
 * @property ModelExtensionShippingZasilkovnaOrders $model_extension_shipping_zasilkovna_orders
 * @property ModelExtensionShippingZasilkovnaShippingRules $model_extension_shipping_zasilkovna_shipping_rules
 * @property ModelExtensionShippingZasilkovnaWeightRules $model_extension_shipping_zasilkovna_weight_rules
 * @property Request $request
 * @property Response $response
 * @property Session $session
 * @property Url $url
 * @property \Cart\User $user
 */
class ControllerExtensionShippingZasilkovna extends Controller {

	/** @var string base routing path for Zasilkovna module (controller action, language file, model) */
	const ROUTING_BASE_PATH = 'extension/shipping/zasilkovnatest';

	// set of constants for weight rules actions
	const ACTION_WEIGHT_RULES = 'weight_rules';
	const ACTION_WEIGHT_RULES_ADD = 'weight_rules_add';
	const ACTION_WEIGHT_RULES_EDIT = 'weight_rules_edit';
	const ACTION_WEIGHT_RULES_DELETE = 'weight_rules_delete';

	// set of constants for shipping rules actions
	const ACTION_SHIPPING_RULES = 'shipping_rules';
	const ACTION_SHIPPING_RULES_ADD = 'shipping_rules_add';
	const ACTION_SHIPPING_RULES_EDIT = 'shipping_rules_edit';
	const ACTION_SHIPPING_RULES_DELETE = 'shipping_rules_delete';


	/** @var string name of url parameter for country code */
	const PARAM_COUNTRY = 'country';

	/** @var string name of template parameter for success message */
	const TEMPLATE_MESSAGE_SUCCESS = 'success';
	/** @var string name of template parameter for error message */
	const TEMPLATE_MESSAGE_ERROR = 'error_warning';

	// set of constants of language independent identifiers for description text
	const TEXT_TITLE_MAIN = 'Zásilkovna';

	/** @var KeyValidator */
	private $keyValidator;

	/** @var CarrierRepository */
	private $carrierRepository;

	/** @var \Packetery\Vendor\VendorRepository */
	private $vendorRepository;

	/** @var \Packetery\DI\Container */
	private $diContainer;

    /** @var \Packetery\Vendor\VendorFacade */
    private $vendorFacade;

    /**
     * @param Registry $registry
     */
    public function __construct(Registry $registry) {
        parent::__construct($registry);

        $this->keyValidator = new KeyValidator();
        $this->carrierRepository = new CarrierRepository($this->db);
        $this->vendorRepository = new \Packetery\Vendor\VendorRepository($this->db);
        $this->diContainer = \Packetery\DI\ContainerFactory::create($registry);
        $this->vendorFacade = new \Packetery\Vendor\VendorFacade($this->vendorRepository, $this->language);
    }


	/**
	 * @return array
	 */
	private function getAdminLinkUrlParameters() {
		$getParameters = $this->request->get;
		unset($getParameters['user_token'], $getParameters['route']);

		foreach ($getParameters as $getParameterKey => &$getParameter) {
			if ($getParameter === '') {
				unset($getParameters[$getParameterKey]);
			}
		}

		return $getParameters;
	}

	/**
	 * Check if user has permission to change module settings.
	 *
	 * @return bool TRUE = success, FALSE = error
	 */
	private function checkPermissions() {
		if (!$this->user->hasPermission('modify', self::ROUTING_BASE_PATH)) {
			$data[self::TEMPLATE_MESSAGE_ERROR] = $this->language->get('error_permission');
			return false;
		}

		return true;
	}

	/**
	 * Method for page initialization. Returns customized base content of template data.
	 *
	 * @param string $actionName internal name of module action
	 * @param string $titleId language independent identifier of page title
	 * @param array $urlParameters additional parameters to url
	 * @return array initial version of template data
	 */
	private function initPageData($actionName, $titleId, $urlParameters = []) {
		// load language file for module
		$this->load->language(\ControllerExtensionShippingZasilkovna::ROUTING_BASE_PATH);
		// set page (document) title
		$this->document->setTitle($this->language->get($titleId));

		// creation of customized common part of template data
		$data = [
			// common parts of page (header, left column with system menu, footer)
			'header' => $this->load->controller('common/header'),
			'column_left' => $this->load->controller('common/column_left'),
			'footer' => $this->load->controller('common/footer'),
			'breadcrumbs' => [
				[
					'text' => $this->language->get('text_home'),
					'href' => $this->createAdminLink('common/dashboard')
				],
				[
					'text' => $this->language->get('text_shipping'),
					'href' => $this->createAdminLink('marketplace/extension', ['type' => 'shipping'])
				],
				[
					'text' => $this->language->get(self::TEXT_TITLE_MAIN),
					'href' => $this->createAdminLink('')
				]
			]
		];

		if (in_array($actionName, [
			self::ACTION_SHIPPING_RULES,
			self::ACTION_SHIPPING_RULES_ADD,
			self::ACTION_SHIPPING_RULES_DELETE,
			self::ACTION_SHIPPING_RULES_EDIT,
			self::ACTION_WEIGHT_RULES,
			self::ACTION_WEIGHT_RULES_ADD,
			self::ACTION_WEIGHT_RULES_DELETE,
			self::ACTION_WEIGHT_RULES_EDIT,
		], true)) {
			$data['breadcrumbs'][] = [
				'text' => $this->language->get('text_pricing_rules'),
				'href' => $this->createAdminLink('pricing_rules'),
			];
		}
		// last part of "breadcrumbs" is added only for nonempty action name (pages of module)
		if (!empty($actionName)) {
			$data['breadcrumbs'][] = [
				'text' => $this->language->get($titleId),
				'href' => $this->createAdminLink($actionName, $urlParameters)
			];
		}

		// check if some error/success messages are stored in session and set it as template parameters
		$templateParameters = [
			self::TEMPLATE_MESSAGE_SUCCESS,
			self::TEMPLATE_MESSAGE_ERROR,
			'error_warning_multirow',
			'alert_info',
			'alert_info_heading',
			'api_key_validation_error',
			'flashMessage',
		];
		foreach ($templateParameters as $templateParameter) {
			if (isset($this->session->data[$templateParameter])) {
				$data[$templateParameter] = $this->session->data[$templateParameter];
				unset($this->session->data[$templateParameter]);
			}
		}

		return $data;
	}

	/**
	 * Creates link to given action in administration including user token.
	 *
	 * @param string $actionName internal name of module action
	 * @param array $urlParameters additional parameters to url
	 * @return string
	 */
	private function createAdminLink($actionName, $urlParameters = [])
	{
		// empty action name => main page of module
		if ('' == $actionName) {
			$actionName = self::ROUTING_BASE_PATH;
		}

		// action name without slash (/) => action of module
		if (strpos($actionName, '/') === false) {
			$actionName = self::ROUTING_BASE_PATH . '/' . $actionName;
		}

		// otherwise action name is absolute routing path => no change in action name
		// user token must be part of any administration link
		$urlParameters['user_token']  = $this->session->data['user_token'];

		return $this->url->link($actionName, $urlParameters, true);
	}

    public function test() {
        $data = $this->initPageData('test', self::TEXT_TITLE_MAIN);
        $data['debugs'] =  ['controller' =>'zasilkovna test controller'];
        $data['link'] = $this->createAdminLink('test2');
        $this->response->setOutput($this->load->view('extension/shipping/zasilkovna_test', $data));
    }

    public function test2() {
        $data = $this->initPageData('test2', self::TEXT_TITLE_MAIN);
        $data['debugs'] =  ['controller' =>'zasilkovna test controller'];
        $data['link'] = $this->createAdminLink('test2');
        $this->response->setOutput($this->load->view('extension/shipping/zasilkovna_test', $data));
    }
}

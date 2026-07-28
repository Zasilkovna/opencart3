<?php

use Packetery\API\KeyValidator;
use Packetery\Carrier\CarrierRepository;
use Packetery\Carrier\CarrierRuleFormService;
use Packetery\Carrier\FinalCarrierNameResolver;
use Packetery\Carrier\RateType;
use Packetery\Carrier\ShippingRuleRepository;
use Packetery\Exceptions\UpgradeException;
use Packetery\Tools\Tools;

require_once DIR_SYSTEM . 'library/Packetery/autoload.php';

/**
 * Controller for admin part of extension for "zasilkovna" shipping module.
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
 * @property Request $request
 * @property Response $response
 * @property Session $session
 * @property Url $url
 * @property \Cart\User $user
 */
class ControllerExtensionShippingZasilkovna extends Controller {

    const VERSION = '2.1.11';
	/** @var string base routing path for Zasilkovna module (controller action, language file, model) */
	const ROUTING_BASE_PATH = 'extension/shipping/zasilkovna';
	/** @var string routing path for zasilkovna orders model */
	const ROUTING_ORDERS = 'extension/shipping/zasilkovna_orders';
	/** @var string routing path for zasilkovna orders model */
	const ROUTING_COUNTRIES = 'extension/shipping/zasilkovna_countries';

	// set of constant for order list actions
	const ACTION_ORDERS = 'orders';
	const ACTION_ORDERS_EXPORT = 'orders_export';
	const ACTION_ORDERS_UPDATE = 'orders_update';
	const ACTION_CARRIERS = 'carriers';
	const ACTION_CARRIERS_DETAIL = 'carriers_detail';

	/** @var string name of url parameter for carrier table record ID */
	const PARAM_CARRIER_RECORD_ID = 'carrier_record_id';

	// set of constants of url links to actions
	const TEMPLATE_LINK_ADD = 'link_add';
	const TEMPLATE_LINK_EDIT = 'link_edit';
	const TEMPLATE_LINK_DELETE = 'link_delete';
	const TEMPLATE_LINK_FORM_ACTION = 'link_form_action';
	const TEMPLATE_LINK_CANCEL = 'link_cancel';
	const TEMPLATE_LINK_BACK = 'link_back';
	const TEMPLATE_LINK_EXPORT_SELECTED = 'link_export_selected';
	const TEMPLATE_LINK_EXPORT_ALL = 'link_export_all';
	const TEMPLATE_LINK_UPDATE = 'link_update';

	/** @var string name of template parameter for success message */
	const TEMPLATE_MESSAGE_SUCCESS = 'success';
	/** @var string name of template parameter for error message */
	const TEMPLATE_MESSAGE_ERROR = 'error_warning';

	// set of constants of language independent identifiers for description text
	const TEXT_TITLE_MAIN = 'heading_title';
	const TEXT_TTILE_ORDERS = 'heading_orders';

	private Tools $packeteryTools;

	private KeyValidator $keyValidator;

	private CarrierRepository $carrierRepository;
	private ShippingRuleRepository $shippingRuleRepository;
	private FinalCarrierNameResolver $finalCarrierNameResolver;
	private CarrierRuleFormService $carrierRuleFormService;

	public function __construct($registry)
	{
		parent::__construct($registry);

		$this->packeteryTools = new Tools();
		$this->keyValidator = new KeyValidator();
		$this->carrierRepository = new CarrierRepository($this->db);
		$this->shippingRuleRepository = new ShippingRuleRepository($this->db);
		$this->finalCarrierNameResolver = new FinalCarrierNameResolver();
		$this->carrierRuleFormService = new CarrierRuleFormService();
	}

    /**
	 * Entry point (main method) for plugin installing. Is called after extension is installed.
	 *
	 * @throws Exception
	 */
	public function install() {
		$this->load->model(self::ROUTING_BASE_PATH);
		$this->model_extension_shipping_zasilkovna->createTablesAndEvents();

		// prefill default configuration items
		$defaultConfig = [
			'shipping_zasilkovna_version' => self::VERSION,
			'shipping_zasilkovna_weight_max' => '5',
			'shipping_zasilkovna_geo_zone_id' => '',
			'shipping_zasilkovna_packet_number_source' => 'order_number',
			'shipping_zasilkovna_order_statuses' => [],
			'shipping_zasilkovna_cash_on_delivery_methods' => [],
			'shipping_zasilkovna_cron_token' => $this->packeteryTools->generateToken(),
		];

        $this->load->model('setting/setting');
		$this->model_setting_setting->editSetting('shipping_zasilkovna', $defaultConfig);
	}

    /**
     * @return array
     */
    private function getSettings()
    {
        return $this->model_setting_setting->getSetting('shipping_zasilkovna');
    }

    /**
     * @return string|null
     */
    private function getSchemaVersion()
    {
        $existingSettings = $this->getSettings();
        if ($existingSettings && $this->isInstalled()) {
            if (!empty($existingSettings['shipping_zasilkovna_version'])) {
                return $existingSettings['shipping_zasilkovna_version'];
            }

            return '2.0.3';
        }

        return null;
    }

    /** Does database version differ from code version? Downgrades not supported.
     * @return bool
     */
    private function isVersionMismatch()
    {
        $version = $this->getSchemaVersion();
        if ($version && version_compare($version, self::VERSION) < 0) {
            return true;
        }

        return false;
    }

    /** Returns name of extension as its known to OpenCart
     * @return string
     */
    private function getExtensionName()
    {
        return basename(__FILE__, '.php');
    }

    /**
     * @return bool
     */
    private function isInstalled()
    {
        $this->load->model('setting/extension');
        $installed = $this->model_setting_extension->getInstalled('shipping');

        $extensionName = $this->getExtensionName();
        foreach ($installed as $installedExtensionName) {
            if ($installedExtensionName === $extensionName) {
                return true;
            }
        }

        return false;
    }

	/**
	 * Entry point (main method) for plugin uninstalling.
	 *
	 * @throws Exception
	 */
	public function uninstall() {
	    // framework deletes shipping_zasilkovna settings before calling extension uninstall method
		$this->load->model(self::ROUTING_BASE_PATH);
		$this->model_extension_shipping_zasilkovna->deleteTablesAndEvents();
	}

	/**
	 * Plugin version upgrade. The need for an upgrade is checked each time the settings page is displayed.
	 */
	public function upgrade()
	{
		$this->load->model(self::ROUTING_BASE_PATH);
		$this->load->model('setting/setting');
		$this->load->language(self::ROUTING_BASE_PATH);

		try {
			$this->model_extension_shipping_zasilkovna->upgradeSchema($this->getSchemaVersion());
		} catch (UpgradeException $exception) {
			$this->session->data['error_warning_multirow'] = [
				$this->language->get('extension_upgrade_failed'),
				$exception->getMessage(),
				$this->language->get('please_see_log'),
				$this->language->get('extension_may_not_work'),
				$this->language->get('error_needs_to_be_resolved'),
			];
			return;
		}

		$this->model_extension_shipping_zasilkovna->installEvents();

		$settings = $this->model_setting_setting->getSetting('shipping_zasilkovna');
		$settings['shipping_zasilkovna_version'] = self::VERSION;
		if (!isset($settings['shipping_zasilkovna_cron_token'])) {
			$settings['shipping_zasilkovna_cron_token'] = $this->packeteryTools->generateToken();
		}
		$this->model_setting_setting->editSetting('shipping_zasilkovna', $settings);

		$this->session->data[self::TEMPLATE_MESSAGE_SUCCESS] =
			sprintf($this->language->get('extension_upgraded'), self::VERSION);
	}

    /**
     * @return bool
     */
    private function isUpgradedNeeded()
    {
        return $this->isInstalled() && $this->isVersionMismatch();
    }

	/**
	 * Handler for main action of extension modul for Zasilkovna (main settings page).
	 *
	 * @throws Exception
	 */
	public function index() {
		$this->load->language(self::ROUTING_BASE_PATH);
		$this->load->model('setting/setting');

		if (!ini_get('allow_url_fopen')) {
			$this->session->data[self::TEMPLATE_MESSAGE_ERROR] = $this->language->get('error_disallowed_url_opening');
		}

        if ($this->isUpgradedNeeded()) {
            $this->upgrade();
        }

		$existingSettings = $this->getSettings();
		if (!isset($existingSettings['shipping_zasilkovna_api_key']) ||
			!$this->keyValidator->validateFormat($existingSettings['shipping_zasilkovna_api_key'])
		) {
			$this->session->data['alert_info_heading'] = $this->language->get('text_important');
			$this->session->data['alert_info'] = [
				$this->language->get('text_api_key_needed_part1'),
				'https://client.packeta.com/support/',
				$this->language->get('text_api_key_needed_part2'),
				$this->language->get('text_api_key_needed_part3'),
			];
			$existingSettings['shipping_zasilkovna_status'] = 0;
			$this->model_setting_setting->editSetting('shipping_zasilkovna', $existingSettings);
			// to render properly in the same request
			$this->config->set('shipping_zasilkovna_status', 0);
		}

		// save new values from POST request data to module settings
		if (($this->request->server['REQUEST_METHOD'] === 'POST') && ($this->checkPermissions())) {
			$postCopy = $this->removeInvalidKeyFromPostData();
			if (
				!isset($this->session->data['api_key_validation_error']) &&
				!isset($this->session->data[self::TEMPLATE_MESSAGE_ERROR])
			) {
				$this->model_setting_setting->editSetting('shipping_zasilkovna', $postCopy + $existingSettings);
				$this->session->data[self::TEMPLATE_MESSAGE_SUCCESS] = $this->language->get('text_success');
				unset($this->session->data['alert_info'], $this->session->data['alert_info_heading']);
				$this->response->redirect($this->createAdminLink('marketplace/extension', ['type' => 'shipping']));
			}
		}

		// full initialization of page
		$data = $this->initPageData('', self::TEXT_TITLE_MAIN);

		$this->setGlobalConfigurationForm($data);

		$this->response->setOutput($this->load->view(self::ROUTING_BASE_PATH, $data));
	}

	/**
	 * Set items of global configuration to template data.
	 *
	 * @throws Exception
	 * @var array $data page template data
	 */
	private function setGlobalConfigurationForm(&$data) {
		$data[self::TEMPLATE_LINK_FORM_ACTION] = $this->createAdminLink('');
		$data[self::TEMPLATE_LINK_CANCEL] = $this->createAdminLink('marketplace/extension', ['type' => 'shipping']);

		// loads list of secondary stores
		$this->load->model('setting/store');
		$secondaryStores = $this->model_setting_store->getStores();

		// loads values for global settings from POST request data or from module configuration
		$configurationItems = [
			'shipping_zasilkovna_api_key',
			'shipping_zasilkovna_tax_class_id',
			'shipping_zasilkovna_weight_max',
			'shipping_zasilkovna_default_free_shipping_limit',
			'shipping_zasilkovna_default_shipping_price',
			'shipping_zasilkovna_status',
			'shipping_zasilkovna_sort_order',
			'shipping_zasilkovna_geo_zone_id',
			'shipping_zasilkovna_order_statuses',
			'shipping_zasilkovna_cash_on_delivery_methods',
			'shipping_zasilkovna_eshop_identifier_0' // default store always exists
		];

		// adds form items for e-shop identifiers for secondary stores
		foreach ($secondaryStores as $storeProperties) {
			$configurationItems[] = 'shipping_zasilkovna_eshop_identifier_' . $storeProperties['store_id'];
		}

		foreach ($configurationItems as $itemName) {
			if (isset($this->request->post[$itemName])) {
				$data[$itemName] = $this->request->post[$itemName];
			}
			else {
				$data[$itemName] = $this->config->get($itemName);
			}
		}

		// loads list of tax classes and geo zones defined in administration
		$this->load->model('localisation/tax_class');
		$data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();
		$this->load->model('localisation/geo_zone');
		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		// loads list of defined order statuses
		$this->load->model('localisation/order_status');
		$data['eshop_order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		// loads list of installed payment methods
		$this->load->model(self::ROUTING_BASE_PATH);
		$data['payment_methods'] = $this->model_extension_shipping_zasilkovna->getInstalledPaymentMethods();

		$data['extension_version'] = self::VERSION;

		$token = $this->model_setting_setting->getSettingValue('shipping_zasilkovna_cron_token');
		$data['cron_url'] = HTTPS_CATALOG . 'index.php?route=extension/module/zasilkovna/updateCarriers&token=' . $token;

		// creates list of store names for e-shop identifier items
		$data['store_list'] = [];
		$data['store_list'][] = [
			'id' => 0,
			'name' => $this->config->get('config_name'),
			'identifier' => $data['shipping_zasilkovna_eshop_identifier_0']
		];
		foreach ($secondaryStores as $storeProperies) {
			$data['store_list'][] = [
				'id' => $storeProperties['store_id'],
				'name' => $storeProperties['name'],
				'identifier' => $data['shipping_zasilkovna_eshop_identifier_' . $storeProperties['store_id']]
			];
		}

		$data['packet_number_sources'] = [
			[
				'value' => 'order_number',
				'label' => $this->language->get('text_order_number'),
			],
			[
				'value' => 'invoice_number',
				'label' => $this->language->get('text_invoice_number'),
			]
		];

		$data['shipping_zasilkovna_packet_number_source'] = $this->config->get('shipping_zasilkovna_packet_number_source');
	}

	/**
	 * Extension of menu in administration. Adds new item with list of Zasilkovna orders to menu "Sales".
	 * This method is called by "before" event on admin/view/common/column_left/before.
	 *
	 * @param string $route routing path of page
	 * @param array $data template parameters
	 * @param StdClass $template instance of page template
	 * @throws Exception
	 */
	public function adminMenuExtension(&$route, &$data, &$template)
	{
		if (!$this->user->hasPermission('access', self::ROUTING_BASE_PATH)) {
			return;
		}

		// load translations for Zasilkovna to separate language context
		$this->load->language(self::ROUTING_BASE_PATH, 'zasilkovna');

		$subMenus = [
			'menu_orders' => self::ACTION_ORDERS,
			'menu_settings' => '',
			'menu_carriers' => self::ACTION_CARRIERS,
		];
		$childrenMenus = [];
		foreach ($subMenus as $translationKey => $action) {
			$childrenMenus[] = [
				'name' => $this->language->get('zasilkovna')->get($translationKey),
				'href' => $this->createAdminLink($action),
			];
		}
		$data['menus'][] = [
			'id' => 'menu-packeta',
			'icon' => 'fa-dropbox',
			'name' => $this->language->get('zasilkovna')->get('menu_title'),
			'children' => $childrenMenus,
		];

	}

	/**
	 * Handler for list of "Zasilkovna" orders.
	 *
	 * @throws Exception
	 */
	public function orders() {
		$this->document->addStyle('view/stylesheet/zasilkovna.css');

		$this->load->language(self::ROUTING_BASE_PATH);
		$this->load->model(self::ROUTING_ORDERS);

		// initialization of page data including setup of list parameters (filters, sorting, paging)
		$data = $this->initPageData(self::ACTION_ORDERS, self::TEXT_TTILE_ORDERS);
		$paramData = $this->model_extension_shipping_zasilkovna_orders->getUrlParameters();

		// load list of order statuses and creation of array for translate ID to status description
		$this->load->model('localisation/order_status');
		$orderStatusList = $this->model_localisation_order_status->getOrderStatuses();
		$data['order_statuses'] = $orderStatusList;
		$orderStatusDescriptions = [];
		foreach ($orderStatusList as $orderStatusItem) {
			$orderStatusDescriptions[$orderStatusItem['order_status_id']] = $orderStatusItem['name'];
		}

		// load list of payment methods considered as "cash on delivery"
		$codPaymentMethods = (array)$this->config->get('shipping_zasilkovna_cash_on_delivery_methods');

		// load count of orders and list of orders for current page
		$orderCount = $this->model_extension_shipping_zasilkovna_orders->getOrdersCount($paramData['filterData']);
		$dbOrderList = $this->model_extension_shipping_zasilkovna_orders->getOrders($paramData);

		// format list of orders for template
		foreach ($dbOrderList as $order) {
			$data['orders'][] = [
				'order_id' => $order['order_id'],
				'customer' => $order['customer'],
				'order_status' => isset($orderStatusDescriptions[$order['order_status_id']]) ? $orderStatusDescriptions[$order['order_status_id']] : '',
				'total' => $this->currency->format($order['total'], $order['currency_code'], $order['currency_value']),
				'weight' => sprintf('%g', $order['total_weight']),
				'is_cod' => in_array($order['payment_code'], $codPaymentMethods),
				'date_added' => date($this->language->get('date_format_short'), strtotime($order['date_added'])),
				'branch_id' => $order['branch_id'],
				'branch_name' => $order['branch_name'],
				'exported' => !empty($order['exported']) ? date($this->language->get('date_format_short'), strtotime($order['exported'])) :	''
			];
		}

		// to keep selected rows as selected
		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array)$this->request->post['selected'];
		} else {
			$data['selected'] = array();
		}

		// creation set of links for CSV export actions
		$csvExportUrlParams = $paramData['filterData'];
		$csvExportUrlParams['sort'] = $paramData['sort'];
		$csvExportUrlParams['order'] = $paramData['order'];
		$data[self::TEMPLATE_LINK_EXPORT_SELECTED] = $this->createAdminLink(self::ACTION_ORDERS_EXPORT,
			array_merge($csvExportUrlParams, ['scope' => 'selected']));
		$data[self::TEMPLATE_LINK_EXPORT_ALL] = $this->createAdminLink(self::ACTION_ORDERS_EXPORT,
			array_merge($csvExportUrlParams, ['scope' => 'all']));
		$data[self::TEMPLATE_LINK_UPDATE] = $this->createAdminLink(self::ACTION_ORDERS_UPDATE,
			array_merge($csvExportUrlParams, ['scope' => 'all']));

		// creation set of links to change grid sorting
		$sortingUrlParams = [];
		foreach ($paramData['filterData'] as $paramName => $paramValue) {
			if (!empty($paramValue)) {
				$sortingUrlParams[$paramName] = $paramValue;
			}
		}
		$sortingUrlParams['page'] = $paramData['page'];
		$sortingUrlParams['order'] = ($paramData['order'] == 'ASC') ? 'DESC' : 'ASC';

		$data['link_sorting_order_id'] = $this->createAdminLink(self::ACTION_ORDERS, array_merge($sortingUrlParams, ['sort' => 'o.order_id']));
		$data['link_sorting_customer'] = $this->createAdminLink(self::ACTION_ORDERS, array_merge($sortingUrlParams, ['sort' => 'customer']));
		$data['link_sorting_order_status_id'] = $this->createAdminLink(self::ACTION_ORDERS, array_merge($sortingUrlParams, ['sort' => 'order_status_id']));
		$data['link_sorting_order_total'] = $this->createAdminLink(self::ACTION_ORDERS, array_merge($sortingUrlParams, ['sort' => 'o.total']));
		$data['link_sorting_order_weight'] = $this->createAdminLink(self::ACTION_ORDERS, array_merge($sortingUrlParams, ['sort' => 'o.weight']));
		$data['link_sorting_order_date'] = $this->createAdminLink(self::ACTION_ORDERS, array_merge($sortingUrlParams, ['sort' => 'date_added']));
		$data['link_sorting_branch_name'] = $this->createAdminLink(self::ACTION_ORDERS, array_merge($sortingUrlParams, ['sort' => 'oz.branch_name']));
		$data['link_sorting_exported'] = $this->createAdminLink(self::ACTION_ORDERS, array_merge($sortingUrlParams, ['sort' => 'exported']));

		// current sorting properties
		$data['sort'] = $paramData['sort'];
		$data['order'] = $paramData['order'];

		// preparation of paging (switch between pages)
		$pagingUrlParameters = [];
		foreach ($paramData['filterData'] as $paramName => $paramValue) {
			if (!empty($paramValue)) {
				$pagingUrlParameters[$paramName] = $paramValue;
			}
		}
		$pagingUrlParameters['sort'] = $paramData['sort'];
		$pagingUrlParameters['order'] = $paramData['order'];
		$pagingUrlParameters['page'] = '{page}';

		$pageNumber = $paramData['page'];
		$pagination = new Pagination();
		$pagination->total = $orderCount;
		$pagination->page = $pageNumber;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->createAdminLink(self::ACTION_ORDERS, $pagingUrlParameters);
		$data['pagination'] = $pagination->render();

		// preparation of paging (current page info)
		// string template: Showing %d to %d of %d (%d Pages)
		$data['results'] = sprintf($this->language->get('text_pagination'),
			($orderCount) ? (($pageNumber - 1) * $this->config->get('config_limit_admin')) + 1 : 0,
			((($pageNumber - 1) * $this->config->get('config_limit_admin')) > ($orderCount - $this->config->get('config_limit_admin'))) ? $orderCount : ((($pageNumber - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')),
			$orderCount,
			ceil($orderCount / $this->config->get('config_limit_admin')));

		// creation set of variables for default value of filters
		foreach ($paramData['filterData'] as $paramName => $paramValue) {
			$data[$paramName] = $paramValue;
		}

		// items of selectbox for type of export
		$data['export_types'] = [
			[ 'value' => 'not_exported', 'name' => $this->language->get('entry_ol_not_exported')],
			[ 'value' => 'exported', 'name' => $this->language->get('entry_ol_exported')],
			[ 'value' => 'all', 'name' => $this->language->get('entry_ol_all_records')]
		];

		// add user token parameter for functionality of JS request (e.g. customer name autocomplete)
		$data['user_token'] = $this->session->data['user_token'];

		$this->response->setOutput($this->load->view('extension/shipping/zasilkovna_orders', $data));
	}

	/**
	 * Handler for Packetery carriers list
	 */
	public function carriers()
	{
		$data = $this->initPageData(self::ACTION_CARRIERS, 'text_carriers');
		$data[self::TEMPLATE_LINK_BACK] = $this->createAdminLink('');

		$filter = $this->request->get;
		$filter = $this->carrierRepository->setDefaultOrdering($filter);

		$columnTypes = [
			'name' => 'text',
			'country' => 'text',
			'currency' => 'text',
			'max_weight' => 'number',
			'is_pickup_points' => 'bool',
			'has_carrier_direct_label' => 'bool',
			'customs_declarations' => 'bool',
			'enabled' => 'bool',
		];
		foreach ($this->carrierRepository->viewColumns as $column) {
			$class = ($column === $filter['orderColumn'] ? strtolower($filter['direction']) : '');
			$sortLinkFilter = $filter;
			$sortLinkFilter['orderColumn'] = $column;
			$sortLinkFilter['direction'] = ($class === 'asc' ? 'DESC' : 'ASC');
			$data['columns'][$column] = [
				'name' => $column,
				'translation' => $this->language->get('column_carrier_' . $column),
				'class' => $class,
				'sortLink' => $this->createAdminLink(self::ACTION_CARRIERS, $sortLinkFilter),
				'type' => $columnTypes[$column],
			];
		}
		$data['columns']['action'] = [
			'name' => 'action',
			'translation' => $this->language->get('column_action'),
			'class' => '',
			'sortLink' => '',
			'type' => 'action',
		];

		$data['user_token'] = $this->session->data['user_token'];
		$carriers = $this->carrierRepository->getFilteredSorted($filter);
		foreach ($carriers as &$carrier) {
			$carrier[self::TEMPLATE_LINK_EDIT] = $this->createAdminLink(
				self::ACTION_CARRIERS_DETAIL,
				[self::PARAM_CARRIER_RECORD_ID => $carrier['id_record']]
			);
		}
		unset($carrier);
		$data['carriers'] = $carriers;
		$data['filter'] = $filter;

		$this->response->setOutput($this->load->view('extension/shipping/zasilkovna_carriers', $data));
	}

	/**
	 * Handler for carrier detail.
	 */
	public function carriers_detail(): void
	{
		if (!isset($this->request->get[self::PARAM_CARRIER_RECORD_ID])) {
			$this->load->language(self::ROUTING_BASE_PATH);
			$this->session->data[self::TEMPLATE_MESSAGE_ERROR] = $this->language->get('error_missing_param');
			$this->response->redirect($this->createAdminLink(self::ACTION_CARRIERS));
		}

		$carrierRecordId = (int)$this->request->get[self::PARAM_CARRIER_RECORD_ID];
		$data = $this->initPageData(
			self::ACTION_CARRIERS_DETAIL,
			'text_carrier_detail',
			[self::PARAM_CARRIER_RECORD_ID => $carrierRecordId]
		);

		$carrier = $this->carrierRepository->findByIdRecord($carrierRecordId);
		if ($carrier === null) {
			$this->session->data[self::TEMPLATE_MESSAGE_ERROR] = $this->language->get('error_missing_param');
			$this->response->redirect($this->createAdminLink(self::ACTION_CARRIERS));
		}

		if (($this->request->server['REQUEST_METHOD'] === 'POST') && $this->checkPermissions()) {
			$postRateType = new RateType($this->request->post['rate_type']);
			$postWeightLimits = (isset($this->request->post['weight_limits']) ? (array)$this->request->post['weight_limits'] : []);
			$postTotalPriceLimits = (isset($this->request->post['total_price_limits']) ? (array)$this->request->post['total_price_limits'] : []);
			$validationReport = $this->carrierRuleFormService->validateCarrierRuleLimits($postRateType, $postWeightLimits, $postTotalPriceLimits);
			if (!$validationReport->isValid()) {
				$data[self::TEMPLATE_MESSAGE_ERROR] = $this->language->get($validationReport->getErrorTranslationKey() ?? 'error_sr_rule_validation_failed');
			} else {
				$this->shippingRuleRepository->saveByCarrierId($carrierRecordId, $this->request->post);
				$this->session->data[self::TEMPLATE_MESSAGE_SUCCESS] = $this->language->get('text_success');
				$this->response->redirect(
					$this->createAdminLink(self::ACTION_CARRIERS_DETAIL, [self::PARAM_CARRIER_RECORD_ID => $carrierRecordId])
				);
			}
		}

		$shippingRule = $this->shippingRuleRepository->findByCarrierId($carrierRecordId);
		$isEnabled = ($shippingRule === null ? 0 : (int)$shippingRule->getIsEnabled());
		$rateType = ($shippingRule === null ? new RateType(RateType::DEFAULT_PRICE) : $shippingRule->getRateType());
		$defaultPrice = ($shippingRule === null ? '' : $this->carrierRuleFormService->formatPriceForForm($shippingRule->getDefaultPrice()));
		$freeShippingLimit = ($shippingRule === null ? '' : $this->carrierRuleFormService->formatPriceForForm($shippingRule->getFreeShippingLimit()));
		$weightLimits = [];
		$totalPriceLimits = [];
		if ($shippingRule !== null) {
			$ruleCollections = $this->carrierRuleFormService->formatCarrierRuleLimitsForForm($rateType, $shippingRule->getLimits());
			$weightLimits = $ruleCollections->getWeightLimits();
			$totalPriceLimits = $ruleCollections->getTotalPriceLimits();
		}
		$carrierFormName = ($shippingRule === null ? '' : (string)$shippingRule->getCarrierName());

		if ($this->request->server['REQUEST_METHOD'] === 'POST') {
			$carrierFormName = (string)$this->request->post['carrier_name'];
			$isEnabled = (int)$this->request->post['is_enabled'];
			$rateType = new RateType($this->request->post['rate_type']);
			$defaultPrice = $this->request->post['default_price'];
			$freeShippingLimit = $this->request->post['free_shipping_limit'];
			$weightLimits = (isset($this->request->post['weight_limits']) ? (array)$this->request->post['weight_limits'] : []);
			$totalPriceLimits = (isset($this->request->post['total_price_limits']) ? (array)$this->request->post['total_price_limits'] : []);
		}
		$weightLimits = $this->carrierRuleFormService->ensureAtLeastOneCarrierRuleLimitForForm($rateType, new RateType(RateType::WEIGHT_BASED), $weightLimits);
		$totalPriceLimits = $this->carrierRuleFormService->ensureAtLeastOneCarrierRuleLimitForForm($rateType, new RateType(RateType::TOTAL_BASED), $totalPriceLimits);

		$data['carrier_form_name'] = $carrier->getName();
		$data['carrier_name'] = $carrierFormName;
		$data['carrier_name_placeholder'] = $carrier->getName();
		$data['is_enabled'] = $isEnabled;
		$data['rate_type'] = $rateType->getValue();
		$data['rate_type_default_price'] = RateType::DEFAULT_PRICE;
		$data['rate_type_weight_based'] = RateType::WEIGHT_BASED;
		$data['rate_type_total_based'] = RateType::TOTAL_BASED;
		$data['rate_types'] = [
			['code' => RateType::DEFAULT_PRICE, 'label' => $this->language->get('text_sr_rate_type_default_price')],
			['code' => RateType::WEIGHT_BASED, 'label' => $this->language->get('text_sr_rate_type_weight_based_rate')],
			['code' => RateType::TOTAL_BASED, 'label' => $this->language->get('text_sr_rate_type_total_based_rate')],
		];
		$data['default_price'] = $defaultPrice;
		$data['free_shipping_limit'] = $freeShippingLimit;
		$data['weight_limits'] = $weightLimits;
		$data['total_price_limits'] = $totalPriceLimits;
		$data[self::TEMPLATE_LINK_FORM_ACTION] = $this->createAdminLink(
			self::ACTION_CARRIERS_DETAIL,
			[self::PARAM_CARRIER_RECORD_ID => $carrierRecordId]
		);
		$data[self::TEMPLATE_LINK_CANCEL] = $this->createAdminLink(self::ACTION_CARRIERS);

		$this->response->setOutput($this->load->view('extension/shipping/zasilkovna_carrier_detail', $data));
	}

	/**
	 * Handler for export orders to CSV (all or selected orders).
	 *
	 * @throws Exception
	 */
	public function orders_export() { // method name with underscore is required for correct routing
		$this->load->language(self::ROUTING_BASE_PATH);
		$this->load->model(self::ROUTING_ORDERS);

		$paramData = $this->model_extension_shipping_zasilkovna_orders->getUrlParameters();
		$exportScope = $this->request->get['scope'];
		// all parameters except list of "row" checkboxes are part of form action (get parameters)
		$orderIdList = (isset($this->request->post['selected'])) ? $this->request->post['selected']: [];

		$this->load->model('setting/store');
		$csvRawData = $this->model_extension_shipping_zasilkovna_orders->getCsvExportData($paramData, $exportScope, $orderIdList);

		// open stdout as file and put content to it using native function for writing data in CSV format
		$fileHandle = fopen('php://output', 'wb');
		ob_start();
		// first two lines are fixed header of file
        $separator = ",";
        $enclosure = "\"";
        $escape = "\\";
		fputcsv($fileHandle, ['version 8'], $separator, $enclosure, $escape);
		fputcsv($fileHandle, [], $separator, $enclosure, $escape);

		foreach ($csvRawData as $rawRecord) {
			fputcsv($fileHandle, $rawRecord, $separator, $enclosure, $escape);
		}
		$csvFileContent = ob_get_contents();
		ob_end_clean();

		// set http headers for "force download" and file type
		$this->response->addHeader('Content-Type: text/csv');
		$fileName = 'orders-' . date('Y-m-d-H-i-s') . '.csv';
		$this->response->addHeader('Content-Disposition: attachment; filename="' . $fileName . '"');

		// send content of csv file as output
		$this->response->setOutput($csvFileContent);
	}

	/**
	 * Handler for orders edit.
	 */
	public function orders_update() {
		$this->load->language(self::ROUTING_BASE_PATH);
		$this->load->model(self::ROUTING_ORDERS);

		$weights = (isset($this->request->post['weight']) ? $this->request->post['weight'] : []);

		foreach ($weights as $orderId => $weight) {
			$this->model_extension_shipping_zasilkovna_orders->updateOrder($orderId, [
				'weight' => $weight
			]);
		}

		$this->session->data[self::TEMPLATE_MESSAGE_SUCCESS] = $this->language->get('orders_updated');
		$this->response->redirect($this->createAdminLink('orders', $this->getAdminLinkUrlParameters()));
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
		$this->load->language(self::ROUTING_BASE_PATH);
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

		if ($actionName === self::ACTION_CARRIERS_DETAIL) {
			$data['breadcrumbs'][] = [
				'text' => $this->language->get('text_carriers'),
				'href' => $this->createAdminLink(self::ACTION_CARRIERS),
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

	/**
	 * @return array
	 */
	private function removeInvalidKeyFromPostData()
	{
		$postCopy = $this->request->post;

		if (!$this->keyValidator->validateFormat($postCopy['shipping_zasilkovna_api_key'])) {
			$postCopy['shipping_zasilkovna_api_key'] = '';
			$this->session->data['api_key_validation_error'] = $this->language->get('error_key_format');
		}

		return $postCopy;
	}

}

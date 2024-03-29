<?php

use Packetery\API\CarriersDownloader;
use Packetery\API\KeyValidator;
use Packetery\Carrier\CarrierRepository;
use Packetery\Carrier\CountryListingPage;
use Packetery\DI\Container;
use Packetery\DI\ContainerFactory;
use Packetery\Exceptions\UpgradeException;
use Packetery\Tools\Tools;
use Packetery\Page\OrderDetailPage;
use Packetery\Carrier\CarrierImporter;
use Packetery\Vendor\Page;
use Packetery\Vendor\VendorFactory;
use Packetery\Vendor\VendorService;

require_once DIR_SYSTEM . 'library/Packetery/deps/autoload.php';

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
    const ROUTING_BASE_PATH = 'extension/shipping/zasilkovna';
    /** @var string routing path for weight rules model */
    const ROUTING_WEIGHT_RULES = 'extension/shipping/zasilkovna_weight_rules';
    /** @var string routing path for shipping rules model */
    const ROUTING_SHIPPING_RULES = 'extension/shipping/zasilkovna_shipping_rules';
    /** @var string routing path for zasilkovna orders model */
    const ROUTING_ORDERS = 'extension/shipping/zasilkovna_orders';
    /** @var string routing path for zasilkovna orders model */
    const ROUTING_COUNTRIES = 'extension/shipping/zasilkovna_countries';
    /** @var string route for zasilkovna orders */
    const ROUTE_PACKETA_ORDERS = 'extension/shipping/zasilkovna/orders';

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

    // set of constant for order list actions
    const ACTION_ORDERS = 'orders';
    const ACTION_ORDERS_EXPORT = 'orders_export';
    const ACTION_ORDERS_UPDATE = 'orders_update';
    const ACTION_ORDER_DETAIL = 'order_detail';

    const ACTION_CARRIER_SETTINGS_COUNTRY = 'carrier_settings_country';
    const ACTION_CARRIER_SETTINGS = 'carrier_settings';
    const ACTION_ADD_VENDOR = 'add_vendor';
    const ACTION_DELETE_VENDOR = 'delete_vendor';

    /** @var string name of url parameter for country code */
    const PARAM_COUNTRY = 'country';
    /** @var string name of url parameter for weight and shipping rule ID */
    const PARAM_RULE_ID = 'rule_id';

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
    const TEXT_TITLE_WEIGHT_RULES = 'heading_weight_rules';
    const TEXT_TITLE_SHIPPING_RULES = 'heading_shipping_rules';
    const TEXT_TTILE_ORDERS = 'heading_orders';
    const CSV_EXPORT_VERSION = 'version 6';
    const PRICING_BY_COUNTRY = 'country';
    const PRICING_BY_CARRIER = 'carrier';

    /** @var KeyValidator */
    private $keyValidator;

    /** @var CarrierRepository */
    private $carrierRepository;

    /** @var Container */
    private $diContainer;

    /**
     * @throws ReflectionException
     */
    public function __construct($registry)
    {
        parent::__construct($registry);

        $this->keyValidator = new KeyValidator();
        $this->diContainer = ContainerFactory::create($registry);
        $this->carrierRepository = $this->diContainer->get(CarrierRepository::class);
    }

    /**
     * Entry point (main method) for plugin installing. Is called after extension is installed.
     *
     * @throws Exception
     */
    public function install() {
        $this->load->model(self::ROUTING_BASE_PATH);
        //TODO: Refactor the module installation, e.g. create an Install class
        $this->model_extension_shipping_zasilkovna->createTablesAndEvents();

        $defaultOrderStatus = $this->config->get('config_order_status_id');

        // prefill default configuration items
        $defaultConfig = [
            'shipping_zasilkovna_version' => Tools::MODULE_VERSION,
            'shipping_zasilkovna_pricing_by' => self::PRICING_BY_COUNTRY,
            'shipping_zasilkovna_weight_max' => '5',
            'shipping_zasilkovna_geo_zone_id' => '',
            'shipping_zasilkovna_packet_number_source' => 'order_number',
            'shipping_zasilkovna_order_statuses' => $defaultOrderStatus ? [$defaultOrderStatus] : [],
            'shipping_zasilkovna_cash_on_delivery_methods' => [],
            'shipping_zasilkovna_cron_token' => Tools::generateCronToken(),
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
     * @throws Exception
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
     * @throws Exception
     */
    private function isVersionMismatch() {
        $schemaVersion = $this->getSchemaVersion();

        return $schemaVersion && version_compare($schemaVersion, Tools::MODULE_VERSION) < 0;
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
     * @throws Exception
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
        $settings['shipping_zasilkovna_version'] = Tools::MODULE_VERSION;
        if (!isset($settings['shipping_zasilkovna_cron_token'])) {
            $settings['shipping_zasilkovna_cron_token'] = Tools::generateCronToken();
        }
        $this->model_setting_setting->editSetting('shipping_zasilkovna', $settings);

        $this->session->data[self::TEMPLATE_MESSAGE_SUCCESS] =
            sprintf($this->language->get('extension_upgraded'), Tools::MODULE_VERSION);
    }

    /**
     * @return bool
     * @throws Exception
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
        $this->document->addStyle('view/stylesheet/zasilkovna.css');
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
                $apiKey = $postCopy['shipping_zasilkovna_api_key'];
                $this->diContainer->register(
                    CarriersDownloader::class,
                    function () use ($apiKey) {
                        return new CarriersDownloader($apiKey);
                    }
                );
                $this->importCarriers();
                $this->response->redirect($this->createAdminLink('marketplace/extension', ['type' => 'shipping']));
            }
        }

        $this->document->addScript('view/javascript/zasilkovna/zasilkovnaBackend.js?v=' . Tools::MODULE_VERSION);

        // full initialization of page
        $data = $this->initPageData('', self::TEXT_TITLE_MAIN);

        $this->setGlobalConfigurationForm($data);

        $this->response->setOutput($this->load->view(self::ROUTING_BASE_PATH, $data));
    }

    /**
     * Handler for showing pricing rules
     * Method name with underscore is required for correct routing
     */
    public function pricing_rules()
    {
        $data = $this->initPageData('pricing_rules', 'text_pricing_rules');
        $data[self::TEMPLATE_LINK_CANCEL] = $this->createAdminLink('');

        // load data for list of weight rules
        $this->load->model(self::ROUTING_WEIGHT_RULES);
        $weightRules = $this->model_extension_shipping_zasilkovna_weight_rules->getAllRules();
        $usedCountries = array_keys($weightRules);

        // load data for list of shipping rules
        $this->load->model(self::ROUTING_SHIPPING_RULES);
        $shippingRules = $this->model_extension_shipping_zasilkovna_shipping_rules->getAllRules();

        $this->load->model(self::ROUTING_COUNTRIES);
        // adding additional data for list of shipping rules
        foreach ($shippingRules as $ruleId => $ruleContent) {
            // name of country
            $shippingRules[$ruleId]['country_name'] = $this->model_extension_shipping_zasilkovna_countries->getCountryNameByIsoCode2($ruleContent['target_country']);

            // print message "not set" if default price or free shipping limit is not set
            if (empty($ruleContent['default_price'])) {
                $shippingRules[$ruleId]['default_price'] = $this->language->get('entry_sr_not_set');
            }
            if (empty($ruleContent['free_over_limit'])) {
                $shippingRules[$ruleId]['free_over_limit'] = $this->language->get('entry_sr_not_set');
            }
            // link to shipping rule editor
            $shippingRules[$ruleId][self::TEMPLATE_LINK_EDIT] = $this->createAdminLink(self::ACTION_SHIPPING_RULES_EDIT,
                [self::PARAM_RULE_ID => $ruleContent['rule_id']]);
            // link to list of weight rules
            $shippingRules[$ruleId]['link_weight_rules'] = $this->createAdminLink(self::ACTION_WEIGHT_RULES,
                [self::PARAM_COUNTRY => $ruleContent['target_country']]);

            if (in_array($ruleContent['target_country'], $usedCountries)) {
                $shippingRules[$ruleId]['weight_rules_description'] = $this->language->get('text_weight_rules_defined');
                $shippingRules[$ruleId]['weight_rules_tooltip'] = $this->language->get('help_weight_rules_change');
            } else {
                $shippingRules[$ruleId]['weight_rules_description'] = $this->language->get('text_weight_rules_missing');
                $shippingRules[$ruleId]['weight_rules_tooltip'] = $this->language->get('help_weight_rules_creation');
            }
        }
        $data['shipping_rules'] = $shippingRules;
        $data['link_shipping_rules'] = $this->createAdminLink(self::ACTION_SHIPPING_RULES);

        // adding additional data for displaying to user in list of weight rules
        foreach ($usedCountries as $countryCode) {
            $weightRules[$countryCode]['country_name'] = $this->model_extension_shipping_zasilkovna_countries->getCountryNameByIsoCode2($countryCode);
            $weightRules[$countryCode][self::TEMPLATE_LINK_EDIT] = $this->createAdminLink(self::ACTION_WEIGHT_RULES, [self::PARAM_COUNTRY => $countryCode]);
        }
        $data['weight_rules'] = $weightRules;

        $this->response->setOutput($this->load->view('extension/shipping/zasilkovna_pricing_rules', $data));
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
            'shipping_zasilkovna_pricing_by',
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

        $data['extension_version'] = Tools::MODULE_VERSION;

        $token = $this->model_setting_setting->getSettingValue('shipping_zasilkovna_cron_token');
        $data['cron_url'] = HTTPS_CATALOG . 'index.php?route=extension/module/zasilkovna/updateCarriers&token=' . $token;

        // creates list of store names for e-shop identifier items
        $data['store_list'] = [];
        $data['store_list'][] = [
            'id' => 0,
            'name' => $this->config->get('config_name'),
            'identifier' => $data['shipping_zasilkovna_eshop_identifier_0']
        ];
        foreach ($secondaryStores as $storeProperties) {
            $data['store_list'][] = [
                'id' => $storeProperties['store_id'],
                'name' => $storeProperties['name'],
                'identifier' => $data['shipping_zasilkovna_eshop_identifier_' . $storeProperties['store_id']]
            ];
        }
        $data['shipping_zasilkovna_pricing_by'] = $this->config->get('shipping_zasilkovna_pricing_by');
        $data['pricing_by_countries_label'] = $this->language->get('pricing_by_countries_label');
        $data['pricing_by_carriers_label'] = $this->language->get('pricing_by_carriers_label');
        $data['pricing_by_countries_value'] = self::PRICING_BY_COUNTRY;
        $data['pricing_by_carriers_value'] = self::PRICING_BY_CARRIER;
        $data['is_pricing_by_countries'] = ($data['shipping_zasilkovna_pricing_by'] === self::PRICING_BY_COUNTRY);

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
     * Handler for show weight rules for given country.
     * @throws Exception
     */
    public function weight_rules() { // method name with underscore is required for correct routing
        $this->checkCountryCode();
        $countryCode = $this->request->get[self::PARAM_COUNTRY];

        $data = $this->initPageData(self::ACTION_WEIGHT_RULES, self::TEXT_TITLE_WEIGHT_RULES, [self::PARAM_COUNTRY => $countryCode]);

        $this->load->model(self::ROUTING_COUNTRIES);
        $this->load->model(self::ROUTING_WEIGHT_RULES);
        $data[self::TEMPLATE_LINK_ADD] = $this->createAdminLink(self::ACTION_WEIGHT_RULES_ADD, [self::PARAM_COUNTRY => $countryCode]);
        $data[self::TEMPLATE_LINK_DELETE] = $this->createAdminLink(self::ACTION_WEIGHT_RULES_DELETE, [self::PARAM_COUNTRY => $countryCode]);
        $data[self::TEMPLATE_LINK_BACK] = $this->createAdminLink('pricing_rules');
        $data['text_country_name'] = $this->model_extension_shipping_zasilkovna_countries->getCountryNameByIsoCode2($countryCode);

        $weightRules = $this->model_extension_shipping_zasilkovna_weight_rules->getRulesForCountry($countryCode);
        foreach ($weightRules as $rule) {
            $data['weight_rules'][] = [
                'rule_id' => $rule['rule_id'],
                'max_weight' => $rule['max_weight'],
                'price' => $rule['price'],
                self::TEMPLATE_LINK_EDIT => $this->createAdminLink(self::ACTION_WEIGHT_RULES_EDIT,
                    [self::PARAM_COUNTRY => $countryCode, self::PARAM_RULE_ID => $rule['rule_id']])
            ];
        }

        $this->response->setOutput($this->load->view('extension/shipping/zasilkovna_weight_rules', $data));
    }

    /**
     * Handler for creation of new weight rule.
     * @throws Exception
     */
    public function weight_rules_add() { // method name with underscore is required for correct routing
        $this->checkCountryCode();
        $countryCode = $this->request->get[self::PARAM_COUNTRY];

        $data = $this->initPageData(self::ACTION_WEIGHT_RULES, self::TEXT_TITLE_WEIGHT_RULES, [self::PARAM_COUNTRY => $countryCode]);

        // check if http method is POST (save of data from form)
        if ($this->request->server['REQUEST_METHOD'] === 'POST') {
            $this->load->model(self::ROUTING_WEIGHT_RULES);
            $errorMessage = $this->model_extension_shipping_zasilkovna_weight_rules->addRule($this->request->post, $countryCode);
            if (empty($errorMessage)) {
                $this->session->data[self::TEMPLATE_MESSAGE_SUCCESS] = $this->language->get('text_success');
                $this->response->redirect($this->createAdminLink(self::ACTION_WEIGHT_RULES, [self::PARAM_COUNTRY => $countryCode]));
            }
            else {
                $data[self::TEMPLATE_MESSAGE_ERROR] = $this->language->get($errorMessage);
            }
        }

        $this->setWeightRuleFormContent($data, $countryCode);
    }

    /**
     * Handler for edit of existing weight rule.
     * @throws Exception
     */
    public function weight_rules_edit() { // method name with underscore is required for correct routing
        $this->checkCountryCode();

        if (!isset($this->request->get['rule_id'])) {
            $this->load->language(self::ROUTING_BASE_PATH);
            $this->session->data[self::TEMPLATE_MESSAGE_ERROR] = $this->language->get('error_missing_param');
            $this->response->redirect($this->createAdminLink(''));
        }

        $countryCode = $this->request->get[self::PARAM_COUNTRY];
        $ruleId = $this->request->get[self::PARAM_RULE_ID];

        $data = $this->initPageData(self::ACTION_WEIGHT_RULES, self::TEXT_TITLE_WEIGHT_RULES, [self::PARAM_COUNTRY => $countryCode]);

        // check if http method is POST (save of data from form)
        if ($this->request->server['REQUEST_METHOD'] === 'POST') {
            $this->load->model(self::ROUTING_WEIGHT_RULES);
            $errorMessage = $this->model_extension_shipping_zasilkovna_weight_rules->editRule($ruleId, $this->request->post, $countryCode);
            if (empty($errorMessage)) {
                $this->session->data[self::TEMPLATE_MESSAGE_SUCCESS] = $this->language->get('text_success');
                $this->response->redirect($this->createAdminLink(self::ACTION_WEIGHT_RULES, [self::PARAM_COUNTRY => $countryCode]));
            }
            else {
                $data[self::TEMPLATE_MESSAGE_ERROR] = $this->language->get($errorMessage);
            }
        }

        $this->setWeightRuleFormContent($data, $countryCode, $ruleId);
    }

    /**
     * Handler for delete of selected weight rules.
     *
     * @throws Exception
     */
    public function weight_rules_delete() { // method name with underscore is required for correct routing
        $this->checkCountryCode();
        $this->load->language(self::ROUTING_BASE_PATH);
        $countryCode = $this->request->get[self::PARAM_COUNTRY];

        if (!empty($this->request->post['selected'])) {
            $this->load->model(self::ROUTING_WEIGHT_RULES);
            $this->model_extension_shipping_zasilkovna_weight_rules->deleteRules($this->request->post['selected']);
        }

        $this->session->data[self::TEMPLATE_MESSAGE_SUCCESS] = $this->language->get('text_success');
        $this->response->redirect($this->createAdminLink(self::ACTION_WEIGHT_RULES, [self::PARAM_COUNTRY => $countryCode]));
    }

    /**
     * Set of form content for weight rule editor. Common part for "add" and "edit" action.
     *
     * @throws Exception
     *
     * @var array $data data for page template
     * @var string $countryCode iso country code of target country
     * @var int $ruleId internal ID of processed rule (0 for adding a new rule)
     */
    private function setWeightRuleFormContent(array $data, $countryCode, $ruleId = 0) {
        $isEdit = ($ruleId !== 0);

        if ($this->request->server['REQUEST_METHOD'] === 'POST') { // load data from POST request
            $postData = $this->request->post;
            $data['max_weight'] = $postData['max_weight'];
            $data['price'] = $postData['price'];
        }
        else if ($isEdit) { // load data from DB
            $this->load->model(self::ROUTING_WEIGHT_RULES);
            $rowData = $this->model_extension_shipping_zasilkovna_weight_rules->getRule($ruleId);
            if (!empty($rowData)) {
                $data['max_weight'] = $rowData['max_weight'];
                $data['price'] = $rowData['price'];
            }
        }

        $data['text_form_title'] = $this->language->get($isEdit ? 'text_edit_weight_rule' : 'text_new_weight_rule');
        if ($isEdit) {
            $data[self::TEMPLATE_LINK_FORM_ACTION] = $this->createAdminLink(self::ACTION_WEIGHT_RULES_EDIT,
                [self::PARAM_COUNTRY => $countryCode, self::PARAM_RULE_ID => $ruleId]);
        }
        else {
            $data[self::TEMPLATE_LINK_FORM_ACTION] = $this->createAdminLink(self::ACTION_WEIGHT_RULES_ADD, [self::PARAM_COUNTRY => $countryCode]);
        }
        $data[self::TEMPLATE_LINK_CANCEL] = $this->createAdminLink(self::ACTION_WEIGHT_RULES, [self::PARAM_COUNTRY => $countryCode]);

        $this->response->setOutput($this->load->view('extension/shipping/zasilkovna_weight_rules_form', $data));
    }

    /**
     * Handler for list of shipping rules for given country.
     * @throws Exception
     */
    public function shipping_rules() { // method name with underscore is required for correct routing
        $data = $this->initPageData(self::ACTION_SHIPPING_RULES, self::TEXT_TITLE_SHIPPING_RULES);

        $this->load->model(self::ROUTING_SHIPPING_RULES);
        $data[self::TEMPLATE_LINK_ADD] = $this->createAdminLink(self::ACTION_SHIPPING_RULES_ADD);
        $data[self::TEMPLATE_LINK_DELETE] = $this->createAdminLink(self::ACTION_SHIPPING_RULES_DELETE);
        $data[self::TEMPLATE_LINK_BACK] = $this->createAdminLink('pricing_rules');

        $shippingRules = $this->model_extension_shipping_zasilkovna_shipping_rules->getAllRules();
        foreach ($shippingRules as $rule) {
            $this->load->model(self::ROUTING_COUNTRIES);
            $data['shipping_rules'][] = [
                'rule_id' => $rule['rule_id'],
                'target_country_name' => $this->model_extension_shipping_zasilkovna_countries->getCountryNameByIsoCode2($rule['target_country']),
                'default_price' => (empty($rule['default_price']) ? $this->language->get('entry_sr_not_set') : $rule['default_price']) ,
                'free_over_limit' => (empty($rule['free_over_limit']) ? $this->language->get('entry_sr_not_set') : $rule['free_over_limit']),
                'is_enabled' => $rule['is_enabled'],
                self::TEMPLATE_LINK_EDIT => $this->createAdminLink(self::ACTION_SHIPPING_RULES_EDIT,
                    [self::PARAM_RULE_ID => $rule['rule_id']])
            ];
        }

        $this->response->setOutput($this->load->view('extension/shipping/zasilkovna_shipping_rules_list', $data));
    }

    /**
     * Handler for creation of new shipping rule.
     * @throws Exception
     */
    public function shipping_rules_add() { // method name with underscore is required for correct routing
        $data = $this->initPageData(self::ACTION_SHIPPING_RULES, self::TEXT_TITLE_SHIPPING_RULES);

        // check if http method is POST (save of data from form)
        if ($this->request->server['REQUEST_METHOD'] === 'POST') {
            $this->load->model(self::ROUTING_SHIPPING_RULES);
            $errorMessage = $this->model_extension_shipping_zasilkovna_shipping_rules->checkRuleData($this->request->post);
            if (empty($errorMessage)) {
                $this->model_extension_shipping_zasilkovna_shipping_rules->addRule($this->request->post);
                $this->session->data[self::TEMPLATE_MESSAGE_SUCCESS] = $this->language->get('text_success');
                $this->response->redirect($this->createAdminLink(self::ACTION_SHIPPING_RULES));
            }
            else {
                $data[self::TEMPLATE_MESSAGE_ERROR] = $this->language->get($errorMessage);
            }
        }

        $this->setShippingRuleFormContent($data);
    }

    /**
     * Handler for edit of existing shipping rule.
     * @throws Exception
     */
    public function shipping_rules_edit() { // method name with underscore is required for correct routing
        if (!isset($this->request->get[self::PARAM_RULE_ID])) {
            $this->load->language(self::ROUTING_BASE_PATH);
            $this->session->data[self::TEMPLATE_MESSAGE_ERROR] = $this->language->get('error_missing_param');
            $this->response->redirect($this->createAdminLink(''));
        }

        $ruleId = $this->request->get[self::PARAM_RULE_ID];
        $data = $this->initPageData(self::ACTION_SHIPPING_RULES, self::TEXT_TITLE_SHIPPING_RULES);

        // check if http method is POST (save of data from form)
        if ($this->request->server['REQUEST_METHOD'] === 'POST') {
            $this->load->model(self::ROUTING_SHIPPING_RULES);
            $errorMessage = $this->model_extension_shipping_zasilkovna_shipping_rules->editRule($ruleId, $this->request->post);
            if (empty($errorMessage)) {
                $this->session->data[self::TEMPLATE_MESSAGE_SUCCESS] = $this->language->get('text_success');
                $this->response->redirect($this->createAdminLink(self::ACTION_SHIPPING_RULES));
            }
            else {
                $data[self::TEMPLATE_MESSAGE_ERROR] = $this->language->get($errorMessage);
            }
        }

        $this->setShippingRuleFormContent($data, $ruleId);
    }

    /**
     * Handler for delete of selected shipping rules.
     *
     * @throws Exception
     */
    public function shipping_rules_delete() { // method name with underscore is required for correct routing
        $this->load->language(self::ROUTING_BASE_PATH);

        if (!empty($this->request->post['selected'])) {
            $this->load->model(self::ROUTING_SHIPPING_RULES);
            $this->model_extension_shipping_zasilkovna_shipping_rules->deleteRules($this->request->post['selected']);
        }

        $this->session->data[self::TEMPLATE_MESSAGE_SUCCESS] = $this->language->get('text_success');
        $this->response->redirect($this->createAdminLink(self::ACTION_SHIPPING_RULES));
    }

    /**
     * Set of form content for shipping rule editor. Common part for "add" and "edit" action.
     *
     * @throws Exception
     *
     * @var array $data data for page template
     * @var int $ruleId internal ID of processed rule (0 for adding a new rule)
     */
    private function setShippingRuleFormContent(array $data, $ruleId = 0) {
        $isEdit = ($ruleId !== 0);

        if ($this->request->server['REQUEST_METHOD'] === 'POST') { // load data from POST request
            $postData = $this->request->post;
            $data['target_country'] = $postData['target_country'];
            $data['default_price'] = $postData['default_price'];
            $data['free_over_limit'] = $postData['free_over_limit'];
            $data['is_enabled'] = $postData['is_enabled'];
        }
        else if ($isEdit) { // load data from DB
            $this->load->model(self::ROUTING_SHIPPING_RULES);
            $rowData = $this->model_extension_shipping_zasilkovna_shipping_rules->getRule($ruleId);
            if (!empty($rowData)) {
                $data['target_country'] = $rowData['target_country'];
                $data['default_price'] = $rowData['default_price'];
                $data['free_over_limit'] = $rowData['free_over_limit'];
                $data['is_enabled'] = $rowData['is_enabled'];
            }
        }

        // creation of localized list of allowed countries
        $countryList = [];
        $this->load->model('localisation/country');
        $countries = $this->model_localisation_country->getCountries();

        foreach ($countries as $country) {
            $countryCode = strtolower($country['iso_code_2']);

            $countryList[] = [
                'code' => $countryCode,
                'name' => $country['name']
            ];
        }
        $data['countries'] = $countryList;

        // set description text and links for form
        $data['text_form_title'] = $this->language->get($isEdit ? 'text_edit_shipping_rule' : 'text_new_shipping_rule');
        if ($isEdit) {
            $data[self::TEMPLATE_LINK_FORM_ACTION] = $this->createAdminLink(self::ACTION_SHIPPING_RULES_EDIT,
                [self::PARAM_RULE_ID => $ruleId]);
        }
        else {
            $data[self::TEMPLATE_LINK_FORM_ACTION] = $this->createAdminLink(self::ACTION_SHIPPING_RULES_ADD);
        }
        $data[self::TEMPLATE_LINK_CANCEL] = $this->createAdminLink(self::ACTION_SHIPPING_RULES);

        $this->response->setOutput($this->load->view('extension/shipping/zasilkovna_shipping_rules_form', $data));
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
            'menu_pricing_rules' => 'pricing_rules',
            'menu_carriers' => 'carriers',
            'menu_carrier_settings' => 'carrier_settings',
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
        $orderStatusesFromSettings = $this->config->get('shipping_zasilkovna_order_statuses');
        $orderStatusList = array_filter(
            $orderStatusList,
            static function($item) use ($orderStatusesFromSettings){
                return in_array($item['order_status_id'], $orderStatusesFromSettings, true);
            }
        );

        $data['order_statuses'] = $orderStatusList;

        // load count of orders and list of orders for current page
        $orderCount = $this->model_extension_shipping_zasilkovna_orders->getOrdersCount($paramData['filterData']);
        $dbOrderList = $this->model_extension_shipping_zasilkovna_orders->getOrders($paramData);

        // format list of orders for template
        foreach ($dbOrderList as $order) {
            $data['orders'][] = [
                'order_id' => $order['order_id'],
                'customer' => $order['customer'],
                'order_status' => $order['order_status_name'],
                'payment_method' => $order['payment_method'],
                'total' => $this->currency->format($order['total'], $order['currency_code'], $order['currency_value']),
                'weight' => sprintf('%g', $order['total_weight']),
                'cod' => $order['cod'] > 0 ? $this->currency->format($order['cod'], $order['currency_code'], $order['currency_value']) : '',
                'date_added' => date($this->language->get('date_format_short'), strtotime($order['date_added'])),
                'branch_id' => $order['branch_id'],
                'branch_name' => $order['branch_name'],
                'exported' => !empty($order['exported']) ? date($this->language->get('date_format_short'), strtotime($order['exported'])) : '',
                'actions' => [
                    'edit' => $this->createAdminLink(self::ACTION_ORDER_DETAIL, ['order_id' => $order['order_id']]),
                    'view' => $this->createAdminLink('sale/order/info', ['order_id' => $order['order_id']]),
                ],
            ];
        }

        $this->load->model(self::ROUTING_BASE_PATH);
        $data['payment_methods'] = $this->model_extension_shipping_zasilkovna->getInstalledPaymentMethods();

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
        $sortingUrlParams['order'] = ($paramData['order'] === 'ASC') ? 'DESC' : 'ASC';

        $data['link_sorting_order_id'] = $this->createAdminLink(self::ACTION_ORDERS, array_merge($sortingUrlParams, ['sort' => 'o.order_id']));
        $data['link_sorting_customer'] = $this->createAdminLink(self::ACTION_ORDERS, array_merge($sortingUrlParams, ['sort' => 'customer']));
        $data['link_sorting_order_status_name'] = $this->createAdminLink(self::ACTION_ORDERS, array_merge($sortingUrlParams, ['sort' => 'order_status_name']));
        $data['link_sorting_order_payment_method'] = $this->createAdminLink(self::ACTION_ORDERS, array_merge($sortingUrlParams, ['sort' => 'o.payment_method']));
        $data['link_sorting_order_total'] = $this->createAdminLink(self::ACTION_ORDERS, array_merge($sortingUrlParams, ['sort' => 'o.total']));
        $data['link_sorting_order_cod'] = $this->createAdminLink(self::ACTION_ORDERS, array_merge($sortingUrlParams, ['sort' => 'cod']));
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
     * Handler for detail of Packeta order.
     * @throws ReflectionException
     */
    public function order_detail()
    {
        /** @var OrderDetailPage $orderDetailPage */
        $orderDetailPage = $this->diContainer->get(OrderDetailPage::class);
        $orderId = (int) $this->request->get['order_id'];
        $this->load->language(self::ROUTING_BASE_PATH);

        if ($this->request->server['REQUEST_METHOD'] === 'POST') {
            if ($orderDetailPage->save($this->request->post)) {
                $this->session->data['flashMessage'] = Tools::flashMessage($this->language->get('order_detail_changes_saved'));
            } else {
                $this->session->data['flashMessage']= Tools::flashMessage(
                    $this->language->get('order_detail_changes_not_saved'),
                    'error_warning'
                );
            }

            $this->response->redirect(
                $this->createAdminLink(
                    self::ACTION_ORDER_DETAIL,
                    ['order_id' => (int) $this->request->post['order_id']]
                )
            );
        }

        $this->document->addScript('https://widget.packeta.com/v6/www/js/library.js');
        $this->document->addScript('/catalog/view/javascript/zasilkovna/shippingExtensionBack.js?v=' . Tools::MODULE_VERSION);
        $data = $this->initPageData(self::ACTION_ORDER_DETAIL, 'text_order_detail', ['order_id' => $orderId]);

        $order = $orderDetailPage->getOrderData($orderId);
        $routeToListLink = $this->createAdminLink(self::ROUTE_PACKETA_ORDERS);

        if (!$order) {
            $this->session->data['error_warning'] = sprintf(
                $this->language->get('order_detail_order_doesnt_exist'),
                $orderId
            );
            $this->response->redirect($routeToListLink);
        }

        $data['order'] = $order;
        $data['link_to_list'] = $routeToListLink;

        $data['widget'] = [
            'api_key' => $this->config->get('shipping_zasilkovna_api_key'),
            'app_identity' => Tools::getAppIdentity(),
            'enabled_country' => strtolower($order['shipping_country_code']),
            'language' => $this->language->get('code'),
        ];

    $this->response->setOutput($this->load->view('extension/shipping/zasilkovna_order_detail', $data));
    }

    /**
     * Handler for Packetery carriers list
     */
    public function carriers()
    {
        $data = $this->initPageData('carriers', 'text_carriers');
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
                'sortLink' => $this->createAdminLink('carriers', $sortLinkFilter),
                'type' => $columnTypes[$column],
            ];
        }

        $data['user_token'] = $this->session->data['user_token'];
        $data['carriers'] = $this->carrierRepository->getFilteredSorted($filter);
        $data['filter'] = $filter;

        $this->response->setOutput($this->load->view('extension/shipping/zasilkovna_carriers', $data));
    }

   /**
     * Handler for Packetery carrier settings
     *
     * @throws \ReflectionException
     */
    public function carrier_settings()
    {
        $this->load->language(self::ROUTING_BASE_PATH);

        /** @var CountryListingPage $countryListingPage */
        $countryListingPage = $this->diContainer->get(CountryListingPage::class);
        $countries = $countryListingPage->getActiveCountries();
        array_walk($countries, function (&$country) {
            $country[self::TEMPLATE_LINK_EDIT] = $this->createAdminLink(self::ACTION_CARRIER_SETTINGS_COUNTRY, [self::PARAM_COUNTRY => $country['code']]);
        });

        $data = $this->initPageData('carrier_settings', 'text_carrier_settings');
        $data['carrier_settings_column_country'] = $this->language->get('carrier_settings_country');
        $data['carrier_settings_column_country_code'] = $this->language->get('carrier_settings_country_code');
        $data['carrier_settings_column_action'] = $this->language->get('carrier_settings_action');
        $data['countries'] = $countries;
        $data['panel_title'] = $this->language->get('carrier_settings_country_list');
        $data['edit_carriers_tooltip'] = $this->language->get('carrier_settings_edit_tooltip');

        $this->response->setOutput($this->load->view('extension/shipping/zasilkovna_carrier_settings', $data));
    }

    /**
     * Handler for Packetery carrier settings in given country
     *
     * @throws \ReflectionException
     * @throws Exception
     * @return void
     */
    public function carrier_settings_country()
    {
        $this->load->language(self::ROUTING_BASE_PATH);
        $this->load->model('extension/shipping/zasilkovna_countries');

        if (!isset($this->request->get['country'])) {
            $this->session->data['flashMessage'] = Tools::flashMessage(
                $this->language->get('carrier_settings_choose_country'),
                'error_warning');

            $this->response->redirect($this->createAdminLink(self::ACTION_CARRIER_SETTINGS));
        }

        $countryCode = $this->request->get['country'];
        $country = $this->model_extension_shipping_zasilkovna_countries->getCountryByIsoCode2($countryCode);

        if (!$country) {
            $this->session->data['flashMessage'] = Tools::flashMessage(
                sprintf($this->language->get('carrier_settings_country_not_found'), htmlspecialchars($countryCode)),
                'error_warning');

            $this->response->redirect($this->createAdminLink(self::ACTION_CARRIER_SETTINGS));
        }

        $this->redirectIfPacketaDoesntDeliverTo($countryCode, $this->createAdminLink(self::ACTION_CARRIER_SETTINGS), $country['name']);

        $this->document->addScript('view/javascript/zasilkovna/zasilkovnaBackend.js?v=' . Tools::MODULE_VERSION);
        $data = $this->initPageData('carrier_settings', 'text_carrier_settings');
        $data['breadcrumbs'][] = [
            'text' => $country['name'],
            'href' => $this->createAdminLink(self::ACTION_CARRIER_SETTINGS_COUNTRY, [self::PARAM_COUNTRY => $countryCode]),
        ];
        $data['carrier_settings_country_column_name'] = $this->language->get('carrier_settings_country_column_name');
        $data['carrier_settings_country_column_action'] = $this->language->get('carrier_settings_country_column_action');

        /** @var VendorService $vendorService */
        $vendorService = $this->diContainer->get(VendorService::class);
        $vendors = $vendorService->fetchVendorsWithTransportByCountry($countryCode);

        $dataVendors = [];
        foreach ($vendors as $vendor) {
            $linkDelete = $this->createAdminLink(self::ACTION_DELETE_VENDOR, ['id' => $vendor->getId()]);
            $confirmText = sprintf(
                $this->language->get('vendor_delete_confirm'),
                $vendor->getTitle()
            );

            $dataVendors[] = [
                'vendor' => $vendor,
                'actions' => [
                    self::TEMPLATE_LINK_DELETE => $linkDelete,
                    'confirm_text' => $confirmText,
                ]
            ];
        }

        $data['vendors'] = $dataVendors;
        $data['panel_title'] = $this->language->get('carrier_settings_carrier_list');
        $data['country_name'] = $country['name'];
        $data['action_add_vendor'] = $this->createAdminLink(self::ACTION_ADD_VENDOR, [self::PARAM_COUNTRY => $countryCode]);

        $this->response->setOutput($this->load->view('extension/shipping/zasilkovna_carrier_settings_country', $data));
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
        fputcsv($fileHandle, [self::CSV_EXPORT_VERSION]);
        fputcsv($fileHandle, []);

        foreach ($csvRawData as $rawRecord) {
            fputcsv($fileHandle, $rawRecord);
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
     * Check presence and content of url parameter for country.
     * If parameter is missing or invalid, redirect to main setting page is performed.
     *
     * @return void
     */
    private function checkCountryCode() {
        // check if code of target country is part of url
        if (empty($this->request->get[self::PARAM_COUNTRY])) {
            $this->load->language(self::ROUTING_BASE_PATH);
            $this->session->data[self::TEMPLATE_MESSAGE_ERROR] = $this->language->get('error_missing_param');
            $this->response->redirect($this->createAdminLink(''));
        }
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
        $urlParameters['user_token'] = $this->session->data['user_token'];

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

    /**
     * @return void
     * @throws Exception
     * @throws ReflectionException
     */
    public function add_vendor() {
        $this->document->addStyle('view/stylesheet/zasilkovna.css');
        $this->load->language(self::ROUTING_BASE_PATH);
        $this->load->model('extension/shipping/zasilkovna_countries');

        // request validation
        if (!isset($this->request->get[self::PARAM_COUNTRY])) {
            $this->session->data['flashMessage'] = Tools::flashMessage(
                $this->language->get('carrier_settings_choose_country'),
                'error_warning');

            $this->response->redirect($this->createAdminLink(self::ACTION_CARRIER_SETTINGS));
        }

        $countryCode = $this->request->get[self::PARAM_COUNTRY];
        $country = $this->model_extension_shipping_zasilkovna_countries->getCountryByIsoCode2($countryCode);

        if (!$country) {
            $this->session->data['flashMessage'] = Tools::flashMessage(
                sprintf($this->language->get('carrier_settings_country_not_found'), htmlspecialchars($countryCode)),
                'error_warning');

            $this->response->redirect($this->createAdminLink(self::ACTION_CARRIER_SETTINGS));
        }
        $actionBack = $this->createAdminLink(self::ACTION_CARRIER_SETTINGS_COUNTRY, [self::PARAM_COUNTRY => $countryCode]);
        $this->redirectIfPacketaDoesntDeliverTo($countryCode, $actionBack, $country['name']);

        // page data + breadcrumbs
        $data = $this->initPageData(self::ACTION_ADD_VENDOR, 'vendor_add_title', ['country' => $countryCode]);
        $addVendorBreadcrumb = array_pop($data['breadcrumbs']);
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_carrier_settings'),
            'href' => $this->createAdminLink(self::ACTION_CARRIER_SETTINGS),
        ];
        $data['breadcrumbs'][] = [
            'text' => $country['name'],
            'href' => $this->createAdminLink(self::ACTION_CARRIER_SETTINGS_COUNTRY, [self::PARAM_COUNTRY => $countryCode]),
        ];
        $data['breadcrumbs'][] = $addVendorBreadcrumb;

        //template data
        /** @var Page $vendorPage */
        $vendorPage = $this->diContainer->get(Page::class);
        $data['vendors'] = [
            'carriers' => $vendorPage->getUnusedCarriersList($countryCode),
            'packeta' => $vendorPage->getUnusedPacketaVendorsList($countryCode),
            ];

        $data['action'] = self::ACTION_ADD_VENDOR;
        $data['action_back'] = $actionBack;

        $data['country'] = [
            'code' => $countryCode,
            'name' => $country['name'],
        ];

        if (($this->request->server['REQUEST_METHOD'] === 'POST')) {
            $postedData = $this->request->post;
            if (isset($postedData['weight_rules'])) {
                $postedData['weight_rules'] = array_values($vendorPage->removeEmptyWeightRules($postedData['weight_rules']));
            }

            $data['form'] = $postedData;
            $errors = $vendorPage->validate($postedData);

            if (empty($errors)) {
                /** @var VendorService $vendorService */
                $vendorService = $this->diContainer->get(VendorService::class);
                /** @var VendorFactory $vendorFactory */
                $vendorFactory = $this->diContainer->get(VendorFactory::class);
                $vendorData = $vendorService->prepareFormData($postedData);
                $vendor = $vendorFactory->create($vendorData);

                $vendorService->save($vendor);

                $this->session->data['flashMessage'] = Tools::flashMessage($this->language->get('vendor_add_success'));

                $this->response->redirect($this->createAdminLink(self::ACTION_CARRIER_SETTINGS_COUNTRY, [self::PARAM_COUNTRY => $countryCode]));
            }

            $data['flashMessage'] = Tools::flashMessage($this->language->get('vendor_add_form_error'), 'error_warning');
            $data['errors'] = $errors;
        }

        $this->response->setOutput($this->load->view('extension/shipping/zasilkovna_add_vendor', $data));
    }

    /**
     * @param string $countryCode
     * @param string $redirectToLink
     * @param string $countryName
     *
     * @return void
     * @throws ReflectionException
     */
    private function redirectIfPacketaDoesntDeliverTo($countryCode, $redirectToLink, $countryName = '') {
        /** @var CountryListingPage $countryListingPage */
        $countryListingPage = $this->diContainer->get(CountryListingPage::class);

        if (!$countryListingPage->doesPacketaDeliverTo($countryCode)) {
            $this->session->data['flashMessage'] = Tools::flashMessage(
                sprintf($this->language->get('carrier_settings_packeta_doesnt_deliver_to_country'), $countryName),
                'error_warning');

            $this->response->redirect($redirectToLink);
        }
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    private function importCarriers() {
        /** @var CarrierImporter $carrierImporter */
        $carrierImporter = $this->diContainer->get(CarrierImporter::class);

        if ($this->carrierRepository->isCarrierTableEmpty()) {
            $carrierImporter->run();
        }
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function delete_vendor() {
        /** @var VendorService $vendorService */
        $vendorService = $this->diContainer->get(VendorService::class);
        $this->load->language(self::ROUTING_BASE_PATH);
        $vendorId = isset($this->request->get['id']) ? (int)$this->request->get['id'] : null;

        $vendor = null;
        if ($vendorId) {
            $vendor = $vendorService->fetchVendorWithTransportById($vendorId);
        }

        if ($vendor === null) {
            $this->response->redirect($this->createAdminLink('error/not_found'));
        }

        $vendorService->delete($vendor);
        $this->session->data['flashMessage'] = Tools::flashMessage($this->language->get('vendor_delete_success'));

        $this->response->redirect(
            $this->createAdminLink(self::ACTION_CARRIER_SETTINGS_COUNTRY, [self::PARAM_COUNTRY => $vendor->getTransport()->getCountry()])
        );
    }
}

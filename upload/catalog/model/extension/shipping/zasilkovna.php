<?php

use Packetery\Checkout\Address;
use Packetery\Checkout\Validator\ValidatorFactory;
use Packetery\DAL\Entity\Carrier;
use Packetery\DAL\Entity\Packeta;
use Packetery\DAL\Entity\Vendor;
use Packetery\Vendor\VendorService;
use Packetery\Tools\Tools;

require_once DIR_SYSTEM . 'library/Packetery/deps/autoload.php';

/**
 * Class ModelExtensionShippingZasilkovna
 *
 * @property Config $config
 * @property DB $db
 * @property Loader $load
 * @property Language $language
 * @property Request $request
 * @property Session $session
 * @property \Cart\Cart $cart
 * @property \Cart\Currency $currency
 * @property \Cart\Tax $tax
 * @property \Packetery\DI\Container $diContainer
 */
class ModelExtensionShippingZasilkovna extends Model {
    /** @var string internal ID of country */
    const KEY_COUNTRY_ID = 'country_id';
    /** @var string internal ID of branch */
    const KEY_SHIPPING_METHOD = 'zasilkovna_shipping_method';
    const KEY_BRANCH_ID = 'zasilkovna_branch_id';
    /** @var string descriptive name for save to additional order data */
    const KEY_BRANCH_NAME = 'zasilkovna_branch_name';
    /** @var string carrier id of selected pickup point */
    const KEY_CARRIER_ID = 'zasilkovna_carrier_id';
    /** @var string selected carrier pickup point */
    const KEY_CARRIER_PICKUP_POINT = 'zasilkovna_carrier_pickup_point';
    /** @var string descriptive name for display to customer */
    const KEY_BRANCH_DESCRIPTION = 'zasilkovna_branch_description';
    /** @var string name of table with shipping rules for country */
    const TABLE_SHIPPING_RULES = DB_PREFIX . 'zasilkovna_shipping_rules';
    /** @var string name of table with content of geo zones */
    const TABLE_WEIGHT_RULES = DB_PREFIX . 'zasilkovna_weight_rules';
    /** @var string name of table with zasilkovna data for orders */
    const TABLE_ZASILKOVNA_ORDERS = DB_PREFIX . 'zasilkovna_orders';
    /** @var string name of oc system order table */
    const TABLE_BASE_ORDER = DB_PREFIX . 'order';

    /** @var string code used as iso code of "other countries" */
    const OTHER_COUNTRIES_CODE = 'other';

    /** @var string name of DB column for free shipping limit */
    const COLUMN_FREE_OVER_LIMIT = 'free_over_limit';
    /** @var string name of DB column for shipping price */
    const COLUMN_PRICE = 'price';
    /** @var string name of DB column for default shipping price */
    const COLUMN_DEFAULT_PRICE = 'default_price';

    /** @var int special value which means "unable to calculate price" */
    const PRICE_UNKNOWN = -1;
    /** @var string name of parameter for shipping price */
    const PARAM_PRICE = 'price';
    /** @var string name of parameter for service name */
    const PARAM_SERVICE_NAME = 'service_name';

    /** @var \Packetery\DI\Container  */
    private $diContainer;

    /** @var VendorService */
    private $vendorService;

    /**
     * Calculation of shipping price. Returns price of shipping or -1 if price cannot be calculated.
     *
     * @param $registry
     * @throws ReflectionException
     */

    public function __construct($registry) {
        parent::__construct($registry);

        $this->diContainer = \Packetery\DI\ContainerFactory::create($registry);
        $this->vendorService = $this->diContainer->get(VendorService::class);

    }
    private function calculatePrice($countryCode, $totalWeight, $totalPrice) {
        // get properties of shipping for target country
        $sqlQueryCountry = sprintf('SELECT * FROM `%s` WHERE `target_country` = "%s" AND `is_enabled` = 1;', self::TABLE_SHIPPING_RULES,
            $this->db->escape($countryCode));
        /** @var StdClass $sqlResult */
        $sqlResult = $this->db->query($sqlQueryCountry);
        if ($sqlResult->num_rows > 0) { // found record for target country
            $countryRow = $sqlResult->row;
            $countryExist = true;
        }
        else { // search for record for "other countries"
            $countryExist = false;
            $sqlQueryOtherCountries = sprintf('SELECT * FROM `%s` WHERE `target_country` = "%s" AND `is_enabled` = 1;', self::TABLE_SHIPPING_RULES,
                self::OTHER_COUNTRIES_CODE);
            /** @var StdClass $sqlResult */
            $sqlResult = $this->db->query($sqlQueryOtherCountries);
            if ($sqlResult->num_rows > 0) { // found record for "other countries"
                $countryRow = $sqlResult->row;
            }
        }

        if (isset($countryRow)) {
            if ($countryRow['' . self::COLUMN_FREE_OVER_LIMIT . ''] > 0 && $totalPrice > $countryRow[self::COLUMN_FREE_OVER_LIMIT]) {
                // price of order is over limit for free shipping
                return [
                    self::PARAM_PRICE => 0,
                    self::PARAM_SERVICE_NAME => ($countryExist ? $countryCode : self::OTHER_COUNTRIES_CODE)
                ];
            }

            // search for weight rule for given country
            $sqlWeightRule = sprintf(
                'SELECT * FROM `%s` WHERE `target_country` = "%s" AND `max_weight` >= %s ORDER BY `max_weight`', // TODO: limit 1
                self::TABLE_WEIGHT_RULES, ($countryExist ? $countryCode : self::OTHER_COUNTRIES_CODE), $totalWeight
            );
            /** @var StdClass $sqlResult */
            $sqlResult = $this->db->query($sqlWeightRule);

            if ($sqlResult->num_rows > 0) { // found weight rule
                return [
                    self::PARAM_PRICE => $sqlResult->row[self::COLUMN_PRICE],
                    self::PARAM_SERVICE_NAME => ($countryExist ? $countryCode : self::OTHER_COUNTRIES_CODE)
                ];
            }

            // check if default price for country is defined
            if ($countryRow[self::COLUMN_DEFAULT_PRICE] > 0) {
                return [
                    self::PARAM_PRICE => $countryRow[self::COLUMN_DEFAULT_PRICE],
                    self::PARAM_SERVICE_NAME => ($countryExist ? $countryCode : self::OTHER_COUNTRIES_CODE)
                ];
            }
        }

        // check if price is over global limit for free shipping
        $globalFreeShippingLimit = (float)$this->config->get('shipping_zasilkovna_default_free_shipping_limit');
        if ($globalFreeShippingLimit > 0 && $totalPrice > $globalFreeShippingLimit) {
            return [
                self::PARAM_PRICE => 0,
                self::PARAM_SERVICE_NAME => 'any'
            ];
        }

        // check if global price for shipping is defined
        $globalShippingPrice = (float)$this->config->get('shipping_zasilkovna_default_shipping_price');
        if ($globalShippingPrice > 0) {
            return [
                self::PARAM_PRICE => $globalShippingPrice,
                self::PARAM_SERVICE_NAME => 'any'
            ];
        }

        // price cannot be calculated
        return [
            self::PARAM_PRICE => self::PRICE_UNKNOWN,
            self::PARAM_SERVICE_NAME => ''
        ];
    }

    /**
     * Method copied from \ModelLocalisationWeightClass because it changed signature/location multiple times while having same function.
     *
     * @param string $unit
     * @return array
     */
    public function getWeightClassDescriptionByUnit($unit) {
        //TODO: Refactor - je nutné brát v uvahu language_id ?
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "weight_class_description` WHERE `unit` = '" . $this->db->escape($unit) . "' AND `language_id` = '" . (int)$this->config->get('config_language_id') . "'");

        return $query->row;
    }

    /**
     * Gets cart weight in kilograms.
     *
     * @return float
     */
    private function getCartWeightKg()
    {
        $weightClassRow = $this->getWeightClassDescriptionByUnit('kg');
        if ($weightClassRow === []) {
            //TODO: upozornit uživatele, že modul má nastavené jednotky, které se nedají přepočítat na kg.
            return 0.0;
        }

        return (float) $this->weight->convert($this->cart->getWeight(), $this->config->get('config_weight_class_id'), $weightClassRow['weight_class_id']);
    }

    /**
     * Returns parameters of available options for shipping.
     * It is called from ControllerCheckoutShippingMethod for all registered shipping extensions.
     *
     * @param array $targetAddress
     * @return array
     * @throws ReflectionException
     */
    public function getQuote($targetAddress) {

        $deliveryAddress = new Address($targetAddress);
        $settingPricingBy =  $this->config->get('shipping_zasilkovna_pricing_by');  //country or carrier
        /** @var ValidatorFactory $checkoutValidatorFactory */
        $checkoutValidatorFactory = $this->diContainer->get(ValidatorFactory::class);
        $checkoutValidator = $checkoutValidatorFactory->create();
        $cartTotalWeight = $this->getCartWeightKg();
        if (!$checkoutValidator->validate($deliveryAddress, $cartTotalWeight)) {
            return [];
        }

        $this->load->language('extension/shipping/zasilkovna');
        $cartCountryCode = $deliveryAddress->getCountryIsoCode2();
        $cartTotalPrice = $this->cart->getTotal();
        $widgetConfig = $this->getWidgetConfig();

        //TODO: do $widgetParams předávat parametr 'weight' - hmotnost košíku
        if ($settingPricingBy === 'country') {
            $checkConditionsByCountry = $this->checkConditionsByCountry($cartTotalWeight, $cartCountryCode);
            if (!$checkConditionsByCountry) {
                return [];
            }
            // calculate price of shipping (only one item can be displayed)
            $calcResult = $this->calculatePrice($cartCountryCode, $cartTotalWeight, $cartTotalPrice);
            $shippingPrice = $calcResult[self::PARAM_PRICE];
            if (self::PRICE_UNKNOWN == $shippingPrice) {
                return [];
            }

            $widgetParams = [
                'country' => $cartCountryCode,
            ];

            $serviceCodeName = $calcResult[self::PARAM_SERVICE_NAME];
            $quoteData[$serviceCodeName] = $this->makeShippingItem($serviceCodeName, $this->language->get('shipping'), $shippingPrice, $widgetParams);
            $widgetConfig['use_vendors'] = false;
        } else {
            $widgetConfig['use_vendors'] = true;
            $quoteData = [];

            /** @var VendorService $vendorService */
            $vendorService = $this->diContainer->get(VendorService::class);
            $vendors = $vendorService->fetchVendorsWithTransportByCountry($deliveryAddress->getCountryIsoCode2(), true);
            foreach($vendors as $vendor) {
                $widgetParams = [];

                if (!$vendor->isHomeDelivery()) {
                    $transport = $vendor->getTransport();
                    if ($transport instanceof Carrier) {
                        $widgetParams = [
                            'carrier_id' => $transport->getId(),
                        ];
                    }

                    if ($transport instanceof Packeta) {
                        $widgetParams = [
                            'country' => $transport->getCountry(),
                            'group' => $transport->getGroup()
                        ];
                    }
                }

                $code = 'vendor-' . $vendor->getId();
                $title = $vendor->getTitle();
                $cost = $this->calculatePriceByVendor($vendor, $cartTotalWeight, $cartTotalPrice);
                if ($cost === null) {
                    continue;
                }

                $quoteData[$code] = $this->makeShippingItem($code, $title, $cost, $widgetParams);
            }
        }

        $scriptWidgetConfig = '<span id="packeta-widget-config"' . $this->parametersToDataAttribute($widgetConfig) . '></span>';
        $keys = array_keys($quoteData);
        $firstKey = $keys[0];
        $quoteData[$firstKey]['text'] .= $scriptWidgetConfig;

        return [
            'code' => 'zasilkovna',
            'title' => $this->language->get('text_title'),
            'quote' => $quoteData,
            'sort_order' => $this->config->get('shipping_zasilkovna_sort_order'),
            'error' => false
        ];
    }

    /**
     * @param array $parameters
     * @return string
     */
    private function parametersToDataAttribute(array $parameters)
    {
        $output = '';

        if (empty($parameters)) {
            return $output;
        }

        foreach ($parameters as $param => $value) {
            $output .= sprintf(' data-%s="%s"', $param, htmlspecialchars($value));
        }

        return $output;
    }

    /**
     * Loads properties of selected branch from session.
     *
     * @return array
     */
    public function loadSelectedBranch() {
        $defaults = [
            self::KEY_SHIPPING_METHOD => '',
            self::KEY_BRANCH_ID => '',
            self::KEY_BRANCH_NAME => '',
            self::KEY_BRANCH_DESCRIPTION => '',
            self::KEY_CARRIER_ID => '',
            self::KEY_CARRIER_PICKUP_POINT => '',
        ];

        if (isset($this->session->data[self::KEY_SHIPPING_METHOD])) {
            $defaults[self::KEY_SHIPPING_METHOD] = $this->session->data[self::KEY_SHIPPING_METHOD];
        }

        if (isset($this->session->data[self::KEY_BRANCH_ID])) {
            $defaults[self::KEY_BRANCH_ID] = $this->session->data[self::KEY_BRANCH_ID];
            $defaults[self::KEY_BRANCH_NAME] = $this->session->data[self::KEY_BRANCH_NAME];
            $defaults[self::KEY_BRANCH_DESCRIPTION] = $this->session->data[self::KEY_BRANCH_DESCRIPTION];
        }
        if (isset($this->session->data[self::KEY_CARRIER_ID])) {
            $defaults[self::KEY_CARRIER_ID] = $this->session->data[self::KEY_CARRIER_ID];
        }
        if (isset($this->session->data[self::KEY_CARRIER_PICKUP_POINT])) {
            $defaults[self::KEY_CARRIER_PICKUP_POINT] = $this->session->data[self::KEY_CARRIER_PICKUP_POINT];
        }

        return $defaults;
    }

    /**
     * Save properties of selected branch from session.
     *
     * @return void
     */
    public function saveSelectedBranch() {
        $this->session->data[self::KEY_SHIPPING_METHOD]  = $this->request->post[self::KEY_SHIPPING_METHOD];
        $this->session->data[self::KEY_BRANCH_ID] = $this->request->post[self::KEY_BRANCH_ID];
        $this->session->data[self::KEY_BRANCH_NAME] = $this->request->post[self::KEY_BRANCH_NAME];
        $this->session->data[self::KEY_BRANCH_DESCRIPTION] = $this->request->post[self::KEY_BRANCH_DESCRIPTION];
        $this->session->data[self::KEY_CARRIER_ID] = $this->request->post[self::KEY_CARRIER_ID];
        $this->session->data[self::KEY_CARRIER_PICKUP_POINT] = $this->request->post[self::KEY_CARRIER_PICKUP_POINT];
    }

    public function saveSelectedCountry($cartType)
    {
        // add new cart here 2/3
        switch ($cartType) {
            case 'standard':
                $countryId = $this->request->post[self::KEY_COUNTRY_ID];
                break;
            case 'journal3':
                $countryId = $this->request->post['order_data']['shipping_country_id'];
                break;
        }
        if ($countryId) {
            $this->session->data[self::KEY_COUNTRY_ID] = $countryId;
        }
    }

    /**
     * Save additional order data to DB during "order confirm" of journal3.
     *
     * @return void
     */
    public function journal3SaveOrderData() {
        $isJournal3Confirm = isset($this->request->get['confirm']) && $this->request->get['confirm'] === 'true';
        if (!$isJournal3Confirm) {
            return;
        }

        $this->saveOrderData();
    }

    /**
     * Save additional order data to DB during "order confirm".
     * All required records with order data are created in DB during this step.
     * This method is called by "after" event on catalog/controller/checkout/confirm.
     *
     * @return void
     */
    public function saveOrderData() {
        // check if selected shipping method is stored in session, it should be saved in step 4 of checkout
        if (!isset($this->session->data['shipping_method']['code'])) {
            return;
        }

        // check if shipping name contains word "zasilkovna", format shlould be "zasilkovna.<titleOfMethod>"
        // title of shipping method for given country is set in settings of plugin
        $selectedShipping = $this->session->data['shipping_method']['code'];
        if (strpos($selectedShipping, 'zasilkovna') === false) {
            return;
        }

        // internal ID of order in e-shop
        $orderId = (int) $this->session->data['order_id'];

        // this check is needed because the method is being called by checkout/save/after trigger of OPC journal3
        // and not only once by checkout/confirm/after as usual
        if (!$orderId) {
            return;
        }

        if ($this->config->get('shipping_zasilkovna_pricing_by') === 'country') {
            if (empty($this->session->data[self::KEY_CARRIER_ID])) {
                // internal ID of selected target pick-up point ID
                $branchId = (int) $this->session->data[self::KEY_BRANCH_ID];
                $carrierPickupPoint = null;
                $isCarrier = 0;
            } else {
                $branchId = (int) $this->session->data[self::KEY_CARRIER_ID];
                $carrierPickupPoint = $this->session->data[self::KEY_CARRIER_PICKUP_POINT];
                $isCarrier = 1;
            }

            // name of selected branch (provided by zasilkovna)
            $branchName = $this->session->data[self::KEY_BRANCH_NAME];
        } else {
            $this->load->language('extension/shipping/zasilkovna');
            $vendorId = str_replace('zasilkovna.vendor-', '', $selectedShipping);
            $vendor = $this->vendorService->fetchVendorWithTransportById($vendorId);
            if ($vendor === null) {
                //TODO: Otestovat tuto situaci, že funguje, nejsem si jistý, jestli metodě write můžu předat pole.
                $log = new \Log('packetery.log');
                $log->write($this->loadSelectedBranch());
            }

            $transport = $vendor->getTransport();
            if ($transport instanceof Carrier) {
                $branchId = $transport->getId();
                $branchName = $vendor->getTitle();
                $isCarrier = 1;
                //TODO: na práci s pickup-points uložené v session udělat nějaký object
                $carrierPickupPointFromSession = isset($this->session->data[self::KEY_CARRIER_PICKUP_POINT]) ? $this->session->data[self::KEY_CARRIER_PICKUP_POINT] : '';
                $carrierPickupPoint = $carrierPickupPointFromSession !== '' ? $carrierPickupPointFromSession : 'NULL';
            } else {
                $isCarrier = 0;
                $branchId = $this->session->data[self::KEY_BRANCH_ID];
                $branchName = isset($this->session->data[self::KEY_BRANCH_NAME]) ?
                    $this->session->data[self::KEY_BRANCH_NAME] : $vendor->getTitle();
                $carrierPickupPoint = 'NULL';
            }
        }

        // total weight of all products in cart (including product options which can modify product weight)
        $totalWeight = $this->getCartWeightKg();

        $sql = sprintf('INSERT IGNORE INTO `%szasilkovna_orders` (`order_id`, `branch_id`, `branch_name`, `is_carrier`, `carrier_pickup_point`, `total_weight`) VALUES (%s, %s, "%s", %d, "%s", %s);',
            DB_PREFIX, $orderId, $branchId, $this->db->escape($branchName), $isCarrier, $carrierPickupPoint, $totalWeight);
        $this->db->query($sql);
    }

    /**
     * @param int $orderId
     */
    public function deleteIfOrderNotPacketaShipping($orderId) {
        $this->db->query(sprintf("
			DELETE `zo` FROM `%s` `zo`
			LEFT JOIN `%s` `o` ON `o`.`order_id` = `zo`.`order_id` 
			WHERE `zo`.`exported` IS NULL 
				AND `o`.`shipping_code` NOT LIKE 'zasilkovna%%'
				AND  `zo`.`order_id` = %d",
            self::TABLE_ZASILKOVNA_ORDERS,
            self::TABLE_BASE_ORDER,
            $orderId
        ));
    }

    /**
     * Returns true if max weight rules for given countryCode are satisfied or don't exist at all
     *
     * @param float  $weight
     * @param string $countryCode
     *
     * @return bool
     */
    private function isWeightAllowedInRules($weight, $countryCode) {
        $sqlWeightRule = sprintf(
            'SELECT `max_weight` FROM `%s` WHERE `target_country` = "%s" ORDER BY `max_weight` DESC LIMIT 1',
            self::TABLE_WEIGHT_RULES,
            $this->db->escape($countryCode)
        );

        /** @var StdClass $weightRulesResult */
        $weightRulesResult = $this->db->query($sqlWeightRule);

        if ($weightRulesResult->num_rows === 0) {
            return true;
        }

        return ($weightRulesResult->row['max_weight'] > $weight);
    }

    /*
     * @param float $totalWeight
     * @param string $cartCountryCode
     * @return bool
     */
    public function checkConditionsByCountry($totalWeight, $cartCountryCode) {
        // check if total weight of order is lower than maximal allowed weight (if limit is defined)
        $maxWeight = (int)$this->config->get('shipping_zasilkovna_weight_max');
        if (!empty($maxWeight) && $totalWeight > $maxWeight) {
            return false;
        }

        // check if max weight rule exists and is fulfilled for target country
        if (!$this->isWeightAllowedInRules($totalWeight, $cartCountryCode)) {
            return false;
        }

        return true;
    }

    private function makeShippingItem($code, $title, $cost, $widgetParams)
    {
        $htmlSpan = '';
        if (!empty($widgetParams)) {
            $htmlSpan = '<span class="packeta-vendor-widget-config"' . $this->parametersToDataAttribute($widgetParams) . '></span>';
        }
        $taxValue = $this->tax->calculate($cost, $this->config->get('shipping_zasilkovna_tax_class_id'), $this->config->get('config_tax'));

        return [
            'code' => 'zasilkovna.' . $code,
            'title' => $title,
            'cost' => $cost,
            'tax_class_id' => $this->config->get('shipping_zasilkovna_tax_class_id'),
            'text' => $this->currency->format($taxValue, $this->session->data['currency']) . $htmlSpan,
        ];
    }

    private function getWidgetConfig()
    {
        $language = $this->language->get('code');
        return [
            'api_key' => $this->config->get('shipping_zasilkovna_api_key'),
            'language' => $language,
            'select_branch_text' => $this->language->get('choose_branch'),
            'no_branch_selected_text' => $this->language->get('no_branch_selected'),
            'app_identity' => Tools::getAppIdentity(),
        ];
    }

    /**
     * @param Vendor $vendor
     * @param float $cartTotalWeight
     * @param float $cartTotalPrice
     * @return float|null
     */
    private function calculatePriceByVendor(Vendor $vendor, $cartTotalWeight, $cartTotalPrice) {

        if ($vendor->getFreeShippingLimit()) {
            if ($cartTotalPrice > (int)$vendor->getFreeShippingLimit()) {
                return 0;
            }
        }

        return $this->vendorService->getPriceForVendor($vendor, $cartTotalWeight);
    }
}

<?php

namespace Packetery\Fun;
use Packetery\Vendor\VendorRepository;
use ControllerExtensionShippingZasilkovna;

/**
 * Controller for Vendor
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
class FunController extends \Controller {
    const ROUTING_BASE_PATH = 'extension/shipping/zasilkovnatest';
    /** @var VendorRepository */
    private $vendorRepository;

    /**
     * @param $registry
     * @param VendorRepository $vendorRepository
     */
    public function __construct( $registry, VendorRepository $vendorRepository )
    {
        parent::__construct($registry);
        $this->vendorRepository = $vendorRepository;
        $this->load->language(ControllerExtensionShippingZasilkovna::ROUTING_BASE_PATH);
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
     * @param array $data
     */
    public function test(array $data)
    {
        $data['debugs'] = [];
        $debug['funController'] = $this;
        foreach($debug as $name => $dbg) {
            $data['debugs'][$name] = print_r($dbg, true);
        }
        $data['link'] = $this->createAdminLink('test2');
        $this->response->setOutput($this->load->view('extension/shipping/zasilkovna_test', $data));
    }
}

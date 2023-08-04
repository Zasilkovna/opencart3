<?php

namespace Packetery\Action\Admin;

use Packetery\Carrier\CountryListingPage;
use Packetery\DAL\Entity\Country;
use Packetery\DAL\Entity\Vendor;
use Packetery\Engine\FlashMessage;
use Packetery\Model\Country\CountryService;
use Packetery\Vendor\Page;
use Packetery\Vendor\VendorFactory;
use Packetery\Vendor\VendorGrid;
use Packetery\Vendor\VendorService;

class VendorFacade extends Facade {
    const ACTION_CARRIER_SETTINGS = 'carrier_settings';
    const ACTION_CARRIER_SETTINGS_COUNTRY = 'carrier_settings_country';

    /** @var VendorService */
    private $vendorService;

    /** @var CountryService */
    private $countryService;

    /** @var Page */
    private $vendorPage;

    /** @var VendorFactory */
    private $vendorFactory;

    /** @var CountryListingPage */
    private $countryListingPage;

    /** @var VendorGrid */
    private $vendorGrid;

    /**
     * @param VendorService $vendorService
     * @param VendorFactory $vendorFactory
     * @param Page $vendorPage
     * @param CountryService $countryService
     * @param CountryListingPage $countryListingPage
     * @param VendorGrid $vendorGrid
     */
    public function __construct(
        VendorService $vendorService,
        VendorFactory $vendorFactory,
        Page $vendorPage,
        CountryService $countryService,
        CountryListingPage $countryListingPage,
        VendorGrid $vendorGrid
    ) {
        $this->vendorService = $vendorService;
        $this->vendorFactory = $vendorFactory;
        $this->vendorPage = $vendorPage;
        $this->countryService = $countryService;
        $this->countryListingPage = $countryListingPage;
        $this->vendorGrid = $vendorGrid;
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function add() {
        $this->handleForm();

        $countryCode = $this->getParameter('country');
        $country = $this->getCountryByCode($countryCode);

        $this->initBreadcrumbs($country);
        $this->template->addBreadcrumb($this->translate('vendor_add_title'));

        $this->template->addParameter('id', null);
        $this->template->addParameter('link_form_action', $this->link->createAdminLink(
            'add_vendor',
            ['country' => $countryCode]
        ));
        $this->template->addParameter('panel_title', $country->getName());
        $this->template->addParameter('country', $country);

        $vendors = [
            'carriers' => $this->vendorPage->getUnusedCarriersList($countryCode),
            'packeta' => $this->vendorPage->getUnusedPacketaVendorsList($countryCode),
        ];
        $this->template->addParameter('vendors', $vendors);
        $this->renderForm();
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function edit() {
        $this->handleForm();

        $vendorId = (int)$this->getParameter('id');
        $vendor = $this->getVendor($vendorId);

        $form = [
            'cart_name' => $vendor->getCartName(),
            'free_shipping_limit' => $vendor->getFreeShippingLimit(),
            'is_enabled' => $vendor->isEnabled(),
        ];

        foreach ($vendor->getPrices() as $key => $vendorPrice) {
            $form['weight_rules'][$key]['max_weight'] = $vendorPrice->getMaxWeight();
            $form['weight_rules'][$key]['price'] = $vendorPrice->getPrice();
        }

        $this->template->setTitle(sprintf($this->translate('vendor_edit_title'), $vendor->getTitle()));
        $this->template->addParameter('id', $vendor->getId());
        $this->template->addParameter('panel_title', $vendor->getTitle());
        $this->template->addParameter('link_form_action', $this->link->createAdminLink(
            'edit_vendor',
            ['id' => $this->getParameter('id')]
        ));
        $countryCode = $vendor->getTransport()->getCountry();
        $country = $this->countryService->getByCountryCode($countryCode);
        $this->template->addParameter('country', $country);

        $this->initBreadcrumbs($country);
        $this->template->addBreadcrumb($vendor->getTitle());

        $this->setDefaultFormValues($form); //přesunout nahoru hned za $form = ...
        $this->renderForm();
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function listVendors() {
        // původně jsem chtěl metodu pojmenovat list(), ale to mi nedovoluje PHP5.6.
        // místo listVendors - použít renderList,  renderAdd, renderEdit ...
        $countryCode = $this->getParameter('country');
        $country = $this->getCountryByCode($countryCode);

        $vendors = $this->vendorService->fetchVendorsWithTransportByCountry($countryCode);

        $dataVendors = [];
        foreach ($vendors as $vendor) {
            $confirmText = sprintf(
                $this->translate('vendor_delete_confirm'),
                $vendor->getTitle()
            );

            $dataVendors[] = [
                'vendor' => $vendor,
                'delete_confirm_text' => $confirmText,
            ];
        }

        $this->beforeBreadcrumbs($country);
        $this->template->addParameter('vendors', $dataVendors);
        $this->template->addParameter('panel_title', $this->translate('carrier_settings_carrier_list'));
        $this->template->addParameter('country', $country);
        $this->template->addParameter(
            'carrier_settings_country_column_name',
            $this->translate('carrier_settings_country_column_name')
        );
        $this->template->addParameter(
            'carrier_settings_country_column_action',
            $this->translate('carrier_settings_country_column_action')
        );

        $vendorGrid = $this->vendorGrid->create($dataVendors);
        $this->template->addParameter('vendor_grid', $vendorGrid);

        $this->response->setOutput($this->template->render('Vendor/list_vendor'));
    }

    /**
     * @return void
     */
    public function delete() {
        $vendorId = (int)$this->getParameter('id');
        $vendor = $this->getVendor($vendorId);
        $this->vendorService->delete($vendor);
        $this->flashMessage($this->language->get('vendor_delete_success'), FlashMessage::SUCCESS);

        $this->response->redirect(
            $this->link->createAdminLink(
                self::ACTION_CARRIER_SETTINGS_COUNTRY,
                ['country' => $vendor->getTransport()->getCountry()]
            )
        );
    }

    /**
     * @return void
     * @throws \Exception
     */
    private function renderForm() {
        if ($this->isFormSubmitted() && $this->hasErrors()) {
            $this->flashMessage($this->translate('vendor_form_error'), FlashMessage::DANGER);
            $this->template->addParameter('errors', $this->getErrors());
            $formValues = $this->getFormValues();
            //TODO: form -> formValues
            $this->template->addParameter('form', $formValues);
        }

        $this->response->setOutput($this->template->render('Vendor/vendor'));
    }

    /**
     * @param string|null $id
     * @return Vendor
     */
    private function getVendor($id) {
        $vendorId = (int)$id;

        $vendor = null;
        if ($vendorId) {
            $vendor = $this->vendorService->fetchVendorWithTransportById($vendorId);
        }

        if ($vendor === null) {
            $this->response->redirect($this->link->createAdminLink('error/not_found'));
        }

        return $vendor;
    }

    /**
     * @return void
     * @throws \Exception
     */
    private function processForm() {
        $postedData = $this->getFormValues();


        $message = sprintf($this->translate('vendor_save_success'), $vendor->getTitle());

        $this->flashMessage($message, FlashMessage::SUCCESS);

        $linkSettingsCountry = $this->link->createAdminLink(
            CarrierFacade::ACTION_SETTINGS_COUNTRY,
            ['country' => $vendor->getTransport()->getCountry()]
        );

        $this->response->redirect($linkSettingsCountry);
    }

    /**
     * @param array $postedData
     * @return void
     */
    private function validateForm(array $postedData) {
        if (isset($postedData['weight_rules'])) {
            $postedData['weight_rules'] = array_values(
                $this->vendorPage->removeEmptyWeightRules($postedData['weight_rules'])
            );
        }
        $errors = $this->vendorPage->validate($postedData);
        $this->setErrors($errors);
    }

    /**
     * @param string|null $countryCode
     * @return Country
     */
    private function getCountryByCode($countryCode) {
        if (!$countryCode) {
            $this->flashMessage($this->translate('carrier_settings_choose_country'), FlashMessage::WARNING);
            $this->response->redirect($this->link->createAdminLink(self::ACTION_CARRIER_SETTINGS));
        }

        $country = $this->countryService->getByCountryCode($countryCode);
        if (!$country) {
            $this->flashMessage(sprintf(
                $this->language->get('carrier_settings_country_not_found'),
                htmlspecialchars($countryCode)
            ), 'error_warning');

            $this->response->redirect($this->link->createAdminLink(self::ACTION_CARRIER_SETTINGS));
        }

        if (!$this->countryListingPage->doesPacketaDeliverTo($countryCode)) {
            $this->flashMessage(sprintf(
                $this->language->get('carrier_settings_packeta_doesnt_deliver_to_country'),
                $country->getName()
            ), 'error_warning');

            $link = $this->link->createAdminLink(
                self::ACTION_CARRIER_SETTINGS_COUNTRY,
                ['country' => $countryCode]
            );
            $this->response->redirect($link);
        }

        return $country;
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function handleForm()
    {
        if ($this->isFormSubmitted()) {
            $postedData = $this->getFormValues();
            $this->validateForm($postedData);
            if (!$this->hasErrors()) {
                $this->processForm();
                exit;
            }
        }
    }

    /**
     * @param Country $country
     * @return void
     */
    private function beforeBreadcrumbs(Country $country) {
        $carrierSettingsLink = $this->link->createAdminLink(self::ACTION_CARRIER_SETTINGS);
        $this->template->addBreadcrumb($this->translate('text_carrier_settings'), $carrierSettingsLink);


        $carrierSettingsCountryLink = $this->link->createAdminLink(
            self::ACTION_CARRIER_SETTINGS_COUNTRY,
            ['country' => strtolower($country->getIsoCode2())]
        );
        $this->template->addBreadcrumb($country->getName(), $carrierSettingsCountryLink);
    }

    public function handleForm2()
    {
        $form = $this->vendorFormFactory->create();
        if ($form->isSuccess()) {
          $onSuccessCallback = function (Vendor $vendor) {
              $message = sprintf($this->translate('vendor_save_success'), $vendor->getTitle());

              $this->flashMessage($message, FlashMessage::SUCCESS);

              $linkSettingsCountry = $this->link->createAdminLink(
                  CarrierFacade::ACTION_SETTINGS_COUNTRY,
                  ['country' => $vendor->getTransport()->getCountry()]
              );

              $this->response->redirect($linkSettingsCountry);
          };

          $form->process($onSuccessCallback);

        }

    }


}

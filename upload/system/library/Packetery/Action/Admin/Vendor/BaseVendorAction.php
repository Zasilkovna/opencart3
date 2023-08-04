<?php

namespace Packetery\Action\Admin\Vendor;

use Packetery\Action\Admin\Carrier\BaseCarrierAction;
use Packetery\Carrier\CountryListingPage;
use Packetery\DAL\Entity\Country;
use Packetery\DAL\Entity\Vendor;
use Packetery\Engine\Action\Action;
use Packetery\Engine\Action\IAction;
use Packetery\Engine\FlashMessage;
use Packetery\Model\Country\CountryService;
use Packetery\Vendor\Page;
use Packetery\Vendor\VendorForm;
use Packetery\Vendor\VendorService;

abstract class BaseVendorAction extends Action implements IAction {

    /** @var VendorService */
    protected $vendorService;

    /** @var Page */
    protected $vendorPage;

    /** @var CountryListingPage */
    protected $countryListingPage;

    /** @var CountryService */
    protected $countryService;

    /** @var VendorForm */
    protected $vendorForm;

    /**
     * @param VendorService $vendorService
     * @param Page $vendorPage
     * @param CountryListingPage $countryListingPage
     * @param CountryService $countryService
     * @param VendorForm $vendorForm
     * @return void
     */
    public function injectVendorServices(
        VendorService $vendorService,
        Page $vendorPage,
        CountryListingPage $countryListingPage,
        CountryService $countryService,
        VendorForm $vendorForm
    ) {
        $this->vendorService = $vendorService;
        $this->countryListingPage = $countryListingPage;
        $this->countryService = $countryService;
        $this->vendorPage = $vendorPage;
        $this->vendorForm = $vendorForm;
    }

    /**
     * @return void
     */
    protected function handleForm() {

        if ($this->vendorForm->isSubmitted() && ! $this->vendorForm->isValid()) {
            $this->flashMessage($this->translate('vendor_form_error'), FlashMessage::DANGER);
        }

        if ($this->vendorForm->isSuccess()) {
            $onSuccessCallback = function (Vendor $vendor) {

                $message = sprintf($this->translate('vendor_save_success'), $vendor->getTitle());

                $this->flashMessage($message, FlashMessage::SUCCESS);

                $linkSettingsCountry = $this->link->createAdminLink(
                    BaseCarrierAction::ACTION_CARRIER_SETTINGS_COUNTRY,
                    ['country' => $vendor->getTransport()->getCountry()]
                );

                $this->response->redirect($linkSettingsCountry);
            };

            $this->vendorForm->process($onSuccessCallback);
        }

        $this->template->addParameter('form', $this->vendorForm);
    }

    /**
     * @param string|null $countryCode
     * @return Country
     */
    protected function getCountryByCode($countryCode) {
        if (!$countryCode) {
            $this->flashMessage($this->translate('carrier_settings_choose_country'), FlashMessage::WARNING);
            $this->response->redirect($this->link->createAdminLink(
                BaseCarrierAction::ACTION_CARRIER_SETTINGS
            ));
        }

        $country = $this->countryService->getByCountryCode($countryCode);
        if (!$country) {
            $this->flashMessage(sprintf(
                $this->language->get('carrier_settings_country_not_found'),
                htmlspecialchars($countryCode)
            ), 'error_warning');

            $this->response->redirect($this->link->createAdminLink(BaseCarrierAction::ACTION_CARRIER_SETTINGS));
        }

        if (!$this->countryListingPage->doesPacketaDeliverTo($countryCode)) {
            $this->flashMessage(sprintf(
                $this->language->get('carrier_settings_packeta_doesnt_deliver_to_country'),
                $country->getName()
            ), 'error_warning');

            $link = $this->link->createAdminLink(
                BaseCarrierAction::ACTION_CARRIER_SETTINGS_COUNTRY,
                ['country' => $countryCode]
            );
            $this->response->redirect($link);
        }

        return $country;
    }

    /**
     * @param string|null $id
     * @return Vendor
     */
    protected function getVendor($id) {
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
     * @param Country $country
     * @return void
     */
    protected function initBreadcrumbs(Country $country) {
        $carrierSettingsLink = $this->link->createAdminLink(BaseCarrierAction::ACTION_CARRIER_SETTINGS);
        $this->template->addBreadcrumb($this->translate('text_carrier_settings'), $carrierSettingsLink);


        $carrierSettingsCountryLink = $this->link->createAdminLink(
            BaseCarrierAction::ACTION_CARRIER_SETTINGS_COUNTRY,
            ['country' => strtolower($country->getIsoCode2())]
        );
        $this->template->addBreadcrumb($country->getName(), $carrierSettingsCountryLink);
    }
}

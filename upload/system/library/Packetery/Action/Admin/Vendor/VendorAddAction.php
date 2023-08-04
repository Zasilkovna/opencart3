<?php

namespace Packetery\Action\Admin\Vendor;

use Packetery\Engine\FlashMessage;

class VendorAddAction extends BaseVendorAction {

    /**
     * @return void
     */
    public function handleForm() {
        parent::handleForm();
    }

    /**
     * @param string $country
     * @return void
     * @throws \Exception
     */
    public function render($country) {
        $countryCode = $country;
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

        $this->response->setOutput($this->template->render('Vendor/vendor'));
    }
}

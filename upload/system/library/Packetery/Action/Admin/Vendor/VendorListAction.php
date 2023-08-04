<?php

namespace Packetery\Action\Admin\Vendor;

class VendorListAction extends BaseVendorAction {

    /**
     * @param string $country
     * @return void
     * @throws \Exception
     */
    public function render($country) {
        $countryCode = $country;
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

        $this->initBreadcrumbs($country);
        $this->template->addParameter('vendors', $dataVendors);
        $this->template->addParameter('country', $country);
        $this->template->addParameter('panel_title', $this->translate('carrier_settings_carrier_list'));
        $this->template->addParameter(
            'carrier_settings_country_column_name',
            $this->translate('carrier_settings_country_column_name')
        );
        $this->template->addParameter(
            'carrier_settings_country_column_action',
            $this->translate('carrier_settings_country_column_action')
        );

        //$vendorGrid = $this->vendorGrid->create($dataVendors);
        //$this->template->addParameter('vendor_grid', $vendorGrid);

        $this->response->setOutput($this->template->render('Vendor/list_vendor'));
    }
}

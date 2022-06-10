<?php
class ModelExtensionTotalZasilkovnaCodSurcharge extends Model {

	public function getTotal($total) {

		$this->load->language('extension/shipping/zasilkovna');
		echo 'jazyk';
		echo '<pre>';
		print_r($this->language);
		echo '</pre>';


		$codSurcharge = $this->config->get('shipping_zasilkovna_cod_surcharge');
		$total['totals'][] = array(
			'code'       => 'zasilkovna_cod_surcharge',
			'title'      => $this->language->get('entry_cod_surcharge'),
			'value'      => $codSurcharge,
			'sort_order' => $this->config->get('shipping_zasilkovna_sort_order')
		);

		$total['total'] += (float) $codSurcharge;
	}

}

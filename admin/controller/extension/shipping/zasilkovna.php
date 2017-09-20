<?php
class ControllerExtensionShippingZasilkovna extends Controller {

	public 	$countries = [
		[
			'code' => 'cz',
			'name' => 'Česká republika',
		],
		[
			'code' => 'hu',
			'name' =>'Maďarsko',
		],
		[
			'code' => 'pl',
			'name' =>'Polsko',
		],
		[
			'code' => 'sk',
			'name' => 'Slovenská republika',
		],
		[
			'code' => 'ro',
			'name' => 'Rumunsko',
		],
	];

		private $error 			= array();

	public function index() {
		$this->load->language('extension/shipping/zasilkovna');

		$this->document->setTitle($this->language->get('heading_title'));
		
		$this->load->model('setting/setting');
				
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->validate())) {
			$this->model_setting_setting->editSetting('shipping_zasilkovna', $this->request->post);
			
			$this->session->data['success'] = $this->language->get('text_success');
			
			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true));
		}
		
		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_all_zones'] = $this->language->get('text_all_zones');
		$data['text_none'] = $this->language->get('text_none');
		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');
	
		
		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		$data['tab_general'] = $this->language->get('tab_general');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}
		
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);


		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_shipping'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/shipping/zasilkovna', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/shipping/zasilkovna', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true);
		

		$data['zasilkovna_countries'] = $this->countries;

		if(isset($this->request->post['shipping_zasilkovna'])) {

		} else {
			$shipping_methods = $this->config->get("shipping_zasilkovna") ?: [];
			foreach ($shipping_methods as $item) {
				$data['zasilkovna_rows'][] = [
					'title' => $item['title'],
					'price' => $item['price'],
					'freeover' => $item['freeover'],
					'branches_enabled' => $item['branches_enabled'],
					'enabled' => $item['enabled'],
					'country' => $item['country']
				];
			}
		}

		if (isset($this->request->post['shipping_zasilkovna_api_key'])) {
			$data['shipping_zasilkovna_api_key'] = $this->request->post['shipping_zasilkovna_api_key'];
		} else {
			$data['shipping_zasilkovna_api_key'] = $this->config->get('shipping_zasilkovna_api_key');
		}

		//save additional info
		if (isset($this->request->post['shipping_zasilkovna_tax_class_id'])) {
			$data['shipping_zasilkovna_tax_class_id'] = $this->request->post['shipping_zasilkovna_tax_class_id'];
		} else {
			$data['shipping_zasilkovna_tax_class_id'] = $this->config->get('shipping_zasilkovna_tax_class_id');
		}
		if (isset($this->request->post['shipping_zasilkovna_geo_zone_id'])) {
			$data['shipping_zasilkovna_geo_zone_id'] = $this->request->post['shipping_zasilkovna_geo_zone_id'];
		} else {
			$data['shipping_zasilkovna_geo_zone_id'] = $this->config->get('shipping_zasilkovna_geo_zone_id');
		}	
		if (isset($this->request->post['shipping_zasilkovna_weight_max'])) {
			$data['shipping_zasilkovna_weight_max'] = $this->request->post['shipping_zasilkovna_weight_max'];
		} else {
			$data['shipping_zasilkovna_weight_max'] = $this->config->get('shipping_zasilkovna_weight_max');
		}	
		if (isset($this->request->post['shipping_zasilkovna_status'])) {
			$data['shipping_zasilkovna_status'] = $this->request->post['shipping_zasilkovna_status'];
		} else {
			$data['shipping_zasilkovna_status'] = $this->config->get('shipping_zasilkovna_status');
		}
		if (isset($this->request->post['shipping_zasilkovna_sort_order'])) {
			$data['shipping_zasilkovna_sort_order'] = $this->request->post['shipping_zasilkovna_sort_order'];
		} else {
			$data['shipping_zasilkovna_sort_order'] = $this->config->get('shipping_zasilkovna_sort_order');
		}

		$this->load->model('localisation/tax_class');
		$data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();
		$this->load->model('localisation/geo_zone');
		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		
		$this->response->setOutput($this->load->view('extension/shipping/zasilkovna', $data));

	}
	
	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/shipping/zasilkovna')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}

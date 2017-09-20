<?php

class ModelExtensionShippingZasilkovna extends Model {
	public function getQuote($address) {
		$this->load->language('extension/shipping/zasilkovna');
		$weight = $this->cart->getWeight();
		$max_weight = (int)$this->config->get('shipping_zasilkovna_weight_max');
		$valid_weight = (!$max_weight && $max_weight !== 0) || ($max_weight > 0 && $weight <= $max_weight); // weight condition check, yay logic
		if($this->config->get('shipping_zasilkovna_status') === '1' && $valid_weight) {
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('shipping_zasilkovna_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");
			if(!$this->config->get('shipping_zasilkovna_geo_zone_id')) {
				$status = true;
			} elseif($query->num_rows) {
				$status = true;
			} else {
				$status = false;
			}
		} else {
			$status = false;
		}
		$method_data = [];
		if($status) {
			$quote_data = [];
			$api_key = $this->config->get('shipping_zasilkovna_api_key');
			$HELPER_JS = '<script> (function(d){ var el, id = "packetery-jsapi", head = d.getElementsByTagName("head")[0]; if(d.getElementById(id)){ return; } el = d.createElement("script"); el.id = id; el.async = true; el.src = "//www.zasilkovna.cz/api/' . $api_key . '/branch.js?callback=addHooks"; head.insertBefore(el, head.firstChild); }(document)); </script>
<script language="javascript" type="text/javascript">   ;
if(typeof window.packetery != "undefined"){
	setTimeout(function(){initBoxes()},1000)
}else{
	setTimeout(function(){setRequiredOpt()},500)
}
function initBoxes(){
	var api = window.packetery;
	divs = $(\'#zasilkovna_box\');
	$(\'.packetery-branch-list\').each(function(){
		api.initialize(api.jQuery(this));
		this.packetery.option("selected-id",0);
	});
	addHooks();  
	setRequiredOpt();
}
var SubmitButtonDisabled = true;
function setRequiredOpt(){
	var setOnce = false;
	var disableButton=false;
	var zasilkovna_selected = false;
	var opts={
		connectField: \'textarea[name=comment]\'
	}        
	$("div.packetery-branch-list").each(
		function(){
			var div = $(this).closest(\'.radio\');
			var radioButt = $(div).find(\'input[name="shipping_method"]:radio\');
			var select_branch_message = $(div).find(\'#select_branch_message\');
			if($(radioButt).is(\':checked\')){
				zasilkovna_selected = true;
			}else{//deselect branch (so when user click the radio again, he must select a branch). Made coz couldnt update connect-field if only clicked on radio with already selected branch
				if(this.packetery.option("selected-id")>0){
					this.packetery.option("selected-id",0);
				}
			}
			if($(radioButt).is(\':checked\')&&!this.packetery.option("selected-id")){
				select_branch_message.show();
				disableButton=true;
			}else{
				select_branch_message.hide();
			}
		}
	);
	$(\'#button-shipping-method\').attr(\'disabled\', disableButton);
		SubmitButtonDisabled = disableButton;
		if(!zasilkovna_selected){
			updateConnectedField(opts,0);
		}
}
function submitForm(){
	if(!SubmitButtonDisabled){
		updateConnectedField();
		$(\'#shipping\').submit();
	}
}
function updateConnectedField(opts, id){
	var branches;
	if(typeof(opts) == "undefined"){
		$(".packetery-branch-list").each(function(){
			if(this.packetery.option("selected-id")){
				opts = {
					connectField: "textarea[name=comment]",
					selectedId: this.packetery.option("selected-id")
				};
				branches = this.packetery.option("branches");
			}
		});
		return;
	}
	if (opts.connectField){
		if (typeof(id) == "undefined"){
			id = opts.selectedId
		}
		var f = $(opts.connectField);
		var v = f.val() || "",
		re = /\[Z\u00e1silkovna\s*;\s*[0-9]+\s*;\s*[^\]]*\]/,
		newV;
		if (id > 0){
			var branch = branches[id];
			newV = "[Z\u00e1silkovna; " + branch.id + "; " + branch.name + "]"
		} else {
			newV = ""
		}
		if (v.search(re) != -1){
			v = v.replace(re, newV)
		} else {
			if (v){
				v += "\n" + newV
			} else {
				v = newV
			}
		}
		
		function trim(s){
			return s.replace(/^\s*|\s*$/, "")
		}
		f.val(trim(v))
	}
}
function addHooks(){ //called when no zasilkovna method is selected. Dunno how to call this from the branch.js
//set each radio button to call setRequiredOpt if clicked
	$(\'input[name="shipping_method"]:radio\').each(
		function(){
			$(this).click(setRequiredOpt);
		}
	);
	button = $(\'[onclick="$(\\\'#shipping\\\').submit();"]\');
	button.removeAttr("onclick");
	button.click(submitForm);
	$("div.packetery-branch-list").each(
		function(){
			var fn = function(){
				var selected_id = this.packetery.option("selected-id");
				var tr = $(this).closest(\'div.radio\');
				var radioButt = $(tr).find(\'input[name="shipping_method"]:radio\');
				if(selected_id){
					$(radioButt).attr("checked",\'checked\');
					$(this).prev().find("input[type=\'radio\']").prop("checked", true);
					$(this).prev().find("input[type=\'radio\']").change();
				}
				setTimeout(setRequiredOpt, 1);
			};
			this.packetery.on("branch-change", fn);
			fn.call(this);
		}
	);
	
	$("#content").delegate("textarea[name=comment]", "change", function (){ 
		updateConnectedField();
	});
}
</script>';
			$addedHelperJS = false;
			$i = 0;
			foreach ($this->config->get('shipping_zasilkovna') as $zasilkovna) {
				$i ++;
				$enabled = $zasilkovna['enabled'];

				$config_destination = $zasilkovna['country'];
				$cart_destination = strtolower($this->cart->session->data["shipping_address"]["iso_code_2"]);
				if(empty($enabled) || $enabled == 0 || ($config_destination && $cart_destination && $config_destination != $cart_destination)) continue;
				$cost = 0;
				if($zasilkovna['freeover'] == 0 || $this->cart->getTotal() < $zasilkovna['freeover']) // shipment is not free
				$cost = $zasilkovna['price'];
				$title = $zasilkovna['title'];
				$country = $zasilkovna['country'];
				$JS = "";
				if($addedHelperJS == false) {
					$JS .= $HELPER_JS;
					$addedHelperJS = true;
				}
				if($zasilkovna['branches_enabled'] == "1") {
					$JS .= '<script>
						var radio = $(\'input:radio[name="shipping_method"][value="zasilkovna.' . $title . $i . '"]\');
						var parent_div = radio.parent().parent(); 
						if(parent_div.find(\'#zasilkovna_box\').length == 0){
							$(parent_div).append(\'<div id="zasilkovna_box" class="packetery-branch-list list-type=3 connect-field=textarea[name=comment] country=' . $country . '" style="border: 1px dotted black;">Načítání: seznam poboček osobního odběru</div> \');
							$(parent_div).append(\'<p id="select_branch_message" style="color:red; font-weight:bold; display:none">Vyberte pobočku</p>\');
						}
					</script>';
				}
				$quote_data[$title . $i] = [
					'id' => 'zasilkovna.' . $title . $i,
					'code' => 'zasilkovna.' . $title . $i,
					'title' => $title,
					'cost' => $cost,
					'tax_class_id' => $this->config->get('shipping_zasilkovna_tax_class_id'),
					'text' => $JS . $this->currency->format($this->tax->calculate($cost, $this->config->get('shipping_zasilkovna_tax_class_id'), $this->config->get('config_tax')), $this->session->data['currency']),
				];
			}
			$method_data = [
				'code' => 'zasilkovna',
				'title' => $this->language->get('text_title'),
				'quote' => $quote_data,
				'sort_order' => $this->config->get('shipping_zasilkovna_sort_order'),
				'error' => false
			];
		}

		return $method_data;
	}
}
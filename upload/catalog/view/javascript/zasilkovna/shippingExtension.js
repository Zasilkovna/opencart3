// helper function used for shipping extension for zasilkovna

// add new cart here 3/3
var cartsConfig = {
	urls: {
		standard: /checkout\/shipping_method/,
		journal3: /journal3\/checkout/,
	},
	buttons: {
		standard: '#button-shipping-method',
		journal3: '#quick-checkout-button-confirm'
	},
};

var $widgetConfigs = false;
var $activeWidgetConfig = false;
var selectedShippingMethod = '';

$(function() {
	/**
	 * Initialization of all required parts.
	 * Called every time ajax call finishes, several times for both classic and journal checkout
	 */
	$(document).ajaxSuccess(function(e, xhr, settings) {
		var isFetchShippingMethodUrl = false;
		for (var cartType in cartsConfig['urls']) {
			if (settings.url.match(cartsConfig['urls'][cartType]) !== null) {
				isFetchShippingMethodUrl = true;
				break;
			}
		}

		if (! isFetchShippingMethodUrl) {
			return;
		}

		$('#packeta-envelope, .packeta-shipping-item-envelope').remove();

		$widgetConfigs = $('.packeta-shipping-item-config');

		zasilkovnaCreateElementsandEvents();
		zasilkovnaLoadSelectedBranch();
	});

});

/**
 * Initialization of required elements and events.
 */
function zasilkovnaCreateElementsandEvents() {
	var additionalElementsHtml = '<input type="hidden" name="packeta-branch-id" id="packeta-branch-id">'
		+ '<input type="hidden" name="packeta-branch-name" id="packeta-branch-name">'
		+ '<input type="hidden" name="packeta-carrier-id" id="packeta-carrier-id">'
		+ '<input type="hidden" name="packeta-carrier-pickup-point" id="packeta-carrier-pickup-point">';
	var additionalElementsEnvelope;

	// create envelope element with required additional html elements
	additionalElementsEnvelope = document.createElement('div');
	additionalElementsEnvelope.setAttribute('id', 'packeta-envelope');
	additionalElementsEnvelope.innerHTML = additionalElementsHtml;
	document.body.appendChild(additionalElementsEnvelope);

	$widgetConfigs.each(function(index, widgetConfigElement) {
		var $widgetConfig = $(widgetConfigElement);
		var methodCode = $widgetConfig.attr('data-method-code');
		var selectedPointElementHtml = '<div> <img src="catalog/view/theme/zasilkovna/zasilkovna.jpg"> <input type="button" class="btn btn-primary open-packeta-widget" data-widget-method-code="' + methodCode + '" value="' + $widgetConfig.attr('data-select-branch-text') + '"> </div>'
			+ '<div class="picked-delivery-place" data-widget-method-code="' + methodCode + '">' + $widgetConfig.attr('data-no-branch-selected-text') + '</div>';
		var selectedPointElement = document.createElement('div');
		selectedPointElement.setAttribute('class', 'packeta-shipping-item-envelope');
		selectedPointElement.innerHTML = selectedPointElementHtml;
		$widgetConfig.parent().parent().append(selectedPointElement);
	});

	$('.open-packeta-widget').on('click', function(e) {
		e.preventDefault();

		var methodCode = $(this).attr('data-widget-method-code');
		$activeWidgetConfig = getWidgetConfigByMethodCode(methodCode);
		if ($activeWidgetConfig.length === 0) {
			return;
		}

		$("input[name='shipping_method'][value='" + methodCode + "']").click();
		initializePacketaWidget();
	});

	// adding onclick handler for radio buttons with list of shipping methods
	$('input[name="shipping_method"]:radio').click(zasilkovnaShipmentMethodOnChange);
	// for case it's selected
	zasilkovnaShipmentMethodOnChange();
}

/**
 * Handler for change of shipping type (click on radio button)
 */
function zasilkovnaShipmentMethodOnChange() {
	var selectedMethodCode = getSelectedShippingMethodCode();
	if (selectedMethodCode !== selectedShippingMethod) {
		selectedShippingMethod = selectedMethodCode;
		$('#packeta-branch-id, #packeta-branch-name, #packeta-carrier-id, #packeta-carrier-pickup-point').val('');
		$('.picked-delivery-place').each(function(index, pickedDeliveryPlaceElement) {
			var methodCode = $(pickedDeliveryPlaceElement).attr('data-widget-method-code');
			var $widgetConfig = getWidgetConfigByMethodCode(methodCode);
			$(pickedDeliveryPlaceElement).html($widgetConfig.attr('data-no-branch-selected-text'));
		});
	}

	updateSelectedMethodWidgetVisibility();

	var isPickupPointCarrierSelected = detectPickupPointCarrierShippingMethod();
	var selectedBranch = $('#packeta-branch-id').val();
	var isSubmitButtonDisabled = false;

	isSubmitButtonDisabled = false;
	if (isPickupPointCarrierSelected) {
		if (selectedBranch === '') {
			isSubmitButtonDisabled = true;
		}
	}

	getConfirmationButton().attr('disabled', isSubmitButtonDisabled);
}

function getSelectedShippingMethodCode() {
	return $("input[name='shipping_method']:checked").val();
}

function getWidgetConfigByMethodCode(methodCode) {
	return $(".packeta-shipping-item-config[data-method-code='" + methodCode + "']");
}

function getPickedDeliveryPlaceElement(methodCode) {
	return $('.picked-delivery-place[data-widget-method-code="' + methodCode + '"]');
}

function updateSelectedMethodWidgetVisibility() {
	var selectedMethodCode = getSelectedShippingMethodCode();

	$('.packeta-shipping-item-envelope').hide();
	if (!detectPickupPointCarrierShippingMethod()) {
		return;
	}

	$('.packeta-shipping-item-envelope').has('.open-packeta-widget[data-widget-method-code="' + selectedMethodCode + '"]').show();
}

function detectPickupPointCarrierShippingMethod() {
	var selectedMethodCode = getSelectedShippingMethodCode();
	if (!selectedMethodCode || selectedMethodCode.indexOf('zasilkovna.') !== 0) {
		return false;
	}

	return getWidgetConfigByMethodCode(selectedMethodCode).length === 1;
}

function getConfirmationButton() {
	for (var cartType in cartsConfig['buttons']) {
		var $element = $(cartsConfig['buttons'][cartType]);
		if ($element.length) {
			return $element;
		}
	}

	console.error('No supported confirmation button found.');
	return null;
}

/**
 * Handler for load of selected branch from session.
 * It is called after initialization of additional HTML elements and JS events during after switch to "Step 4: Delivery Method"
 * during "checkout".
 */
function zasilkovnaLoadSelectedBranch() {
	$.ajax({
		url: 'index.php?route=extension/module/zasilkovna/loadSelectedBranch',
		type: 'get',
		dataType: 'json',
		success: function(json) {
			if (json.zasilkovna_branch_id !== '') {
				$('#packeta-branch-id').val(json.zasilkovna_branch_id);
				$('#packeta-branch-name').val(json.zasilkovna_branch_name);
				$('#packeta-carrier-id').val(json.zasilkovna_carrier_id);
				$('#packeta-carrier-pickup-point').val(json.zasilkovna_carrier_pickup_point);
				if (detectPickupPointCarrierShippingMethod()) {
					getPickedDeliveryPlaceElement(getSelectedShippingMethodCode()).html(json.zasilkovna_branch_description);
				}
			}
			updateSelectedMethodWidgetVisibility();
			zasilkovnaUpdateSubmitButtonStatus();
		},
		error: function(xhr, ajaxOptions, thrownError) {
			alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
		}
	});
}

/**
 * Sets status of "Continue" button according to selected shipping properties.
 * Button "Continue" is disabled when "zasilkovna" is selected ad shipping method and target branch is not selected.
 */
function zasilkovnaUpdateSubmitButtonStatus() {
	var isZasilkovnaSelected = detectPickupPointCarrierShippingMethod();
	var selectedBranchId = $('#packeta-branch-id').val();

	getConfirmationButton().attr('disabled', (isZasilkovnaSelected && selectedBranchId === ''));
}

/**
 * Handler for save selected branch to session.
 * It it called after click on "Continue" button in "Step 4: Delivery Method". Another ajax request for save
 * selected shipping method and comment is sent at the same time.
 */
function zasilkovnaSaveSelectedBranch() {
	var branchId = $('#packeta-branch-id').val(),
		dataToSend,
		selectedPointDescription = '';

	if ($activeWidgetConfig.length) {
		selectedPointDescription = getPickedDeliveryPlaceElement($activeWidgetConfig.attr('data-method-code')).html();
	}

	dataToSend = {
		zasilkovna_branch_id: branchId,
		zasilkovna_branch_name: $('#packeta-branch-name').val(),
		zasilkovna_branch_description: selectedPointDescription,
		zasilkovna_carrier_id: $('#packeta-carrier-id').val(),
		zasilkovna_carrier_pickup_point: $('#packeta-carrier-pickup-point').val()
	};

	$.ajax({
		url: 'index.php?route=extension/module/zasilkovna/saveSelectedBranch',
		type: 'post',
		data: dataToSend,
		success: function() {
			// enable "Continue" button for switch to next step in "checkout workflow"
			getConfirmationButton().attr('disabled', false);
			$("input[name='shipping_method'][value='" + $activeWidgetConfig.attr('data-method-code') + "']").click();
		},
		error: function(xhr, ajaxOptions, thrownError) {
			alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
		}
	});
}

/**
 * helper function for library to choose delivery point using map widget
 * defined as function because it must be called when all required elements are created in DOM
 */
function initializePacketaWidget() {
	// list of configuration properties for widget
	var apiKey = $activeWidgetConfig.attr('data-api_key');
	var vendors = $activeWidgetConfig.attr('data-vendors');

	// preparation of parameters for widget
	var widgetOptions = {
		appIdentity: $activeWidgetConfig.attr('data-app_identity'),
		country: $activeWidgetConfig.attr('data-enabled_countries'),
		language: $activeWidgetConfig.attr('data-language')
	};
	if (vendors) {
		widgetOptions.vendors = JSON.parse(vendors);
	}

	Packeta.Widget.pick(apiKey, selectPickUpPointCallback, widgetOptions);
}

/**
 * Callback function for processing of pickup point selection using map widget.
 * It is called by widget with object "ExtendedPoint" as parameter.
 *
 * @param targetPoint detail information about selected point
 * @return void
 */
function selectPickUpPointCallback(targetPoint) {
	if (null == targetPoint) { // selection of pickup point was cancelled
		return;
	}

	// save ID and name of selected point point to hidden input elements
	document.getElementById('packeta-branch-id').value = targetPoint.pickupPointType === 'external' ? targetPoint.carrierId : targetPoint.id;
	document.getElementById('packeta-branch-name').value = targetPoint.nameStreet;
	document.getElementById('packeta-carrier-id').value = targetPoint.carrierId ? targetPoint.carrierId : '';
	document.getElementById('packeta-carrier-pickup-point').value = targetPoint.carrierPickupPointId ? targetPoint.carrierPickupPointId : '';

	// show name of selected pickup point to user
	getPickedDeliveryPlaceElement($activeWidgetConfig.attr('data-method-code')).html(targetPoint.nameStreet);

	// Save selected branch to session. It must be done now because it it not possible to send two ajax requests after click
	// on "Continue" button. There is conflict if two php script wants to save to session. Session data saved by first script
	// can be overwritten by second script.
	// Button "Continue" is enabled when request is finished to avoid conflict described above.
	zasilkovnaSaveSelectedBranch();
}

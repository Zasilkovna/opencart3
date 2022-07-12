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

var $widgetButton = false;
var loadBranchRunning = false;

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

		$widgetButton = $('#packeta-first-shipping-item');
		if (!$widgetButton.length) {
			return;
		}

		zasilkovnaCreateElementsandEvents();
		initializePacketaWidget();
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
	var selectedPointElementHtml =  '<div> <img src="catalog/view/theme/zasilkovna/zasilkovna.jpg"> <input type="button" class="btn btn-primary" id="open-packeta-widget" value="' + $widgetButton.data('select_branch_text') + '"> </div>'
		+ ' <div id="picked-delivery-place">' + $widgetButton.data('no_branch_selected_text') + '</div>';
	var selectedPointElement;
	var additionalElementsEnvelope;
	var shippingElementEnvelope;

	// create envelope element with required additional html elements
	additionalElementsEnvelope = document.createElement('div');
	additionalElementsEnvelope.setAttribute('id', 'packeta-envelope');
	additionalElementsEnvelope.innerHTML = additionalElementsHtml;
	document.body.appendChild(additionalElementsEnvelope);

	// Adding additional visible element for display information about selected pickup point.
	// Search for dummy element of "zasilkovna" shipping item is required because there is no id nor class which can
	// be used for identification
	shippingElementEnvelope = $widgetButton.parent().parent();
	selectedPointElement = document.createElement('div');
	selectedPointElement.setAttribute('class', 'packeta-shipping-item-envelope');
	selectedPointElement.innerHTML = selectedPointElementHtml;
	shippingElementEnvelope.append(selectedPointElement);

	// adding onclick handler for radio buttons with list of shipping methods
	$('input[name="shipping_method"]:radio').click(zasilkovnaShipmentMethodOnChange);
	// for case it's selected
	zasilkovnaShipmentMethodOnChange();
}

/**
 * Handler for change of shipping type (click on radio button)
 */
function zasilkovnaShipmentMethodOnChange() {
	// check if radio button for zasilkovna is selected
	var isZasilkovnaSelected = detectPacketeryShippingMethod();
	var selectedBranch = $('#packeta-branch-id').val();
	var isSubmitButtonDisabled = false;

	// disable "Continue" button if zasilkovna is selected but no branch is selected from map widget
	isSubmitButtonDisabled = false;
	if (isZasilkovnaSelected) {
		if (selectedBranch === '') {
			isSubmitButtonDisabled = true;
		}
	}

	getConfirmationButton().attr('disabled', isSubmitButtonDisabled);
}

function detectPacketeryShippingMethod() {
	return $("input[name='shipping_method'][value^='zasilkovna.']:checked").length === 1;
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

function delay(time) {
	return new Promise(resolve => setTimeout(resolve, time));
}

/**
 * Handler for load of selected branch from session.
 * It is called after initialization of additional HTML elements and JS events during after switch to "Step 4: Delivery Method"
 * during "checkout".
 */
function zasilkovnaLoadSelectedBranch() {

	if (loadBranchRunning) {
		return;
	}
	loadBranchRunning = true;
	const requestStart = Date.now();

	$.ajax({
		url: 'index.php?route=extension/module/zasilkovna/loadSelectedBranch',
		type: 'get',
		dataType: 'json',
		success: function(json) {
			if (json.zasilkovna_branch_id !== '') {
				$('#packeta-branch-id').val(json.zasilkovna_branch_id);
				$('#packeta-branch-name').val(json.zasilkovna_branch_name);
				$('#picked-delivery-place').html(json.zasilkovna_branch_description);
				$('#packeta-carrier-id').val(json.zasilkovna_carrier_id);
				$('#packeta-carrier-pickup-point').val(json.zasilkovna_carrier_pickup_point);
			}
			zasilkovnaUpdateSubmitButtonStatus();

			const requestEnd = Date.now();
			const delayTime = 1000 - (requestEnd - requestStart);
			if (delayTime > 0) {
				delay(delayTime).then(function () {
					loadBranchRunning = false;
				});
			} else {
				loadBranchRunning = false;
			}
		},
		error: function(xhr, ajaxOptions, thrownError) {
			loadBranchRunning = false;
			alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
		}
	});
}

/**
 * Sets status of "Continue" button according to selected shipping properties.
 * Button "Continue" is disabled when "zasilkovna" is selected ad shipping method and target branch is not selected.
 */
function zasilkovnaUpdateSubmitButtonStatus() {
	var selectedShipment = $('#collapse-shipping-method input[type=\'radio\']:checked');
	var isZasilkovnaSelected = detectPacketeryShippingMethod();
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
		dataToSend;

	dataToSend = {
		zasilkovna_branch_id: branchId,
		zasilkovna_branch_name: $('#packeta-branch-name').val(),
		zasilkovna_branch_description: $('#picked-delivery-place').html(),
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
			// mark carrier "Zasilkovna" as active when pickup point is selected
			$widgetButton.parent().parent().find('input[type=radio]').click();
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
	var apiKey = $widgetButton.data('api_key');

	// preparation of parameters for widget
	var widgetOptions = {
		appIdentity: $widgetButton.data('app_identity'),
		country: $widgetButton.data('enabled_countries'),
		language: $widgetButton.data('language')
	};

	document.getElementById('open-packeta-widget').addEventListener('click', function (e) {
		e.preventDefault();
		// displaying of map widget
		Packeta.Widget.pick(apiKey, selectPickUpPointCallback, widgetOptions);
	});

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
	document.getElementById('picked-delivery-place').innerHTML = targetPoint.nameStreet;

	// Save selected branch to session. It must be done now because it it not possible to send two ajax requests after click
	// on "Continue" button. There is conflict if two php script wants to save to session. Session data saved by first script
	// can be overwritten by second script.
	// Button "Continue" is enabled when request is finished to avoid conflict described above.
	zasilkovnaSaveSelectedBranch();
}

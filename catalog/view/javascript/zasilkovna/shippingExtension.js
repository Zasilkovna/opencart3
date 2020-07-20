// helper function used for shipping extension for zasilkovna

/**
 * Initialization of all required parts.
 */
function zasilkovnaInitAll() {
	zasilkovnaCreateElementsandEvents();
	initializePacketaWidget();
	zasilkovnaLoadSelectedBranch();
}

/**
 * Initialization of required elements and events.
 */
function zasilkovnaCreateElementsandEvents() {
	var additionalElementsHtml = '<input type="hidden" name="packeta-branch-id" id="packeta-branch-id">'
		+ '<input type="hidden" name="packeta-branch-name" id="packeta-branch-name">';
	var selectedPointElementHtml =  '<div> <img src="catalog/view/image/zasilkovna.jpg"> <input type="button" class="btn btn-primary" id="open-packeta-widget" value="' + window.zasilkovnaWidgetParameters.selectBranchText + '"> </div>'
		+ ' <div id="picked-delivery-place">' + window.zasilkovnaWidgetParameters.noBranchSelectedText + '</div>';
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
	shippingElementEnvelope = $('#packeta-first-shipping-item').parent().parent();
	selectedPointElement = document.createElement('div');
	selectedPointElement.setAttribute('class', 'packeta-shipping-item-envelope');
	selectedPointElement.innerHTML = selectedPointElementHtml;
	shippingElementEnvelope.append(selectedPointElement);

	// adding onclick handler for radio buttons with list of shipping methods
	$('input[name="shipping_method"]:radio').click(zasilkovnaShipmentMethodOnChange);
}

/**
 * Handler for change of shipping type (click on radio button)
 */
function zasilkovnaShipmentMethodOnChange() {
	// check if radio button for zasilkovna is selected
	var isZasilkovnaSelected = $(this).val().match('zasilkovna.*') !== null;
	var selectedBranch = $('#packeta-branch-id').val();
	var isSubmitButtonDisabled = false;

	// disable "Continue" button if zasilkovna is selected but no branch is selected from map widget
	isSubmitButtonDisabled = false;
	if (isZasilkovnaSelected) {
		if (selectedBranch === '') {
			isSubmitButtonDisabled = true;
		}
	}

	$('#button-shipping-method').attr('disabled', isSubmitButtonDisabled);
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
				$('#picked-delivery-place').html(json.zasilkovna_branch_description);
			}
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
	var selectedShipment = $('#collapse-shipping-method input[type=\'radio\']:checked');
	var isZasilkovnaSelected = selectedShipment.val().match('zasilkovna.*') !== null;
	var selectedBranchId = $('#packeta-branch-id').val();

	$('#button-shipping-method').attr('disabled', (isZasilkovnaSelected && selectedBranchId === ''));
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
		zasilkovna_branch_description: $('#picked-delivery-place').html()
	};

	$.ajax({
		url: 'index.php?route=extension/module/zasilkovna/saveSelectedBranch',
		type: 'post',
		data: dataToSend,
		success: function() {
			// enable "Continue" button for switch to next step in "checkout workflow"
			$('#button-shipping-method').attr('disabled', false);
			// mark carrier "Zasilkovna" as active when pickup point is selected
			$('#packeta-first-shipping-item').parent().parent().find('input[type=radio]').prop('checked', true);
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
	var apiKey = window.zasilkovnaWidgetParameters.apiKey;


	// preparation of parameters for widget
	var widgetOptions = {
		appIdentity: window.zasilkovnaWidgetParameters.appIdentity,
		country: window.zasilkovnaWidgetParameters.enabledCountries,
		language: window.zasilkovnaWidgetParameters.language
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
	document.getElementById('packeta-branch-id').value = targetPoint.id;
	document.getElementById('packeta-branch-name').value = targetPoint.nameStreet;

	// show name of selected pickup point to user
	document.getElementById('picked-delivery-place').innerHTML = targetPoint.nameStreet;

	// Save selected branch to session. It must be done now because it it not possible to send two ajax requests after click
	// on "Continue" button. There is conflict if two php script wants to save to session. Session data saved by first script
	// can be overwritten by second script.
	// Button "Continue" is enabled when request is finished to avoid conflict described above.
	zasilkovnaSaveSelectedBranch();
}

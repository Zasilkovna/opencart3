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
	var selectedPointElementHtml =  '<div> <img src="' + getPacketaLogoBase64() + '"> <input type="button" class="btn btn-primary" id="open-packeta-widget" value="' + $widgetButton.data('select_branch_text') + '"> </div>'
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
				$('#packeta-carrier-id').val(json.zasilkovna_carrier_id);
				$('#packeta-carrier-pickup-point').val(json.zasilkovna_carrier_pickup_point);
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
			$widgetButton.parent().parent().find('input[type=radio]').prop('checked', true);
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

function getPacketaLogoBase64() {
	return "data:image/jpeg;base64,/9j/4AAQSkZJRgABAgEAYABgAAD/4QUJRXhpZgAATU0AKgAAAAgABwESAAMAAAABAAEAAAEaAAUAAAABAAAAYgEbAAUAAAABAAAAagEoAAMAAAABAAIAAAExAAIAAAAcAAAAcgEyAAIAAAAUAAAAjodpAAQAAAABAAAApAAAANAADqV6AAAnEAAOpXoAACcQQWRvYmUgUGhvdG9zaG9wIENTMyBXaW5kb3dzADIwMTc6MDQ6MzAgMTQ6NTU6MzYAAAAAA6ABAAMAAAABAAEAAKACAAQAAAABAAAAIKADAAQAAAABAAAAIgAAAAAAAAAGAQMAAwAAAAEABgAAARoABQAAAAEAAAEeARsABQAAAAEAAAEmASgAAwAAAAEAAgAAAgEABAAAAAEAAAEuAgIABAAAAAEAAAPTAAAAAAAAAEgAAAABAAAASAAAAAH/2P/gABBKRklGAAECAABIAEgAAP/tAAxBZG9iZV9DTQAB/+4ADkFkb2JlAGSAAAAAAf/bAIQADAgICAkIDAkJDBELCgsRFQ8MDA8VGBMTFRMTGBEMDAwMDAwRDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAENCwsNDg0QDg4QFA4ODhQUDg4ODhQRDAwMDAwREQwMDAwMDBEMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwM/8AAEQgAIgAgAwEiAAIRAQMRAf/dAAQAAv/EAT8AAAEFAQEBAQEBAAAAAAAAAAMAAQIEBQYHCAkKCwEAAQUBAQEBAQEAAAAAAAAAAQACAwQFBgcICQoLEAABBAEDAgQCBQcGCAUDDDMBAAIRAwQhEjEFQVFhEyJxgTIGFJGhsUIjJBVSwWIzNHKC0UMHJZJT8OHxY3M1FqKygyZEk1RkRcKjdDYX0lXiZfKzhMPTdePzRieUpIW0lcTU5PSltcXV5fVWZnaGlqa2xtbm9jdHV2d3h5ent8fX5/cRAAICAQIEBAMEBQYHBwYFNQEAAhEDITESBEFRYXEiEwUygZEUobFCI8FS0fAzJGLhcoKSQ1MVY3M08SUGFqKygwcmNcLSRJNUoxdkRVU2dGXi8rOEw9N14/NGlKSFtJXE1OT0pbXF1eX1VmZ2hpamtsbW5vYnN0dXZ3eHl6e3x//aAAwDAQACEQMRAD8A5xznbjqefFWsHpnU+oE/Y6X2tb9Kz6Nbf69z9tbP85LpVdNnV8WvIG6my9jXjyc7au+Zh42b06r7d65LTIvx3lvpOYSz2YdcUbG7fzKXrNxYuOzez03N837HCK+b9I9P8H9J4XN6L1fBZ6t1RdQeMipwtq/7dpL2f5yotc7cNTz4ruT1ezHzBj72ZrLmPdV1Kprsawhg3O9euv8ARZX+Z6a5r6xMrZ1Rm0APfRRZfADQbHsbZYQxgaxn0vzUsmIRHECd6oq5bmpZJcE4i+HjEo/KR8vyyf/Q51ljqr22t+lW8PHxady9B6L1nGupkO/V3vcQ881vscbPs+SPzPe79Xu/mrV5476R+KPg52RgX+tQRqNtlbhLHsP0q7Wfnscs3Dl9s+Ben53lBzEB0lH5XqrxQyol5bWMdhruyXDSpr/8Gxv5+Rb/AIOlv9tcx1HMGd1GzKa0tY8tDGuMkNaG1sn+y1Nm9RyM0V1viumkRXSzRgJ+nZ/Kts/PscqzfpD4pZs3HoBoEcnyfsgykbmQR/difU//0efd9I/Q5Tf5i4tJZD2D2n+Ynb9IfQ5XFJJKf//Z/+0KQFBob3Rvc2hvcCAzLjAAOEJJTQQlAAAAAAAQAAAAAAAAAAAAAAAAAAAAADhCSU0ELwAAAAAASukAAQBIAAAASAAAAAAAAAAAAAAA0AIAAEACAAAAAAAAAAAAABgDAABkAgAAAAHAAwAAsAQAAAEADycBAGEAZwBlAC4AcABuAGcAOEJJTQPtAAAAAAAQAF/8kgABAAIAX/ySAAEAAjhCSU0EJgAAAAAADgAAAAAAAAAAAAA/gAAAOEJJTQQNAAAAAAAEAAAAHjhCSU0EGQAAAAAABAAAAB44QklNA/MAAAAAAAkAAAAAAAAAAAEAOEJJTQQKAAAAAAABAAA4QklNJxAAAAAAAAoAAQAAAAAAAAACOEJJTQP1AAAAAABIAC9mZgABAGxmZgAGAAAAAAABAC9mZgABAKGZmgAGAAAAAAABADIAAAABAFoAAAAGAAAAAAABADUAAAABAC0AAAAGAAAAAAABOEJJTQP4AAAAAABwAAD/////////////////////////////A+gAAAAA/////////////////////////////wPoAAAAAP////////////////////////////8D6AAAAAD/////////////////////////////A+gAADhCSU0E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LjAvIiB4YXA6Q3JlYXRlRGF0ZT0iMjAxNy0wNC0zMFQxNDo1NTozNiswMzowMCIgeGFwOk1vZGlmeURhdGU9IjIwMTctMDQtMzBUMTQ6NTU6MzYrMDM6MDAiIHhhcDpNZXRhZGF0YURhdGU9IjIwMTctMDQtMzBUMTQ6NTU6MzYrMDM6MDAiIHhhcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENTMyBXaW5kb3dzIiBkYzpmb3JtYXQ9ImltYWdlL2pwZWciIHBob3Rvc2hvcDpDb2xvck1vZGU9IjMiIHBob3Rvc2hvcDpJQ0NQcm9maWxlPSJzUkdCIElFQzYxOTY2LTIuMSIgcGhvdG9zaG9wOkhpc3Rvcnk9IiIgeGFwTU06SW5zdGFuY2VJRD0idXVpZDpCRDkxRkZERjlCMkRFNzExODI1NEQ2RjdCOUUwQjU4OSIgeGFwTU06RG9jdW1lbnRJRD0idXVpZDpCQzkxRkZERjlCMkRFNzExODI1NEQ2RjdCOUUwQjU4OSIgdGlmZjpPcmllbnRhdGlvbj0iMSIgdGlmZjpYUmVzb2x1dGlvbj0iOTU5ODY2LzEwMDAwIiB0aWZmOllSZXNvbHV0aW9uPSI5NTk4NjYvMTAwMDAiIHRpZmY6UmVzb2x1dGlvblVuaXQ9IjIiIHRpZmY6TmF0aXZlRGlnZXN0PSIyNTYsMjU3LDI1OCwyNTksMjYyLDI3NCwyNzcsMjg0LDUzMCw1MzEsMjgyLDI4MywyOTYsMzAxLDMxOCwzMTksNTI5LDUzMiwzMDYsMjcwLDI3MSwyNzIsMzA1LDMxNSwzMzQzMjtGMzBDMTc5MjQ1MEJGQTAyNUU1Mjk3MDBGNkNBOUNGNCIgZXhpZjpQaXhlbFhEaW1lbnNpb249IjMyIiBleGlmOlBpeGVsWURpbWVuc2lvbj0iMzQiIGV4aWY6Q29sb3JTcGFjZT0iMSIgZXhpZjpOYXRpdmVEaWdlc3Q9IjM2ODY0LDQwOTYwLDQwOTYxLDM3MTIxLDM3MTIyLDQwOTYyLDQwOTYzLDM3NTEwLDQwOTY0LDM2ODY3LDM2ODY4LDMzNDM0LDMzNDM3LDM0ODUwLDM0ODUyLDM0ODU1LDM0ODU2LDM3Mzc3LDM3Mzc4LDM3Mzc5LDM3MzgwLDM3MzgxLDM3MzgyLDM3MzgzLDM3Mzg0LDM3Mzg1LDM3Mzg2LDM3Mzk2LDQxNDgzLDQxNDg0LDQxNDg2LDQxNDg3LDQxNDg4LDQxNDkyLDQxNDkzLDQxNDk1LDQxNzI4LDQxNzI5LDQxNzMwLDQxOTg1LDQxOTg2LDQxOTg3LDQxOTg4LDQxOTg5LDQxOTkwLDQxOTkxLDQxOTkyLDQxOTkzLDQxOTk0LDQxOTk1LDQxOTk2LDQyMDE2LDAsMiw0LDUsNiw3LDgsOSwxMCwxMSwxMiwxMywxNCwxNSwxNiwxNywxOCwyMCwyMiwyMywyNCwyNSwyNiwyNywyOCwzMDs1M0VGNjZGRjVGNDZEQTdBQUQ2NzZEMjEzMEZFM0EyRiI+IDx4YXBNTTpEZXJpdmVkRnJvbSByZGY6cGFyc2VUeXBlPSJSZXNvdXJjZSIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8P3hwYWNrZXQgZW5kPSJ3Ij8+/+IMWElDQ19QUk9GSUxFAAEBAAAMSExpbm8CEAAAbW50clJHQiBYWVogB84AAgAJAAYAMQAAYWNzcE1TRlQAAAAASUVDIHNSR0IAAAAAAAAAAAAAAAEAAPbWAAEAAAAA0y1IUCAgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAARY3BydAAAAVAAAAAzZGVzYwAAAYQAAABsd3RwdAAAAfAAAAAUYmtwdAAAAgQAAAAUclhZWgAAAhgAAAAUZ1hZWgAAAiwAAAAUYlhZWgAAAkAAAAAUZG1uZAAAAlQAAABwZG1kZAAAAsQAAACIdnVlZAAAA0wA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";
}

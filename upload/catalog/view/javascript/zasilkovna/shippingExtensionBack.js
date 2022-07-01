/**
 * Initialization of all required parts.
 */
$(function() {
	$widgetButton = $('#packeta-widget-settings');

	if (!$widgetButton.length) {
		return;
	}

	initializePacketaWidget();

});

/*
 * helper function for library to choose delivery point using map widget
 * defined as function because it must be called when all required elements are created in DOM
 */
function initializePacketaWidget() {
	// list of configuration properties for widget
	var apiKey = $widgetButton.data('api_key');

	// preparation of parameters for widget
	var widgetOptions = {
		appIdentity: $widgetButton.data('app_identity'),
		country: $widgetButton.data('enabled_country'),
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
	$('#picked-delivery-place').html(targetPoint.nameStreet);
}

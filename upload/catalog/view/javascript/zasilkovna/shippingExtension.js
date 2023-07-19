// helper function used for shipping extension for zasilkovna

$(function() {

	var cartsConfig = {
		urls: {
			standard: /checkout\/shipping_method$/,
			journal3: /journal3\/checkout/,
		},
		buttons: {
			standard: '#button-shipping-method',
			journal3: '#quick-checkout-button-confirm'
		},
	};

	var loadBranchRunning = false;	// flag for preventing multiple ajax calls? (related to Journal3)
	var loadSelectedBranchDebounced = debounce(loadSelectedBranch, 1000);

	var selectedPickupPoints = {};

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

		// Journal3: calls checkout/save both after shipping method selection (we don't need to reinit) and country change (we need to reinit)
		// instead of detecting country change, we just remove all existing widgets and reinit them
		// TODO: mazat jen pokud se změnila země?
		$('.packeta-shipping-item-envelope').remove();
		selectedPickupPoints = {};

		var $vendorWidgetConfigs = findVendorWidgetConfig();
		if ($vendorWidgetConfigs.length === 0) {
			return;
		}

		var widgetConfig = $('#packeta-widget-config').data();

		addWidgetButtons(widgetConfig, $vendorWidgetConfigs);
		registerOnShippingMethodChangeListener();
		initializeWidgetButtons(widgetConfig);
		loadSelectedBranchDebounced();
	});

	function findVendorWidgetConfig($parentElement = null) {
		var selector = '.packeta-vendor-widget-config';

		if ($parentElement === null) {
			return $(selector);
		}

		return $(selector, $parentElement);
	}

	/**
	 * Initialization of required elements and events.
	 */
	function addWidgetButtons(widgetConfig, $vendorWidgetConfigs) {
		$vendorWidgetConfigs.each(function () {
			// TODO: rename packeta-shipping-item-envelope to packeta-widget, prefix picked-delivery-place
			var html =
				'<div class="packeta-shipping-item-envelope" style="display: none;">' +
					'<img src="catalog/view/theme/zasilkovna/zasilkovna.jpg">' +
					'<input type="button" class="btn btn-primary open-packeta-widget" value="' + widgetConfig.select_branch_text + '">' +
					'<div class="picked-delivery-place">' + widgetConfig.no_branch_selected_text + '</div>' +
				' </div>';

			$(this).parent().parent().append(html);
		});
	}

	function registerOnShippingMethodChangeListener() {
		// adding onclick handler for radio buttons with list of shipping methods
		$('input[name="shipping_method"]:radio')
			.off('change.packeta')
			.on('change.packeta', onShippingMethodChange);

		// trigger change to set initial state of "Continue" button
		var shippingMethod = getSelectedShippingMethod();
		getShippingMethodRadioButton(shippingMethod).trigger('change');
	}

	/**
	 * helper function for library to choose delivery point using map widget
	 * defined as function because it must be called when all required elements are created in DOM
	 */
	function initializeWidgetButtons(widgetConfig) {
		$('.open-packeta-widget').each(function () {
			var $this = $(this);
			var $vendorWidgetConfig = findVendorWidgetConfig(
				$this.closest('.packeta-shipping-item-envelope').parent()
			);

			var widgetVendors = {
				//price: 100, TODO: přidat cenu
				selected: true
			};

			var carrierId = $vendorWidgetConfig.data('carrier_id');
			if (carrierId === '' || typeof carrierId === 'undefined') {
				widgetVendors.country = $vendorWidgetConfig.data('country');
				widgetVendors.group = ($vendorWidgetConfig.data('group')) === 'zpoint' ? '' : $vendorWidgetConfig.data('group');
			} else {
				widgetVendors.carrierId = carrierId;
			}

			// preparation of parameters for widget
			var widgetOptions = {
				appIdentity: widgetConfig.app_identity,
				language: widgetConfig.language,
			};

			var useVendors = widgetConfig.use_vendors;
			if (useVendors === 1) {
				widgetOptions.vendors = [widgetVendors];
			} else  {
				widgetOptions.country = widgetVendors.country;
			}

			$this.on('click', function () {
				Packeta.Widget.pick(widgetConfig.api_key, onPickUpPointSelected, widgetOptions);
			});
		});
	}

	/**
	 * Callback function for processing of pickup point selection using map widget.
	 * It is called by widget with object "ExtendedPoint" as parameter.
	 *
	 * @param targetPoint detail information about selected point
	 * @return void
	 */
	function onPickUpPointSelected(targetPoint) {
		if (null == targetPoint) { // selection of pickup point was cancelled
			return;
		}

		var shippingMethod = getSelectedShippingMethod();
		var selectedBranch = {
			branchId: targetPoint.pickupPointType === 'external' ? targetPoint.carrierId : targetPoint.id,
			branchName: targetPoint.nameStreet,
			branchDescription: targetPoint.nameStreet,
			carrierId: targetPoint.carrierId ? targetPoint.carrierId : '',
			carrierPickupPoint: targetPoint.carrierPickupPointId ? targetPoint.carrierPickupPointId : ''
		};
		selectedPickupPoints[shippingMethod] = selectedBranch;

		showPickupPointDescription(shippingMethod, selectedBranch.branchDescription);

		// Save selected branch to session. It must be done now because it it not possible to send two ajax requests after click
		// on "Continue" button. There is conflict if two php script wants to save to session. Session data saved by first script
		// can be overwritten by second script.
		// Button "Continue" is enabled when request is finished to avoid conflict described above.
		saveSelectedBranch(shippingMethod, selectedBranch);
	}

	/**
	 * Handler for change of shipping type (click on radio button)
	 */
	function onShippingMethodChange() {
		$('.packeta-shipping-item-envelope').hide();

		if (! selectedShippingMethodRequiresPickupPoint()) {
			enableSubmitButton();
			return;
		}

		var shippingMethod = getSelectedShippingMethod();
		getShippingMethodEnvelope(shippingMethod).show();
		disableSubmitButton();

		var selectedBranch = getStoredSelectedPickupPoint();
		if (selectedBranch !== null) {
			saveSelectedBranch(shippingMethod, selectedBranch);	// toggles Submit button as well
		}
	}

	function selectedShippingMethodRequiresPickupPoint() {
		var shippingMethod = getSelectedShippingMethod();
		return getShippingMethodEnvelope(shippingMethod).length === 1;
	}

	function getShippingMethodRadioButton(shippingMethod) {
		return $("input[name='shipping_method'][value='" + shippingMethod + "']");
	}

	function getSelectedShippingMethod() {
		return $("input[name='shipping_method']:checked").val();
	}


	function getShippingMethodEnvelope(shippingMethod) {
		return getShippingMethodRadioButton(shippingMethod)
			.parent()
			.parent()
			.find('.packeta-shipping-item-envelope');
	}

	function showPickupPointDescription(shippingMethod, text) {
		getShippingMethodEnvelope(shippingMethod)
			.find('.picked-delivery-place')
			.html(text);
	}


	function getStoredSelectedPickupPoint() {
		var shippingMethod = getSelectedShippingMethod();
		if (typeof selectedPickupPoints[shippingMethod] !== 'undefined') {
			return selectedPickupPoints[shippingMethod];
		}

		return null;
	}

	function disableSubmitButton() {
		toggleSubmitButton(true);
	}

	function enableSubmitButton() {
		toggleSubmitButton(false);
	}

	function toggleSubmitButton(isDisabled) {
		for (var cartType in cartsConfig['buttons']) {
			var $element = $(cartsConfig['buttons'][cartType]);
			if ($element.length) {
				$element.attr('disabled', isDisabled);
				return;
			}
		}

		console.error('No supported confirmation button found.');
	}

	/**
	 * Sets status of "Continue" button according to selected shipping properties.
	 * Button "Continue" is disabled when "zasilkovna" is selected ad shipping method and target branch is not selected.
	 */
	function updateSubmitButtonStatus() {
		var isDisabled = false;

		if (selectedShippingMethodRequiresPickupPoint()) {
			isDisabled = getStoredSelectedPickupPoint() === null;
		}

		toggleSubmitButton(isDisabled);
	}

	/**
	 * Handler for load of selected branch from session.
	 * It is called after initialization of additional HTML elements and JS events during after switch to "Step 4: Delivery Method"
	 * during "checkout".
	 */
	function loadSelectedBranch() {
		$.ajax({
			url: 'index.php?route=extension/module/zasilkovna/loadSelectedBranch',
			type: 'get',
			dataType: 'json',
			success: function(response) {
				if (response.zasilkovna_branch_id !== '') {
					var shippingMethod = response.zasilkovna_shipping_method;
					var selectedBranch = {
						branchId: response.zasilkovna_branch_id,
						branchName: response.zasilkovna_branch_name,
						branchDescription: response.zasilkovna_branch_description,
						carrierId: response.zasilkovna_carrier_id,
						carrierPickupPoint: response.zasilkovna_carrier_pickup_point
					};
					selectedPickupPoints[shippingMethod] = selectedBranch;

					showPickupPointDescription(shippingMethod, selectedBranch.branchDescription);
				}

				updateSubmitButtonStatus();
			},
			error: function(xhr, ajaxOptions, thrownError) {
				loadBranchRunning = false;
				alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
			}
		});
	}

	/**
	 * Handler for save selected branch to session.
	 * It is called after click on "Continue" button in "Step 4: Delivery Method". Another ajax request for save
	 * selected shipping method and comment is sent at the same time.
	 */
	function saveSelectedBranch(shippingMethod, selectedBranch) {
		var dataToSend = {
			zasilkovna_shipping_method: shippingMethod,
			zasilkovna_branch_id: selectedBranch.branchId,
			zasilkovna_branch_name: selectedBranch.branchName,
			zasilkovna_branch_description: selectedBranch.branchDescription,
			zasilkovna_carrier_id: selectedBranch.carrierId,
			zasilkovna_carrier_pickup_point: selectedBranch.carrierPickupPoint
		};

		$.ajax({
			url: 'index.php?route=extension/module/zasilkovna/saveSelectedBranch',
			type: 'post',
			data: dataToSend,
			success: function() {
				updateSubmitButtonStatus();
			},
			error: function(xhr, ajaxOptions, thrownError) {
				alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
			}
		});
	}

	/**
	 * Returns a function, that, as long as it continues to be invoked, will not
	 * be triggered. The function will be called after it stops being called for
	 * N milliseconds. If `immediate` is passed, trigger the function on the
	 * leading edge, instead of the trailing.
	 * @param func
	 * @param wait
	 * @param immediate
	 * @returns {(function(): void)|*}
	 */
	function debounce(func, wait, immediate = false) {
		var timeout;
		return function() {
			var context = this, args = arguments;
			var later = function() {
				timeout = null;
				if (!immediate) func.apply(context, args);
			};
			var callNow = immediate && !timeout;
			clearTimeout(timeout);
			timeout = setTimeout(later, wait);
			if (callNow) func.apply(context, args);
		};
	}

});


<?php
// Heading
$_['heading_title']       	 	= 'Zasilkovna';
$_['heading_weight_rules']		= 'Weight rules';
$_['heading_shipping_rules']	= 'Shipping rules';
$_['heading_orders']			= 'Orders with shipping through Zasilkovna';
$_['text_shipping'] 			= 'Shipping';
$_['text_module_config']		= 'Global configuration';
$_['text_weight_rules_list']	= 'Weight rules list';
$_['text_about_extension']	    = 'About extension';
$_['text_shipping_rules_list']	= 'Shipping rules list';
$_['text_order_list']			= 'Order List';
$_['text_pricing_rules']		= 'Pricing rules';

// Menu
$_['menu_title']				= 'Packeta';
$_['menu_orders']				= 'Orders';
$_['menu_settings']				= 'Settings';
$_['menu_pricing_rules']		= 'Pricing rules';

// Text global
$_['text_success']				= 'Success: You have modified Zasilkovna shipping!';
$_['text_all_countries']		= 'All countries';

// About extension
$_['text_extension_version']	= 'Extension version';
$_['text_cron_url']             = 'URL for updating carriers using cron';

// Text main settings form
$_['entry_title']				= 'Title';
$_['entry_price']				= 'Price';
$_['entry_freeover']			= 'Free over';
$_['entry_country_target'] 		= 'Country target';
$_['entry_show_branches'] 		= 'Show branches';

$_['entry_status'] 				= 'Status';
$_['entry_sort_order'] 			= 'Sort Order';
$_['entry_geo_zone'] 			= 'Geo Zone';
$_['entry_weight_max'] 			= 'Weight max';
$_['entry_api_key'] 			= 'API Key';
$_['entry_tax_class'] 			= 'Tax Class';
$_['entry_default_free_shipping_limit']	= 'Free shipping limit';
$_['entry_default_shipping_price']	= 'Default shipping price';
$_['entry_order_status']		= 'Order status';
$_['entry_cod_methods']			= 'Cash on delivery payment methods';
$_['entry_eshop_identifier']	= 'E-shop identifier';
$_['text_form_item_store_name'] = 'Store name';

// Text weight rules
$_['text_new_weight_rule']		= 'Add weight rule';
$_['text_edit_weight_rule']		= 'Edit weight rule';
$_['text_no_weight_rules']		= 'No weight rules defined';
$_['text_weight_rules_defined'] = 'Rule set defined.';
$_['text_weight_rules_missing'] = 'No rules defined';

$_['entry_wr_max_weight']		= 'Maximal weight';
$_['entry_wr_price']			= 'Price';

// Text shipping rules
$_['text_new_shipping_rule']	= 'Add shipping rule';
$_['text_edit_shipping_rule']	= 'Edit shipping rule';
$_['text_no_shipping_rules']	= 'No shipping rules defined';

$_['entry_sr_target_country']	= 'Target country';
$_['entry_sr_default_price']	= 'Default price';
$_['entry_sr_free_over_limit']	= 'Free shipping limit';
$_['entry_sr_is_enabled']		= 'Enabled';
$_['entry_sr_not_set']          = 'Not set';

// Text order list
$_['entry_ol_order_id']			= 'Order ID';
$_['entry_ol_customer']			= 'Customer';
$_['entry_ol_order_status']		= 'Order Status';
$_['entry_ol_order_date_from']	= 'Start Order date';
$_['entry_ol_order_date_to']	= 'End Order date';
$_['entry_ol_branch_name']		= 'Branch name';
$_['entry_ol_export_date_from']	= 'Start Export Date';
$_['entry_ol_export_date_to']	= 'End Export Date';
$_['entry_ol_export_type']		= 'Export type';

$_['entry_ol_not_exported']		= 'Not exported only';
$_['entry_ol_exported']			= 'Exported only';
$_['entry_ol_all_records']		= 'All records';

// Text buttons
$_['button_export_selected'] 	= 'Export selected orders';
$_['button_export_all'] 		= 'Export all orders';
$_['button_check_installation'] 		= 'Check Installation';

// Text grid columns
$_['column_weight_rule_max_weight']	= 'Max. weight';
$_['column_weight_rule_price']	= 'Price';
$_['column_action'] = 'Action';

$_['column_shipping_rule_target_country']	= 'Target country';
$_['column_shipping_rule_default_price']	= 'Default price';
$_['column_shipping_rule_free_over_limit']	= 'Free shipping limit';
$_['column_shipping_rule_is_enabled']	= 'Enabled';
$_['column_shipping_rule_weight_rules']	= 'Weight rules';

$_['column_order_id'] 			= 'Order ID';
$_['column_customer']			= 'Customer';
$_['column_order_status']		= 'Order Status';
$_['column_order_total']		= 'Total';
$_['column_cod']				= 'COD';
$_['column_order_date']			= 'Order Date';
$_['column_branch_name']		= 'Branch Name';
$_['column_exportDate']			= 'Export Date';

// Error messages
$_['error_permission']			= 'Warning: You do not have permission to modify module Zasilkovna!';
$_['error_missing_param']		= 'The request is incorrect. Required parameter is missing.';
$_['error_invalid_price']		= 'Price must be valid integer number.';
$_['error_invalid_weight']		= 'Weight must be valid integer number.';
$_['error_rules_overlapping']= 'Rule is overlapping with another rule.';
$_['error_duplicate_country_rule'] = 'Rule for this country is already defined.';
$_['error_guzzle_missing'] = 'Guzzle library is not loaded, it will not be possible to update carriers using cron.';
$_['error_key_format']			= 'The API key must be 16 characters long (digits and letters).';
$_['text_important']			= 'Important';
$_['text_api_key_needed_part1']		= 'A valid API key must be filled in for the module to function properly. You can find it in the client section of Packeta';
$_['text_api_key_needed_part2']		= 'here';
$_['text_api_key_needed_part3']		= 'The module cannot be used without the API key filled in. Therefore, the module is now switched off.';

// Help
$_['help_status'] 				= 'Turns modul for Zasilkovna off and on.';
$_['help_api_key'] 				= 'Enter key for communication with Zasilkovna API.';
$_['help_max_weight']			= 'Maximal allowed weight for shipping through Zasilkovna.';
$_['help_default_free_shipping_limit'] = 'Default price limit for free shipping. Used only if limit is not defined in shipping rules.';
$_['help_default_shipping_price'] = 'Default price for shipping. Used only if price from shipping rules or weight rules can\'t be used.';
$_['help_order_status']			= 'List of order statuses displayed in list of Zasilkovna orders.';
$_['help_cod_methods']			= 'Select payment methods considered as cash on delivery.';
$_['help_weight_rules_change']	= 'Management of weight rules for country.';
$_['help_weight_rules_creation']	= 'Click to create weight rules for country.';
$_['help_default_shipping_rule_price'] = 'Default price for shipping. Used only if price from weight rules can\'t be used.';
$_['help_eshop_identifiers']	= 'You can find Your e-shop identifiers in client section of your Zasilkovna account.';

// Upgrade
$_['extension_upgraded'] = 'Extension was upgraded to version %s';
$_['extension_upgrade_failed'] = 'Extension\'s database upgrade failed:';
$_['please_see_log'] = 'Please see OpenCart\'s Error Log, see System - Maintenance - Error Logs.';
$_['extension_may_not_work'] = 'The extension may not work properly at this time.';
$_['error_needs_to_be_resolved'] = 'The error needs to be resolved. Please refresh the page after resolving the error, the update will start again.';

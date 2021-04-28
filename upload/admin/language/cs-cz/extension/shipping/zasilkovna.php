<?php
// Heading
$_['heading_title']       	 	= 'Zásilkovna';
$_['heading_weight_rules']		= 'Váhová pravidla';
$_['heading_shipping_rules']	= 'Pravidla dopravy';
$_['heading_orders']			= 'Objednávky s dopravou přes Zásilkovnu';
$_['text_shipping'] 			= 'Doprava';
$_['text_module_config']		= 'Globální konfigurace';
$_['text_weight_rules_list']	= 'Seznam váhových pravidel';
$_['text_about_extension']	    = 'Informace o rozšíření';
$_['text_shipping_rules_list']	= 'Seznam pravidel dopravy';
$_['text_order_list']			= 'Seznam objednávek';
$_['text_menu_item']			= 'Objednávky Zásilkovny';

// Text global
$_['text_success']         		= 'Nastavení modulu zásilkovna bylo v pořádku uloženo.';
$_['text_all_countries']    	= 'Všechny země';

// About extension
$_['text_extension_version']    = 'Verze rošíření';

// Text main settings form
$_['entry_title']				= 'Název dopravy';
$_['entry_price']				= 'Cena';
$_['entry_freeover']			= 'Zdarma od';
$_['entry_country_target']		= 'Cílová země';
$_['entry_show_branches']		= 'Zobrazit pobočky';
$_['entry_status']				= 'Stav';
$_['entry_sort_order'] 			= 'Pořadí';
$_['entry_geo_zone'] 			= 'Daňová oblast';
$_['entry_weight_max'] 			= 'Max. hmotnost';
$_['entry_api_key'] 			= 'API klíč';
$_['entry_tax_class'] 			= 'Daňová třída';
$_['entry_default_free_shipping_limit']	= 'Limit dopravy zdarma';
$_['entry_default_shipping_price']	= 'Výchozí cena dopravy';
$_['entry_order_status']		= 'Stav objednávky';
$_['entry_cod_methods']			= 'Metody platby na dobírku';
$_['entry_eshop_identifier']	= 'Identifikátor e-shopu';
$_['text_form_item_store_name'] = 'Název obchodu';

// Text weight rules
$_['text_new_weight_rule']		= 'Přidat váhové pravidlo';
$_['text_edit_weight_rule']		= 'Upravit váhové pravidlo';
$_['text_no_weight_rules']		= 'Žádné váhové pravidlo není definováno.';
$_['text_weight_rules_defined'] = 'Sada pravidel definována.';
$_['text_weight_rules_missing'] = 'Pravidla nejsou definována.';

$_['entry_wr_min_weight']		= 'Minimální váha';
$_['entry_wr_max_weight']		= 'Maximální váha';
$_['entry_wr_price']			= 'Cena';

// Text shipping rules
$_['text_new_shipping_rule']	= 'Přidat pravidlo dopravy';
$_['text_edit_shipping_rule']	= 'Upravit pravidlo dopravy';
$_['text_no_shipping_rules']	= 'Žádná pravidla dopravy nejsou definována.';

$_['entry_sr_target_country']	= 'Cílová země';
$_['entry_sr_default_price']	= 'Výchozí cena';
$_['entry_sr_free_over_limit']	= 'Limit dopravy zdarma';
$_['entry_sr_is_enabled']		= 'Povoleno';
$_['entry_sr_not_set']          = 'Nenastaveno';

// Text order list
$_['entry_ol_order_id']			= 'ID objednávky';
$_['entry_ol_customer']			= 'Zákazník';
$_['entry_ol_order_status']		= 'Stav objednávky';
$_['entry_ol_order_date_from']	= 'Počáteční datum objednávky';
$_['entry_ol_order_date_to']	= 'Koncový datum objednávky';
$_['entry_ol_branch_name']		= 'Název pobočky';
$_['entry_ol_export_date_from']	= 'Počáteční datum exportu';
$_['entry_ol_export_date_to']	= 'Koncový datumu exportu';
$_['entry_ol_export_type']		= 'Typ exportu';

$_['entry_ol_not_exported']		= 'Pouze neexportované';
$_['entry_ol_exported']			= 'Pouze exportované';
$_['entry_ol_all_records']		= 'Všechny záznamy';

// Text buttons
$_['button_export_selected'] 	= 'Exportovat vybrané objednávky';
$_['button_export_all'] 		= 'Exportovat všechny objednávky';

// Text grid columns
$_['column_weight_rule_min_weight']	= 'Min. váha';
$_['column_weight_rule_max_weight']	= 'Max. váha';
$_['column_weight_rule_price']	= 'Cena';
$_['column_action'] = 'Akce';

$_['column_shipping_rule_target_country']	= 'Cílová země';
$_['column_shipping_rule_default_price']	= 'Výchozí cena';
$_['column_shipping_rule_free_over_limit']	= 'Limit dopravy zdarma';
$_['column_shipping_rule_is_enabled']	= 'Povoleno';
$_['column_shipping_rule_weight_rules']	= 'Váhová pravidla';

$_['column_order_id'] 			= 'ID ojednávky';
$_['column_customer']			= 'Zákazník';
$_['column_order_status']		= 'Stav objednávky';
$_['column_order_total']		= 'Celkem';
$_['column_cod']				= 'Dobírka';
$_['column_order_date']			= 'Datum objednávky';
$_['column_branch_name']		= 'Název pobočky';
$_['column_exportDate']			= 'Datum exportu';

// Error message
$_['error_permission']         	= 'Varování: Nemáte oprávnění upravovat modul Zásilkovna!';
$_['error_missing_param']		= 'Požadavek je nesprávný. Chybí povinný parametr.';
$_['error_invalid_price']		= 'Cena musí být celé číslo.';
$_['error_invalid_weight']		= 'Váha musí být celé číslo.';
$_['error_invalid_weight_range']= 'Minimální váha musí být menší než maximální váha.';
$_['error_rules_overlapping']	= 'Pravidlo se překrývá s jiným pravidlem.';
$_['error_duplicate_country_rule'] = 'Pravidlo pro tuto zemi je již definováno.';

// Help
$_['help_status'] 				= 'Vypíná a zapíná modul zásilkovny.';
$_['help_api_key'] 				= 'Zadejte správný klíč pro komunikaci s API Zásilkovny.';
$_['help_max_weight']			= 'Maximální povolená hmotnost pro dopravu přes Zásilkovnu.';
$_['help_default_free_shipping_limit'] = 'Výchozí limit pro dopravu zdarma. Použije se pouze pokud není definován limit v pravidle pro dopravu.';
$_['help_default_shipping_price'] = 'Výchozí cena dopravy. Použije se pouze pokud nelze použít cenu z pravidel pro dopravu ani váhových pravidel.';
$_['help_order_status']			= 'Seznam stavů objednávek zobrazovaných v seznamu objednávek Zásilkovny.';
$_['help_cod_methods']			= 'Vyberte platební metody považované za platbu na dobírku.';
$_['help_weight_rules_change']	= 'Správa váhových pravidel pro zemi.';
$_['help_weight_rules_creation']	= 'Klikněte pro vytvoření váhových pravidel pro zemi.';
$_['help_default_shipping_rule_price'] = 'Výchozí cena dopravy. Použije se pouze pokud nelze použít cenu z váhových pravidel.';
$_['help_eshop_identifiers']	= 'Identifikátory Vašich e-shopů najdete v klientské sekci svého účtu Zásilkovny.';

// Upgrade
$_['extension_upgraded'] = 'Modul byl aktualizován na verzi %s';
$_['extension_upgrade_failed'] = 'Aktualizace databáze na novou verzi se nezdařila:';
$_['please_see_log'] = 'Zkontrolujte prosím chybové záznamy OpenCartu, viz Systém - Údržba - Chybová hlášení.';
$_['extension_may_not_work'] = 'Modul nemusí v této chvíli správně fungovat.';
$_['error_needs_to_be_resolved'] = 'Je potřeba chybu vyřešit. Po vyřešení chyby stránku obnovte, aktualizace se spustí znovu.';

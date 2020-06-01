<?php
// Heading
$_['heading_title']       	 	= 'Zásielkovňa';
$_['heading_weight_rules']		= 'Váhové pravidlá';
$_['heading_shipping_rules']	= 'Pravidlá dopravy';
$_['heading_orders']			= 'Objednávky s dopravou cez Zásielkovňu';
$_['text_shipping'] 			= 'Doprava';
$_['text_module_config']		= 'Globálna konfigurácia';
$_['text_weight_rules_list']	= 'Zoznam váhových pravidiel';
$_['text_shipping_rules_list']	= 'Zoznam pravidiel dopravy';
$_['text_order_list']			= 'Zoznam objednávok';
$_['text_menu_item']			= 'Objednávky Zásielkovne';

// Text global
$_['text_success']         		= 'Nastavenie modulu Zásielkovňa bolo v poriadku uložené.';
$_['text_all_countries']    	= 'Všetky krajiny';

// Text main settings form
$_['entry_title']				= 'Názov dopravy';
$_['entry_price']				= 'Cena';
$_['entry_freeover']			= 'Zadarmo od';
$_['entry_country_target']		= 'Cieľová krajina';
$_['entry_show_branches']		= 'Zobraziť pobočky';
$_['entry_status']				= 'Stav';
$_['entry_sort_order'] 			= 'Poradie';
$_['entry_geo_zone'] 			= 'Daňová oblasť';
$_['entry_weight_max'] 			= 'Max. hmotnosť';
$_['entry_api_key'] 			= 'API kľúč';
$_['entry_tax_class'] 			= 'Daňová trieda';
$_['entry_default_free_shipping_limit']	= 'Limit dopravy zadarmo';
$_['entry_default_shipping_price']	= 'Východzia cena dopravy';
$_['entry_order_status']		= 'Stav objednávky';
$_['entry_cod_methods']			= 'Metódy platby na dobierku';
$_['entry_eshop_identifier']	= 'Identifikátor e-shopu';
$_['text_form_item_store_name'] = 'Názov obchodu';

// Text weight rules
$_['text_new_weight_rule']		= 'Pridať váhové pravidlo';
$_['text_edit_weight_rule']		= 'Upraviť váhové pravidlo';
$_['text_no_weight_rules']		= 'Žiadne váhové pravidlo nie je definované.';
$_['text_weight_rules_defined'] = 'Sada pravidiel definované.';
$_['text_weight_rules_missing'] = 'Pravidla nie sú definované.';

$_['entry_wr_min_weight']		= 'Minimálna váha';
$_['entry_wr_max_weight']		= 'Maximálna váha';
$_['entry_wr_price']			= 'Cena';

// Text shipping rules
$_['text_new_shipping_rule']	= 'Pridať pravidlo dopravy';
$_['text_edit_shipping_rule']	= 'Upraviť pravidlo dopravy';
$_['text_no_shipping_rules']	= 'Žiadne pravidlá dopravy nie sú definované.';

$_['entry_sr_target_country']	= 'Cieľová krajina';
$_['entry_sr_default_price']	= 'Východzia cena';
$_['entry_sr_free_over_limit']	= 'Limit dopravy zadarmo';
$_['entry_sr_is_enabled']		= 'Povolené';
$_['entry_sr_not_set']          = 'Nenastavené';

// Text order list
$_['entry_ol_order_id']			= 'ID objednávky';
$_['entry_ol_customer']			= 'Zákazník';
$_['entry_ol_order_status']		= 'Stav objednávky';
$_['entry_ol_order_date_from']	= 'Počiatočný dátum objednávky';
$_['entry_ol_order_date_to']	= 'Koncové dátum objednávky';
$_['entry_ol_branch_name']		= 'Názov pobočky';
$_['entry_ol_export_date_from']	= 'Počiatočný dátum exportu';
$_['entry_ol_export_date_to']	= 'Koncový dátum exportu';
$_['entry_ol_export_type']		= 'Typ exportu';

$_['entry_ol_not_exported']		= 'Len neexportované';
$_['entry_ol_exported']			= 'Len exportované';
$_['entry_ol_all_records']		= 'Všetky záznamy';

// Text buttons
$_['button_export_selected'] 	= 'Exportovať vybrané objednávky';
$_['button_export_all'] 		= 'Exportovať všetky objednávky';

// Text grid columns
$_['column_weight_rule_min_weight']	= 'Min. váha';
$_['column_weight_rule_max_weight']	= 'Max. váha';
$_['column_weight_rule_price']	= 'Cena';
$_['column_action'] = 'Akcia';

$_['column_shipping_rule_target_country']	= 'Cieľová krajina';
$_['column_shipping_rule_default_price']	= 'Východzia cena';
$_['column_shipping_rule_free_over_limit']	= 'Limit dopravy zadarmo';
$_['column_shipping_rule_is_enabled']	= 'Povolené';
$_['column_shipping_rule_weight_rules']	= 'Váhové pravidlá';

$_['column_order_id'] 			= 'ID ojednávky';
$_['column_customer']			= 'Zákazník';
$_['column_order_status']		= 'Stav objednávky';
$_['column_order_total']		= 'Celkom';
$_['column_cod']				= 'Dobierka';
$_['column_order_date']			= 'Dátum objednávky';
$_['column_branch_name']		= 'Názov pobočky';
$_['column_exportDate']			= 'Dátum exportu';

// Error message
$_['error_permission']         	= 'Varovanie: Nemáte oprávnenia upravovať modul Zásielkovňa!';
$_['error_missing_param']		= 'Požiadavok je nesprávny. Chýba povinný parameter.';
$_['error_invalid_country']		= 'Uvedená krajina nie je platná.';
$_['error_invalid_price']		= 'Cena musí byť celé číslo.';
$_['error_invalid_weight']		= 'Váha musí býť celé číslo.';
$_['error_invalid_weight_range']= 'Minimálna váha musí býť menšia než maximálna váha.';
$_['error_rules_overlapping']	= 'Pravidlo sa prekrýva s iným pravidlom.';
$_['error_duplicate_country_rule'] = 'Pravidlo pre túto krajinu je už definované.';

// Help
$_['help_status'] 				= 'Vypína a zapína modul Zásielkovne.';
$_['help_api_key'] 				= 'Zadajte správny kľúč pre komunikáciu s API Zásielkovne.';
$_['help_max_weight']			= 'Maximálna povolená hmotnosť pre dopravu cez Zásielkovňu.';
$_['help_default_free_shipping_limit'] = 'Výchozí limit pre dopravu zadarmo. Použije sa len pokiaľ nie je definovaný limit v pravidle pre dopravu.';
$_['help_default_shipping_price'] = 'Výchozí cena dopravy. Použije se pouze pokud nelze použít cenu z pravidel pro dopravu ani váhových pravidel.';
$_['help_order_status']			= 'Zoznam stavov objednávok zobrazovaných v Zozname objednávok Zásielkovne.';
$_['help_cod_methods']			= 'Vyberte platobné metódy považované za platbu na dobierku.';
$_['help_weight_rules_change']	= 'Správa váhových pravidiel pre krajinu.';
$_['help_weight_rules_creation']	= 'Kliknite pre vytvorenie váhových pravidiel pre krajinu.';
$_['help_default_shipping_rule_price'] = 'Východzia cena dopravy. Použije sa len ak nie je možné použiť cenu z váhových pravidiel.';
$_['help_eshop_identifiers']	= 'Identifikátor Vášho e-shopu nájdete v klientskej sekcii svojho účtu Zásielkovne.';

// Country names
$_['country_cz']				= 'Česká republika';
$_['country_hu']				= 'Maďarsko';
$_['country_pl']				= 'Poľsko';
$_['country_sk']				= 'Slovensko';
$_['country_ro']				= 'Rumunsko';
$_['country_other']				= 'ostatné krajiny';

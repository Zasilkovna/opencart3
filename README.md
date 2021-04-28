[Návod v češtině](#modul-pro-opencart-3)

# Module for Opencart 3

## Download link
[Download version 2.0.4](https://github.com/Zasilkovna/opencart3/archive/v2.0.4.zip)

### Installation
1. Log in to Administration, go to Extensions » Installer and upload the archive with .ocmod.zip extension.
2. Go to Extensions » Extensions page and select the extension type **Shipping**.

![screen1](https://raw.githubusercontent.com/Zasilkovna/opencart3/master/doc/img/01-extensions.png)

3. Install the module by clicking the green (+) button.
4. After installing the module, click the blue **edit** button.

![screen2](https://raw.githubusercontent.com/Zasilkovna/opencart3/master/doc/img/02-extensions-zasilkovna.png)

- On the General Module Configuration page, complete all fields
	- **API key** - you can find it in the [client section » Client support](https://client.packeta.com/en/support/)
	- **Max. weight** - for orders with a higher weight, the shipping method will not be offered in the shopping cart
	- **Default shipping cost** - basic shipping cost, you can define shipping prices for specific countries 
	and weights below
	- **E-shop identifier** - identification of the sender which you have set in the client section of your [sender](https://client.packeta.com/en/senders/).
	- Enable or disable the module

 <a href="https://raw.githubusercontent.com/Zasilkovna/opencart3/master/doc/img/03-configuration-global.png"><img width="50%" src="https://raw.githubusercontent.com/Zasilkovna/opencart3/master/doc/img/03-configuration-global.png"></a>

5. Next, add the transport rules by clicking on the "Edit" link. Enter the shipping cost and free shipping limit here 
for each country.

<a href="https://raw.githubusercontent.com/Zasilkovna/opencart3/master/doc/img/05-shipping-rules-list.png"><img src="https://raw.githubusercontent.com/Zasilkovna/opencart3/master/doc/img/05-shipping-rules-list.png"></a>

6. Click the weight icon to enter the weighting rules for the selected country. Here you can select the shipping price 
for the selected weight range.

<a href="https://raw.githubusercontent.com/Zasilkovna/opencart3/master/doc/img/07-weight-rules-list.png"><img src="https://raw.githubusercontent.com/Zasilkovna/opencart3/master/doc/img/07-weight-rules-list.png"></a>

7. The list of orders for which customers choose to transport via Packeta can be found in the main menu **Sales » Mail Order 
Orders**. Here you can export the marked orders to a csv file, which you then upload to the client section » [Parcels Import](https://client.packeta.com/en/packets-import/upload).

### Upgrading module
1. Log in to Administration, go to Extensions » Installer and upload the archive with .ocmod.zip extension.
2. Go to Extensions » Extensions page and select the extension type **Shipping**.
3. After installing the module, click the blue **edit** button. (required action for database update)
4. Check settings

### Informations about the module

#### Supported languages:
- czech
- english

#### Supported versions:
- Opencart 3.0.0 and newer
- php < 8 and >= 5.6
 
#### Features provided:
- Widget integration in eshop cart
- Set different prices for different target countries
- Setting prices according to weighting rules
- Traffic tax and GEO zone settings
- Free shipping from the specified price or weight of the order
- Export shipments to a csv file that can be imported in the client section
- External pickup point support
- Journal 3 one page checkout support

# Modul pro Opencart 3

### Stažení modulu
[Aktuální verze 2.0.4](https://github.com/Zasilkovna/opencart3/archive/v2.0.4.zip)

## Instalace
1. Přihlaste se do administrace, přejděte na stránku Extensions » Installer a nahrajte archiv s příponou .ocmod.zip.
2. Přejděte na stránku Extensions » Extensions a vyberte typ rozšíření **Shipping**.

<a href="https://raw.githubusercontent.com/Zasilkovna/opencart3/master/doc/img/01-extensions.png"><img src="https://raw.githubusercontent.com/Zasilkovna/opencart3/master/doc/img/01-extensions.png"></a><br><br>   

3. Instalujte modul kliknutím na zelené tlačítko (+).
4. Po nainstalování modulu klikněte na modré tlačítko **edit**

<a href="https://github.com/Zasilkovna/opencart3/blob/master/doc/img/02-extensions-zasilkovna.png?"><img src="https://github.com/Zasilkovna/opencart3/blob/master/doc/img/02-extensions-zasilkovna.png?raw=true"></a><br><br>
  
Na stránce obecná konfigurace modulu vyplňte všechna pole:

- **API klíč** - naleznete jej v [klientské sekci](https://client.packeta.com/cs/support/) » Klientská podpora
- **Max. hmotnost** - u objednávek s větší hmotnostní nebude v košíku přepravní metoda Zásilkovna nabízena
- **Výchozí cena dopravy** - základní cena dopravy, pro konkrétní země a hmotnosti můžete ceny dopravy definovat níže
- **Identifikátor eshopu** - označení odesílatele které máte nastaveno v klientské sekci u vašeho [odesílatele](https://client.packeta.com/cs/senders/).
- Povolte nebo zakažte modul
  
<a href="https://raw.githubusercontent.com/Zasilkovna/opencart3/master/doc/img/03-configuration-global.png"><img width="50%" src="https://raw.githubusercontent.com/Zasilkovna/opencart3/master/doc/img/03-configuration-global.png"></a><br/><br/>
  
5. Dále přidáme přepravní pravidla kliknutím na odkaz *Edit*. Pro jednotlivé země zde zadáte cenu přepravy a limit pro dopravu zdarma.

<a href="https://raw.githubusercontent.com/Zasilkovna/opencart3/master/doc/img/05-shipping-rules-list.png"><img src="https://raw.githubusercontent.com/Zasilkovna/opencart3/master/doc/img/05-shipping-rules-list.png"></a><br><br>
  
6. Kliknutím na ikonu váhy zadáte váhová pravidla pro vybranou zemi. Můžete zde zvolit cenu přepravy pro zvolené váhové rozmezí.

<a href="https://raw.githubusercontent.com/Zasilkovna/opencart3/master/doc/img/07-weight-rules-list.png"><img src="https://raw.githubusercontent.com/Zasilkovna/opencart3/master/doc/img/07-weight-rules-list.png"></a><br><br>

Seznam objednávek u kterých si zákazníci zvolí dopravu přes Zásilkovnu najdete v hlavním menu **Sales » Zásilkovna Orders**. 
Zde můžete označené objednávky exportovat do csv souboru, který poté nahrajete do klientské sekce  » [Import zásilek](https://client.packeta.com/cs/packets-import/upload). 

## Aktualizace modulu
1. Přihlaste se do administrace, přejděte na stránku Extensions » Installer a nahrajte archiv s příponou .ocmod.zip.
2. Přejděte na stránku Extensions » Extensions a vyberte typ rozšíření **Shipping**.
3. Po nainstalování modulu klikněte na modré tlačítko **edit** (nutné pro aktualizaci databáze)
4. Zkontrolujte nastavení

### Informace o modulu

#### Podporované jazyky:

- čeština
- angličtina

#### Podporované verze:

- Opencart 3.0.0 a novější
- php < 8 a >= 5.6

#### Poskytované funkce:

- Integrace widgetu v košíku eshopu
- Nastavení různé ceny pro různé cílové země
- Nastavení cen podle váhových pravidel
- Nastavení daně dopravy a GEO zóny
- Doprava zdarma od zadané ceny nebo hmotnosti objednávky
- Export zásilek do csv souboru, který lze importovat v [klientské sekci](https://client.packeta.com/)
- Podpora výdejních míst cizích dopravců
- Podpora jednokrokového košíku Journal 3

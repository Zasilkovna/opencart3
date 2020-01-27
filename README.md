<h1>Modul pro Opencart 3</h1>
<h2>Instalace</h2>
<ol style="color: black; ">
  <li><a href="https://github.com/Zasilkovna/opencart3/archive/2.0.1.zip">Stáhnout soubor modulu (v 2.0.1) &raquo;</a></li>
  <li>
    Adresáře "admin" a "catalog" z archivu nakopírujte do kořenového adresáře vašeho obchodu opencart.<br>
  </li>
  <li>
    Přihlašte se do administrace, přejděte na stránku Extensions » Extensions a vyberte typ rozšíření "Shipping".<br><br>
    <a href="https://raw.githubusercontent.com/Zasilkovna/opencart3/master/doc/img/01-extensions.png"><img src="https://raw.githubusercontent.com/Zasilkovna/opencart3/master/doc/img/01-extensions.png"></a><br><br>   
  </li>
  <li>
  	Instalujte modul kliknutím na zelené tlačítko (+).
  </li>
  <li>
    Po nainstalování modulu klikněte na modré tlačítko <i><strong>edit</strong></i><br><br>
    <a href="https://github.com/Zasilkovna/opencart3/blob/master/doc/img/02-extensions-zasilkovna.png?"><img src="https://github.com/Zasilkovna/opencart3/blob/master/doc/img/02-extensions-zasilkovna.png?raw=true"></a><br><br>
  </li>
  	Na stránce obecná konfigurace modulu vyplňte všechna pole
  <ul>
  	<li>API klíč - naleznete jej v <a href="https://client.packeta.com/cs/support/">klientské sekci » Klientská podpora</a></li>
  	<li>Max. hmotnost - u objednávek s větší hmotnostní nebude v košíku přepravní metoda Zásilkovna nabízena</li>
  	<li>Výchozí cena dopravy - základní cena dopravy, pro konkrétní země a hmotnosti můžete ceny dopravy definovat níže</li>
  	<li>Povolení nebo zakázání modulu</li>
  	<li>Identifikátor eshopu - označení odesílatele které máte nastaveno v klientské sekci u vašeho <a href="https://client.packeta.com/cs/senders/">odesílatele</a>.<br><br></li>
  </ul>
  <a href="https://raw.githubusercontent.com/Zasilkovna/opencart3/master/doc/img/03-configuration-global.png"><img width="50%" src="https://raw.githubusercontent.com/Zasilkovna/opencart3/master/doc/img/03-configuration-global.png"></a><br/><br/>
  <li>Dále přidáme přepravní pravidla kliknutím na odkaz "Edit". Pro jednotlivé země zde zadáte cenu přepravy a limit pro dopravu zdarma. <br><br>
  <a href="https://raw.githubusercontent.com/Zasilkovna/opencart3/master/doc/img/05-shipping-rules-list.png"><img src="https://raw.githubusercontent.com/Zasilkovna/opencart3/master/doc/img/05-shipping-rules-list.png"></a><br><br>
  </li>
  <li>
  	Kliknutím na ikonu váhy zadáte váhová pravidla pro vybranou zemi. Můžete zde zvolit cenu přepravy pro zvolené váhové rozmezí. <br><br>
  <a href="https://raw.githubusercontent.com/Zasilkovna/opencart3/master/doc/img/07-weight-rules-list.png"><img src="https://raw.githubusercontent.com/Zasilkovna/opencart3/master/doc/img/07-weight-rules-list.png"></a><br><br>
  </li>
  <li>
    Seznam objednávek u kterých si zákazníci zvolí dopravu přes Zásilkovnu najdete v hlavním menu <strong>Sales » Zásilkovna Orders</strong>. 
    Zde můžete označené objednávky exportovat do csv souboru, který poté nahrajete do klientské sekce  » Import zásilek. 
  </li>  
</ol>
<h2>Informace o modulu</h2>
<p>Podporované jazyky:</p>
<ul>
  <li>čeština</li>
  <li>angličtina</li>
</ul>
<h3>Podporované verze:</h3>
<ul>
  <li>Opencart 3.0.0 a novější</li>
</ul>
<h3>Poskytované funkce:</h3>
<ul>
  <li>Integrace widgetu v košíku eshopu</li>
  <li>Nastavení různé ceny pro různé cílové země</li>
  <li>Nastavení cen podle váhových pravidel</li>
  <li>Nastavení daně dopravy a GEO zóny</li>
  <li>Doprava zdarma od zadané ceny nebo hmotnosti objednávky</li>
  <li>Export zásilek do csv souboru, který lze importovat v <a href="https://client.packeta.com/">klientské sekci</a></li>
</ul>

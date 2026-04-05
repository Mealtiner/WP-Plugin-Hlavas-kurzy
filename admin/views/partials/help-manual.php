<div class="hlavas-docs-section" id="hlavas-info-manual">
	<h2>Dokumentace — jak plugin funguje</h2>
	<div class="hlavas-docs-intro">
		Plugin HLAVAS – Správa termínů kurzů a zkoušek slouží ke správě termínů kurzů a zkoušek na webu HLAVAS.cz.
		Propojuje vlastní administraci termínů s přihlašovacími formuláři ve Fluent Forms — automaticky plní výběrové
		seznamy termínů, hlídá kapacitu a sbírá přihlášky. Vše bez nutnosti ručně upravovat formuláře.
	</div>

	<div class="hlavas-docs-grid">
		<details class="hlavas-doc-item">
			<summary>
				<span class="hlavas-doc-item-icon">🏷️</span>
				Typy kvalifikací (kurzů)
			</summary>
			<div class="hlavas-doc-item-body">
				<p>
					Typ kvalifikace je základní stavební kámen pluginu. Určuje, o jaký druh kurzu nebo zkoušky jde,
					jaké má akreditační číslo a ke kterému Fluent Forms formuláři se má daný typ napojit.
				</p>
				<ul>
					<li><strong>Název typu</strong> slouží pro přehled v administraci a při filtrování.</li>
					<li><strong>Číslo akreditace</strong> se používá v názvech zkoušek, ve výpisech i při párování historických dat.</li>
					<li><strong>Form ID pro kurz / zkoušku</strong> určuje, do kterého formuláře se mají synchronizovat termíny.</li>
					<li><strong>Kurzy / Zkoušky</strong> říká, zda typ obsahuje obě varianty, nebo jen jednu z nich.</li>
				</ul>
				<p>
					Pokud máš více kvalifikací, doporučený postup je založit každý typ zvlášť a teprve potom k němu vytvářet
					jednotlivé termíny. Díky tomu je pozdější synchronizace i filtrování účastníků výrazně spolehlivější.
				</p>
			</div>
		</details>

		<details class="hlavas-doc-item">
			<summary>
				<span class="hlavas-doc-item-icon">🗓️</span>
				Termíny kurzů a zkoušek
			</summary>
			<div class="hlavas-doc-item-body">
				<p>
					Každý termín má vlastní typ, datum, kapacitu, interní klíč <code>term_key</code>, label pro uživatele
					a administrativní název. U kurzů bývá label kratší, u zkoušek se může generovat automaticky z akreditace a data.
				</p>
				<ul>
					<li><strong>Kapacita</strong> říká maximální počet účastníků.</li>
					<li><strong>Uzávěrka</strong> určuje, do kdy má být termín nabízen ve formuláři.</li>
					<li><strong>Web / Aktivní / Archiv</strong> ovlivňují, zda se termín nabídne na frontendu, v exportech a v synchronizaci.</li>
					<li><strong>Pořadí</strong> slouží k ručnímu seřazení termínů, pokud nestačí jen datum.</li>
				</ul>
				<p>
					Interní klíč termínu by po prvním ostrém použití už neměl být ručně měněn. Na něj se váže synchronizace,
					párování účastníků i některé exporty.
				</p>
			</div>
		</details>

		<details class="hlavas-doc-item">
			<summary>
				<span class="hlavas-doc-item-icon">🔄</span>
				Synchronizace s Fluent Forms
			</summary>
			<div class="hlavas-doc-item-body">
				<p>
					Synchronizace je pracovní most mezi HLAVAS pluginem a Fluent Forms. Plugin umí termíny z formulářů načíst
					do sebe a zároveň umí svoje termíny poslat zpět do konkrétních FF polí.
				</p>
				<h4>Co synchronizace řeší</h4>
				<ul>
					<li>hledání polí <code>termin_kurz</code> a <code>termin_zkouska</code>,</li>
					<li>zápis value / label dvojic podle zvoleného režimu,</li>
					<li>propis kapacit do formulářového inventáře,</li>
					<li>diagnostiku, zda formulář obsahuje očekávaná pole a pod jakým názvem.</li>
				</ul>
				<div class="hlavas-doc-tip">
					Na stránce Synchronizace vždy nejdřív zkontroluj nalezená pole a až potom spouštěj zápis do formuláře.
				</div>
			</div>
		</details>

		<details class="hlavas-doc-item">
			<summary>
				<span class="hlavas-doc-item-icon">📊</span>
				Kapacita a obsazenost
			</summary>
			<div class="hlavas-doc-item-body">
				<p>
					Obsazenost se počítá z odeslaných Fluent Forms entries, z ručního párování účastníků a z případných
					legacy oprav. Proto se může lišit od prostého počtu termínů ve formuláři, pokud historická data ještě nejsou srovnaná.
				</p>
				<ul>
					<li><strong>Přihlášeno</strong> je počet skutečně přiřazených účastníků k danému termínu.</li>
					<li><strong>Zbývá</strong> je rozdíl mezi kapacitou a aktuálním počtem účastníků.</li>
					<li><strong>Obsazení</strong> je procentuální přehled pro rychlou orientaci.</li>
				</ul>
				<p>
					Pokud jsou ve Fluent Forms starší záznamy, které vznikly ještě před nasazením pluginu, vyplatí se použít
					legacy opravu a následný rebuild účastníků a kapacit.
				</p>
			</div>
		</details>

		<details class="hlavas-doc-item">
			<summary>
				<span class="hlavas-doc-item-icon">👁️</span>
				Skrývání termínů na frontendu
			</summary>
			<div class="hlavas-doc-item-body">
				<p>
					Termín můžeš skrýt bez smazání. K tomu slouží přepínač viditelnosti a stav aktivní / archiv.
					Přihlášky tím nemažeš a historická data zůstávají zachovaná.
				</p>
				<ul>
					<li><strong>Web</strong> rozhoduje, zda je termín veřejně nabízen.</li>
					<li><strong>Aktivní</strong> říká, zda se s termínem pracuje v běžném provozu.</li>
					<li><strong>Archiv</strong> slouží pro přesunutí starých termínů mimo hlavní pracovní tok.</li>
				</ul>
				<p>
					Skrytí termínu je bezpečnější než mazání, protože kapacity, výpisy účastníků i exporty stále znají jeho historii.
				</p>
			</div>
		</details>

		<details class="hlavas-doc-item">
			<summary>
				<span class="hlavas-doc-item-icon">📋</span>
				Výpis účastníků
			</summary>
			<div class="hlavas-doc-item-body">
				<p>
					Stránka Účastníci zobrazuje entries z navázaných formulářů a snaží se je spárovat s aktuálními termíny v pluginu.
					U nových záznamů to bývá automatické, u legacy dat může být potřeba ruční zásah.
				</p>
				<ul>
					<li>Filtruješ podle akreditace, typu přihlášky i konkrétního termínu.</li>
					<li>U nepárovaných záznamů lze ručně vybrat správný termín.</li>
					<li>Stejným prvkem lze i už spárovaného účastníka přesunout na jiný termín.</li>
					<li>Výsledek se okamžitě promítne do kapacity a obsazenosti.</li>
				</ul>
			</div>
		</details>

		<details class="hlavas-doc-item">
			<summary>
				<span class="hlavas-doc-item-icon">⚙️</span>
				Nastavení pluginu
			</summary>
			<div class="hlavas-doc-item-body">
				<p>
					Stránka Nastavení je určená pro provozní konfiguraci pluginu. Patří sem fallback Form ID, výchozí režim
					synchronizace, debug režim, reportovací e-mail a pokročilé ruční mapování polí.
				</p>
				<ul>
					<li><strong>Výchozí Fluent Form ID</strong> slouží jako fallback, pokud typ kvalifikace nemá vlastní formulář.</li>
					<li><strong>Výchozí režim synchronizace</strong> předvyplní exportní akce.</li>
					<li><strong>Ruční mapování</strong> pomůže tam, kde FF pole používají jiná interní jména.</li>
					<li><strong>Shortcodes</strong> jsou zdokumentované v Nastavení, protože jde o konfigurační a implementační vrstvu frontend výstupů.</li>
				</ul>
			</div>
		</details>

		<details class="hlavas-doc-item">
			<summary>
				<span class="hlavas-doc-item-icon">📝</span>
				Jak správně nastavit formulář ve Fluent Forms
			</summary>
			<div class="hlavas-doc-item-body">
				<p>
					Formulář by měl obsahovat samostatná pole pro kurz a zkoušku a podmíněně je zobrazovat podle typu přihlášky.
					Plugin standardně očekává pole <code>typ_prihlasky</code>, <code>termin_kurz</code> a <code>termin_zkouska</code>.
				</p>
				<ol class="hlavas-doc-steps">
					<li>Vytvoř pole pro volbu typu přihlášky.</li>
					<li>Přidej pole termínu kurzu a termínu zkoušky.</li>
					<li>Nastav podmínky zobrazení podle vybraného typu přihlášky.</li>
					<li>Zkontroluj inventář a zobrazení textu se zbývající kapacitou.</li>
					<li>Pokud formulář používá jiná field names, doplň ruční mapování v Nastavení.</li>
				</ol>
			</div>
		</details>

		<details class="hlavas-doc-item">
			<summary>
				<span class="hlavas-doc-item-icon">📊</span>
				Reporty a exporty
			</summary>
			<div class="hlavas-doc-item-body">
				<p>
					Na stránkách Přehled, Termíny kurzů, Termíny zkoušek, Obsazenost a Účastníci jsou k dispozici exporty do CSV a XLS,
					odeslání dat na e-mail i tisková verze výstupu.
				</p>
				<ul>
					<li>Exporty respektují aktuální filtry a řazení.</li>
					<li>E-mail reportu se nastavuje centrálně v Nastavení pluginu.</li>
					<li>Tisk používá čistou výstupní šablonu, aby se přehledy nerozsypaly při tisku nebo do PDF.</li>
				</ul>
			</div>
		</details>

		<details class="hlavas-doc-item">
			<summary>
				<span class="hlavas-doc-item-icon">🧾</span>
				Aktivitní log
			</summary>
			<div class="hlavas-doc-item-body">
				<p>
					Plugin si vede aktivitní log do souboru v adresáři pluginu. Zapisuje se čas, uživatel a provedená akce,
					například změny termínů, synchronizace, importy, exporty nebo servisní zásahy.
				</p>
				<ul>
					<li>Log je dostupný na stránce <strong>Servis / Diagnostika</strong>.</li>
					<li>Lze ho zobrazit, stáhnout jako text a v případě potřeby vymazat.</li>
					<li>Slouží hlavně pro dohledání chyb, změn a problémových operací v administraci.</li>
				</ul>
			</div>
		</details>

		<details class="hlavas-doc-item">
			<summary>
				<span class="hlavas-doc-item-icon">🚀</span>
				První nastavení po instalaci
			</summary>
			<div class="hlavas-doc-item-body">
				<ol class="hlavas-doc-steps">
					<li>Založ nebo zkontroluj typy kvalifikací.</li>
					<li>Doplň Form ID pro kurzy a zkoušky.</li>
					<li>Rozhodni, zda chceš termíny nejdřív načíst z FF nebo je spravovat přímo v pluginu.</li>
					<li>Ověř na stránce Synchronizace nalezená pole a náhled termínů.</li>
					<li>Teprve potom proveď první ostrou synchronizaci do formuláře.</li>
				</ol>
				<div class="hlavas-doc-tip">
					Při přebírání staršího webu nejdřív importuj termíny z FF do pluginu. Přímý export do formuláře dělej až ve chvíli,
					kdy máš zkontrolované názvy, kapacity a návaznosti.
				</div>
			</div>
		</details>

		<details class="hlavas-doc-item">
			<summary>
				<span class="hlavas-doc-item-icon">📦</span>
				Migrace na jiný web
			</summary>
			<div class="hlavas-doc-item-body">
				<p>
					Pro přesun pluginu na jiný web použij export backupu v Servis / Diagnostika, přenes pluginové soubory
					a potom na cíli proveď import zálohy. Fluent Forms entries se řeší samostatně v rámci FF.
				</p>
				<ul>
					<li>Export backupu ukládá nastavení, typy kvalifikací a termíny.</li>
					<li>Po importu zkontroluj Form ID, protože na jiném webu mohou mít formuláře jiné identifikátory.</li>
					<li>Pokud převádíš i starší záznamy, počítej s následnou kontrolou legacy dat a případným rebuildem.</li>
				</ul>
			</div>
		</details>
	</div>
</div>

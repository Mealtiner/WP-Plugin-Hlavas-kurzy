<div class="hlavas-faq-section" id="hlavas-info-faq">
	<h2>Nejčastější otázky (FAQ)</h2>
	<div class="hlavas-faq-list">
		<details class="hlavas-faq-item">
			<summary>Přidal jsem termín, ale ve formuláři se nezobrazuje. Proč?</summary>
			<div class="hlavas-faq-answer">
				<p>Nejčastější důvody jsou tři: termín není aktivní nebo viditelný, není navázaný na správný typ kvalifikace, nebo ještě neproběhla synchronizace do Fluent Forms.</p>
				<p>Zkontroluj v detailu termínu typ, viditelnost na webu, archivaci a pak otevři stránku <strong>Synchronizace s Fluent Forms</strong>, ověř nalezená pole a spusť export do formuláře.</p>
			</div>
		</details>

		<details class="hlavas-faq-item">
			<summary>Jak skryju termín z formuláře, aniž smažu přihlášky?</summary>
			<div class="hlavas-faq-answer">
				<p>Termín nemaž. Místo toho ho vypni pro web, případně ho archivuj. Přihlášky ve Fluent Forms tím zůstanou zachované a stále se budou zobrazovat v účastnících a reportech.</p>
				<p>Po změně stavu proveď synchronizaci do FF, aby se termín stáhl i z formuláře.</p>
			</div>
		</details>

		<details class="hlavas-faq-item">
			<summary>Změnil jsem kapacitu termínu. Propíše se to do formuláře?</summary>
			<div class="hlavas-faq-answer">
				<p>Ano, ale až po synchronizaci do Fluent Forms. Kapacita vedená v pluginu je zdrojová hodnota, kterou je potřeba zapsat i do inventáře pole ve formuláři.</p>
				<p>Pokud používáš legacy data nebo ruční přesuny účastníků, vyplatí se po větších změnách spustit i rebuild účastníků a kapacit.</p>
			</div>
		</details>

		<details class="hlavas-faq-item">
			<summary>Zobrazuje se obsazenost špatně (jiný počet než skutečnost). Proč?</summary>
			<div class="hlavas-faq-answer">
				<p>Obvykle jde o nepárované legacy entries, staré textové hodnoty termínů nebo ručně přesunuté účastníky, které ještě nejsou propsané do FF i do interního párování pluginu.</p>
				<p>Řešení bývá: oprava legacy entries, rebuild účastníků a kapacit a případně ruční dopárování konkrétních účastníků na stránce <strong>Účastníci</strong>.</p>
			</div>
		</details>

		<details class="hlavas-faq-item">
			<summary>Synchronizace hlásí, že pole nebylo nalezeno. Co s tím?</summary>
			<div class="hlavas-faq-answer">
				<p>Plugin formulář prohledává podle interního <code>name</code>, admin labelu i viditelného labelu. Pokud nic nenajde, pole je pravděpodobně pojmenované jinak, je zanořené v jiné struktuře, nebo v daném formuláři opravdu chybí.</p>
				<p>Otevři na stránce synchronizace přehled všech nalezených polí a případně doplň ruční mapování v <strong>Nastavení</strong>.</p>
			</div>
		</details>

		<details class="hlavas-faq-item">
			<summary>Mám více typů kvalifikací. Potřebuji pro každý jiný formulář?</summary>
			<div class="hlavas-faq-answer">
				<p>Nemusíš. Jeden formulář může obsluhovat více typů kvalifikací. Záleží hlavně na tom, jestli mají stejné pole, stejné workflow a stejný obsah přihlášky.</p>
				<p>Pokud se formuláře výrazně liší, doporučené je mít pro různé kvalifikace samostatná Form ID a napojit je přímo v typech kurzů.</p>
			</div>
		</details>

		<details class="hlavas-faq-item">
			<summary>Co je term_key a proč ho nesmím měnit po synchronizaci?</summary>
			<div class="hlavas-faq-answer">
				<p><code>term_key</code> je interní identifikátor termínu. Právě na něj se váže nový režim synchronizace, párování účastníků a některé frontend shortcodes.</p>
				<p>Pokud ho změníš u termínu, který už byl použit ve formuláři nebo v entries, může se rozbít párování historických dat a kapacit.</p>
			</div>
		</details>

		<details class="hlavas-faq-item">
			<summary>Jak exportovat seznam přihlášených na konkrétní termín?</summary>
			<div class="hlavas-faq-answer">
				<p>Na stránce <strong>Účastníci</strong> nejdřív vyfiltruj konkrétní termín a potom použij export do CSV nebo XLS. Stejně můžeš data i vytisknout nebo odeslat na nastavený reportovací e-mail.</p>
			</div>
		</details>

		<details class="hlavas-faq-item">
			<summary>Přihlašovací formulář blokuje přihlášení, i když termín ještě není plný. Co dělat?</summary>
			<div class="hlavas-faq-answer">
				<p>Nejčastěji je problém v nesrovnané kapacitě mezi pluginem a FF inventářem nebo v podmínkách samotného formuláře. Pomůže znovu zapsat termíny do FF a zkontrolovat, že se správně propsala kapacita i remaining quantity.</p>
				<p>Pokud jde o starší web, zkontroluj i legacy záznamy a jejich dopad na obsazenost.</p>
			</div>
		</details>

		<details class="hlavas-faq-item">
			<summary>Deaktivoval jsem Fluent Forms. Přestal plugin fungovat?</summary>
			<div class="hlavas-faq-answer">
				<p>Ano, alespoň jeho napojená část. HLAVAS umí dál držet termíny a nastavení, ale bez Fluent Forms nefunguje synchronizace, entries, obsazenost ani návazná práce s přihláškami.</p>
				<p>Po znovuaktivaci FF je vhodné otevřít stránku synchronizace a ověřit, že plugin znovu vidí formuláře i entries.</p>
			</div>
		</details>

		<details class="hlavas-faq-item">
			<summary>Jak zálohovat a přenést plugin na jiný web?</summary>
			<div class="hlavas-faq-answer">
				<p>Použij export backupu na stránce <strong>Servis / Diagnostika</strong>. Ten přenese provozní nastavení, termíny a typy kvalifikací. Po importu na cílovém webu zkontroluj Form ID a případně proveď novou synchronizaci.</p>
			</div>
		</details>

		<details class="hlavas-faq-item">
			<summary>Kde najdu logy a jak zjistím, kdo co změnil?</summary>
			<div class="hlavas-faq-answer">
				<p>Aktivitní log najdeš na stránce <strong>Servis / Diagnostika</strong>. Je uložený i jako soubor v pluginu a obsahuje čas, uživatele a popis provedené akce.</p>
			</div>
		</details>
	</div>
</div>

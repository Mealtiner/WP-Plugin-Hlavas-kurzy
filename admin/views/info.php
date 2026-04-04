<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var array<string, string> $plugin_info */
/** @var array<int, array<string, mixed>> $form_registry */

$grouped_forms = [];

foreach ( $form_registry as $form_item ) {
	$form_id = (int) ( $form_item['form_id'] ?? 0 );

	if ( $form_id <= 0 ) {
		continue;
	}

	if ( ! isset( $grouped_forms[ $form_id ] ) ) {
		$grouped_forms[ $form_id ] = [
			'form_id'    => $form_id,
			'form_title' => (string) ( $form_item['form_title'] ?? '' ),
			'exists'     => ! empty( $form_item['exists'] ),
			'status'     => (string) ( $form_item['status'] ?? '' ),
			'usages'     => [],
		];
	}

	$grouped_forms[ $form_id ]['usages'][] = (string) ( $form_item['usage'] ?? '' );
}
?>
<div class="wrap hlavas-terms-wrap">
	<h1>Nápověda / O pluginu</h1>
	<p>Technické informace o pluginu, kompletní uživatelská dokumentace a odpovědi na nejčastější otázky.</p>

	<!-- ==================== TECHNICKÁ TABULKA ==================== -->
	<table class="widefat striped" style="max-width: 980px;">
		<tbody>
			<tr>
				<th style="width: 240px;">Název pluginu</th>
				<td><?php echo esc_html( $plugin_info['name'] ); ?></td>
			</tr>
			<tr>
				<th>Verze</th>
				<td><?php echo esc_html( $plugin_info['version'] ); ?></td>
			</tr>
			<tr>
				<th>Slug / text domain</th>
				<td><code><?php echo esc_html( $plugin_info['slug'] ); ?></code> / <code><?php echo esc_html( $plugin_info['text_domain'] ); ?></code></td>
			</tr>
			<tr>
				<th>Autor</th>
				<td><a href="<?php echo esc_url( $plugin_info['author_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $plugin_info['author'] ); ?></a></td>
			</tr>
			<tr>
				<th>E-mail autora</th>
				<td><a href="mailto:<?php echo esc_attr( $plugin_info['author_email'] ); ?>"><?php echo esc_html( $plugin_info['author_email'] ); ?></a></td>
			</tr>
			<tr>
				<th>Zadavatel / organizace</th>
				<td><?php echo esc_html( $plugin_info['organization'] ); ?></td>
			</tr>
			<tr>
				<th>E-mail organizace</th>
				<td><a href="mailto:<?php echo esc_attr( $plugin_info['organization_email'] ); ?>"><?php echo esc_html( $plugin_info['organization_email'] ); ?></a></td>
			</tr>
			<tr>
				<th>Projekt / URL</th>
				<td>
					<a href="<?php echo esc_url( $plugin_info['plugin_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $plugin_info['plugin_url'] ); ?></a>
					<span class="description">nasazený web</span>
				</td>
			</tr>
			<tr>
				<th>Licence</th>
				<td><?php echo esc_html( $plugin_info['license'] ); ?></td>
			</tr>
			<tr>
				<th>Minimální kompatibilita</th>
				<td>WordPress <?php echo esc_html( $plugin_info['min_wp'] ); ?>+, PHP <?php echo esc_html( $plugin_info['min_php'] ); ?>+</td>
			</tr>
			<tr>
				<th>Aktuální prostředí</th>
				<td>WordPress <?php echo esc_html( $plugin_info['current_wp'] ); ?>, PHP <?php echo esc_html( $plugin_info['current_php'] ); ?></td>
			</tr>
			<tr>
				<th>Databázová tabulka</th>
				<td><code><?php echo esc_html( $plugin_info['table'] ); ?></code></td>
			</tr>
			<tr>
				<th>Tabulka typů kvalifikací</th>
				<td><code><?php echo esc_html( $plugin_info['types_table'] ); ?></code></td>
			</tr>
			<tr>
				<th>Propojené Fluent Forms formuláře</th>
				<td>
					<?php if ( empty( $grouped_forms ) ) : ?>
						<span class="hlavas-subline">Žádný formulář zatím není propojen.</span>
					<?php else : ?>
						<ul style="margin: 0; padding-left: 18px;">
							<?php foreach ( $grouped_forms as $form_id => $grouped_form ) : ?>
								<?php
								$form_url = add_query_arg(
									[
										'page'      => 'fluent_forms',
										'form_id'   => $form_id,
										'route'     => 'settings',
										'sub_route' => 'form_settings',
									],
									admin_url( 'admin.php' )
								) . '#basic_settings';
								?>
								<li>
									<a href="<?php echo esc_url( $form_url ); ?>">ID <?php echo esc_html( (string) $form_id ); ?></a>
									<?php if ( ! empty( $grouped_form['form_title'] ) ) : ?>
										<strong><?php echo esc_html( ' – ' . (string) $grouped_form['form_title'] ); ?></strong>
									<?php endif; ?>
									<?php if ( ! empty( $grouped_form['status'] ) ) : ?>
										<span class="hlavas-subline">(stav: <?php echo esc_html( (string) $grouped_form['status'] ); ?>)</span>
									<?php endif; ?>
									<div class="hlavas-subline">Využití: <?php echo esc_html( implode( ', ', array_unique( array_filter( $grouped_form['usages'] ) ) ) ); ?></div>
									<?php if ( empty( $grouped_form['exists'] ) ) : ?>
										<div class="hlavas-subline hlavas-status-no">Formulář v databázi Fluent Forms nebyl nalezen.</div>
									<?php endif; ?>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th>Debug režim</th>
				<td><?php echo esc_html( $plugin_info['debug_mode'] ); ?></td>
			</tr>
			<tr>
				<th>Plugin file</th>
				<td><code><?php echo esc_html( $plugin_info['plugin_file'] ); ?></code></td>
			</tr>
			<tr>
				<th>Plugin directory</th>
				<td><code><?php echo esc_html( $plugin_info['plugin_directory'] ); ?></code></td>
			</tr>
		</tbody>
	</table>

	<p style="margin-top: 16px;">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-settings' ) ); ?>" class="button button-primary">Otevřít nastavení pluginu</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=hlavas-terms-sync' ) ); ?>" class="button">Přejít na synchronizaci</a>
	</p>

	<!-- ==================== DOKUMENTACE ==================== -->
	<div class="hlavas-docs-section">
		<h2>📖 Dokumentace — jak plugin funguje</h2>

		<div class="hlavas-docs-intro">
			Plugin <strong>HLAVAS – Správa termínů kurzů a zkoušek</strong> slouží ke správě termínů kurzů a zkoušek na webu HLAVAS.cz.
			Propojuje vlastní administraci termínů s přihlašovacími formuláři ve Fluent Forms — automaticky plní výběrové seznamy termínů,
			hlídá kapacitu a sbírá přihlášky. Vše bez nutnosti ručně upravovat formuláře.
		</div>

		<div class="hlavas-docs-grid">

			<!-- TYPY KVALIFIKACÍ -->
			<details class="hlavas-doc-item">
				<summary>
					<span class="hlavas-doc-item-icon">🏷️</span>
					Typy kvalifikací (kurzů)
				</summary>
				<div class="hlavas-doc-item-body">
					<p>Typ kvalifikace je kategorie, do které termíny patří — například <em>Vedoucí oddílů (75-007-M)</em> nebo <em>Zdravotník (75-001-T)</em>.</p>
					<h4>Co typ kvalifikace obsahuje</h4>
					<ul>
						<li><strong>Název a číslo akreditace</strong> — zobrazuje se ve výpisech a reportech</li>
						<li><strong>Form ID pro kurzy</strong> — Fluent Forms formulář, do jehož pole <code>termin_kurz</code> se synchronizují termíny kurzů tohoto typu</li>
						<li><strong>Form ID pro zkoušky</strong> — stejně, ale pro pole <code>termin_zkouska</code></li>
						<li><strong>Příznaky</strong> — jestli má typ kurzy, zkoušky, zda je akreditovaný</li>
					</ul>
					<h4>Kde nastavit</h4>
					<p>Menu <strong>Typy kurzů</strong> → tlačítko <em>Přidat typ</em> nebo klik na existující typ.</p>
					<div class="hlavas-doc-tip">💡 Jeden formulář může obsluhovat více typů kvalifikací zároveň. Plugin je sloučí při synchronizaci automaticky.</div>
				</div>
			</details>

			<!-- TERMÍNY -->
			<details class="hlavas-doc-item">
				<summary>
					<span class="hlavas-doc-item-icon">📅</span>
					Termíny kurzů a zkoušek
				</summary>
				<div class="hlavas-doc-item-body">
					<p>Termín je konkrétní datum (nebo rozsah dat), kdy kurz nebo zkouška proběhne. Každý termín má:</p>
					<ul>
						<li><strong>Typ</strong> — kurz nebo zkouška</li>
						<li><strong>Klíč (term_key)</strong> — automaticky generovaný unikátní identifikátor, např. <code>kurz_2026_05_15_17</code>. Neměnit po synchronizaci!</li>
						<li><strong>Label</strong> — lidsky čitelný popis, např. <em>kurz: 15. – 17. května 2026</em></li>
						<li><strong>Datum začátku a konce</strong></li>
						<li><strong>Uzávěrka přihlášek</strong> — datum, po kterém termín přestane být nabízen ve formuláři</li>
						<li><strong>Kapacita</strong> — maximální počet přihlášených</li>
						<li><strong>Viditelnost a aktivita</strong> — řídí, zda se termín zobrazí ve formuláři</li>
					</ul>
					<h4>Viditelnost vs. aktivita vs. archiv</h4>
					<ul>
						<li><strong>Viditelný</strong> — zobrazuje se na frontendu ve formuláři po synchronizaci</li>
						<li><strong>Aktivní</strong> — zahrnut do provozních reportů a přehledů</li>
						<li><strong>Archivován</strong> — skryt z přehledů, data zachována</li>
					</ul>
					<div class="hlavas-doc-warning">⚠️ Skrytí termínu (viditelnost = Ne) ho odstraní z formuláře až po provedení synchronizace. Samo o sobě to formulář nezmění.</div>
				</div>
			</details>

			<!-- SYNCHRONIZACE -->
			<details class="hlavas-doc-item">
				<summary>
					<span class="hlavas-doc-item-icon">🔄</span>
					Synchronizace s Fluent Forms
				</summary>
				<div class="hlavas-doc-item-body">
					<p>Synchronizace přenese aktuální seznam termínů (viditelné, aktivní, budoucí) do výběrových polí ve Fluent Forms formuláři. Zároveň nastaví kapacitu jako zásobu (inventory).</p>
					<h4>Co synchronizace dělá</h4>
					<ul>
						<li>Najde v každém nastaveném formuláři pole <code>termin_kurz</code> nebo <code>termin_zkouska</code></li>
						<li>Přepíše jejich volby (options) aktuálními termíny</li>
						<li>Nastaví inventory_settings s kapacitami termínů</li>
						<li>Zapíše datum poslední synchronizace</li>
					</ul>
					<h4>Co synchronizace nedělá</h4>
					<ul>
						<li>Nesmaže přijaté přihlášky</li>
						<li>Nezmění podmíněnou logiku ani jiná nastavení formuláře</li>
						<li>Neovlivní záznamy v tabulce submissions</li>
					</ul>
					<h4>Dva režimy synchronizace</h4>
					<ul>
						<li><strong>term_key (doporučeno)</strong> — jako hodnota volby se uloží klíč, např. <code>kurz_2026_05_15_17</code>. Stabilní i při změně textu labelu.</li>
						<li><strong>label (legacy)</strong> — jako hodnota se uloží celý text, např. <em>kurz: 15. – 17. května 2026</em>. Vhodné, pokud formulář používá hodnotu v podmínkách nebo e-mailech.</li>
					</ul>
					<div class="hlavas-doc-tip">💡 Synchronizaci spustíš buď z menu <strong>Synchronizace s Fluent Forms</strong> (všechny formuláře najednou), nebo přímo z editace termínu (jen jeho formuláře).</div>
				</div>
			</details>

			<!-- KAPACITA -->
			<details class="hlavas-doc-item">
				<summary>
					<span class="hlavas-doc-item-icon">👥</span>
					Kapacita a obsazenost
				</summary>
				<div class="hlavas-doc-item-body">
					<p>Plugin sleduje kapacitu termínů dvěma způsoby, které se doplňují:</p>
					<h4>1. Fluent Forms Inventory</h4>
					<p>Po synchronizaci se v každém poli termínu nastaví „zásoby" (inventory) rovné kapacitě. Fluent Forms pak sám blokuje výběr plných termínů přímo ve formuláři (frontend).</p>
					<h4>2. Serverová validace pluginu</h4>
					<p>Při odeslání formuláře plugin provede vlastní kontrolu kapacity. Funguje jako záchranná síť — zachytí přihlášky, které by prošly, i kdyby inventory selhalo (například při souběžném odeslání dvou formulářů).</p>
					<h4>Kde vidět obsazenost</h4>
					<p>Menu <strong>Obsazenost a kapacita</strong> — přehled všech aktivních termínů s počtem přihlášených a zbývajícími místy.</p>
					<div class="hlavas-doc-ok">✅ Pokud změníš kapacitu termínu v pluginu a spustíš synchronizaci, nová kapacita se propíše do Fluent Forms i do inventory automaticky.</div>
				</div>
			</details>

			<!-- SKRYTÍ TERMÍNU -->
			<details class="hlavas-doc-item">
				<summary>
					<span class="hlavas-doc-item-icon">👁️</span>
					Skrývání termínů na frontendu
				</summary>
				<div class="hlavas-doc-item-body">
					<p>Potřebuješ-li termín skrýt z přihlašovacího formuláře (aniž by se ztratila data), postupuj takto:</p>
					<ol class="hlavas-doc-steps">
						<li>Otevři termín v editaci (<strong>Termíny kurzů / zkoušek</strong> → klik na název).</li>
						<li>Zaškrtávátko <strong>Viditelný na webu</strong> — odšktrtni.</li>
						<li>Ulož termín.</li>
						<li>Přejdi na stránku <strong>Synchronizace s Fluent Forms</strong> a spusť synchronizaci. Nebo použij tlačítko <em>Synchronizovat do FF</em> přímo v editaci termínu.</li>
					</ol>
					<div class="hlavas-doc-ok">✅ Přihlášky, které již byly odeslány pro tento termín, zůstanou nedotčeny — ve výpisu účastníků i v přehledu Fluent Forms entries.</div>
					<div class="hlavas-doc-warning">⚠️ Bez spuštění synchronizace se změna do formuláře nepropíše. Skrytí viditelnosti bez synchronizace nemá vliv na frontend.</div>
				</div>
			</details>

			<!-- VÝPIS ÚČASTNÍKŮ -->
			<details class="hlavas-doc-item">
				<summary>
					<span class="hlavas-doc-item-icon">📋</span>
					Výpis účastníků
				</summary>
				<div class="hlavas-doc-item-body">
					<p>Stránka <strong>Účastníci</strong> zobrazuje všechny osoby, které odeslaly přihlášku přes jakýkoliv napojený formulář.</p>
					<h4>Co výpis zobrazuje</h4>
					<ul>
						<li>Jméno, datum narození, adresa, e-mail, telefon</li>
						<li>Termín a typ kvalifikace (kurz / zkouška)</li>
						<li>Způsob platby a fakturační údaje</li>
						<li>Stav přihlášky (nová, přečtená, zaplacená)</li>
					</ul>
					<h4>Filtrování</h4>
					<p>Lze filtrovat podle termínu, typu kvalifikace a stavu přihlášky. Data lze exportovat jako CSV nebo XLSX, tisknout nebo odeslat e-mailem.</p>
					<div class="hlavas-doc-tip">💡 Výpis čte přihlášky přímo z Fluent Forms databáze. Nezáleží na tom, jestli je termín skrytý nebo archivovaný — přihlášky jsou vždy vidět.</div>
				</div>
			</details>

			<!-- NASTAVENÍ -->
			<details class="hlavas-doc-item">
				<summary>
					<span class="hlavas-doc-item-icon">⚙️</span>
					Nastavení pluginu
				</summary>
				<div class="hlavas-doc-item-body">
					<p>Stránka <strong>Nastavení</strong> obsahuje provozní konfiguraci pluginu.</p>
					<h4>Výchozí Fluent Form ID</h4>
					<p>Fallback formulář pro typy kvalifikací, které nemají vlastní Form ID. Slouží také pro debug a reporting starších přihlášek. Výchozí hodnota je <code>3</code>.</p>
					<h4>Režim synchronizace</h4>
					<p>Přednastavuje volbu term_key vs. label na stránce synchronizace. Doporučujeme <code>term_key</code> pro nová napojení.</p>
					<h4>E-mail pro reporty</h4>
					<p>Adresa, na kterou se zasílají exportované reporty (obsazenost, účastníci, termíny).</p>
					<h4>Debug režim</h4>
					<p>Při zapnutém debug módu se na stránce synchronizace zobrazuje detailní výpis struktury formulářů — užitečné při řešení problémů s mapováním polí.</p>
					<h4>Záloha a obnovení</h4>
					<p>Plugin umožňuje exportovat veškerá data (termíny, typy, nastavení) do JSON souboru a stejný soubor zpětně importovat — vhodné při migraci na jiný web.</p>
				</div>
			</details>

			<!-- FLUENT FORMS NAPOJENÍ -->
			<details class="hlavas-doc-item">
				<summary>
					<span class="hlavas-doc-item-icon">📝</span>
					Jak správně nastavit formulář ve Fluent Forms
				</summary>
				<div class="hlavas-doc-item-body">
					<p>Aby synchronizace fungovala, musí formulář ve Fluent Forms obsahovat výběrové pole se správným <code>name</code> atributem.</p>
					<h4>Povinné pole pro kurzy</h4>
					<ul>
						<li>Typ pole: <strong>Select / Dropdown</strong></li>
						<li>Atribut <code>name</code>: <code>termin_kurz</code></li>
						<li>Nebo label pole: <em>Vyber termín kurzu</em></li>
					</ul>
					<h4>Povinné pole pro zkoušky</h4>
					<ul>
						<li>Typ pole: <strong>Select / Dropdown</strong></li>
						<li>Atribut <code>name</code>: <code>termin_zkouska</code></li>
						<li>Nebo label pole: <em>Vyber termín zkoušky</em></li>
					</ul>
					<h4>Ostatní doporučená pole</h4>
					<p>Pro správný výpis účastníků by formulář měl obsahovat pole s názvy (nebo labely): <code>Name</code>, <code>narozeni</code>, <code>Address</code>, <code>ucastnik_email</code>, <code>ucastnik_telefon</code>, <code>typ_platby</code>.</p>
					<div class="hlavas-doc-tip">💡 Na stránce <strong>Synchronizace</strong> v sekci každého formuláře najdeš přehled, která pole plugin ve formuláři našel a která chybí.</div>
				</div>
			</details>

			<!-- REPORTY -->
			<details class="hlavas-doc-item">
				<summary>
					<span class="hlavas-doc-item-icon">📊</span>
					Reporty a exporty
				</summary>
				<div class="hlavas-doc-item-body">
					<p>Na stránkách <strong>Termíny kurzů</strong>, <strong>Termíny zkoušek</strong>, <strong>Obsazenost</strong> a <strong>Účastníci</strong> najdeš panel pro exporty.</p>
					<h4>Dostupné formáty</h4>
					<ul>
						<li><strong>CSV</strong> — tabulka oddělená středníkem, pro Excel a Google Sheets</li>
						<li><strong>XLSX</strong> — nativní Excel soubor</li>
						<li><strong>Tisk</strong> — tiskový náhled přímo v prohlížeči</li>
						<li><strong>E-mail</strong> — odešle CSV na e-mail nastavený v nastavení pluginu</li>
					</ul>
					<h4>Obsah exportů</h4>
					<ul>
						<li><em>Termíny</em> — seznam termínů s daty, kapacitami, stavem synchronizace</li>
						<li><em>Obsazenost</em> — termíny s počtem přihlášených a zbývající kapacitou</li>
						<li><em>Účastníci</em> — všichni přihlášení s osobními údaji, termínem a platebními informacemi</li>
					</ul>
				</div>
			</details>

			<!-- AKTIVITNÍ LOG -->
			<details class="hlavas-doc-item">
				<summary>
					<span class="hlavas-doc-item-icon">📝</span>
					Aktivitní log
				</summary>
				<div class="hlavas-doc-item-body">
					<p>Plugin zapisuje důležité akce do textového logu uloženého v adresáři pluginu (<code>logs/activity.log</code>).</p>
					<h4>Co se loguje</h4>
					<ul>
						<li>Aktivace a deaktivace pluginu</li>
						<li>Uložení, smazání a úprava termínů a typů</li>
						<li>Každá synchronizace do Fluent Forms (úspěch i chyba)</li>
						<li>Stažení, tisk a odeslání reportů e-mailem</li>
						<li>Import a export zálohy</li>
					</ul>
					<h4>Formát záznamu</h4>
					<p>Každý řádek obsahuje datum a čas, úroveň (INFO / WARNING / ERROR), kód akce, zprávu a kontext ve formátu JSON (včetně ID a jména uživatele).</p>
					<p>Log lze stáhnout nebo vymazat na stránce <strong>Nastavení</strong> v sekci <em>Aktivitní log pluginu</em>.</p>
					<div class="hlavas-doc-tip">💡 Log je jen textový soubor — nevyžaduje databázi a nezpomaluje web. Uchovej ho při migraci webu.</div>
				</div>
			</details>

			<!-- PRVNÍ NASTAVENÍ -->
			<details class="hlavas-doc-item">
				<summary>
					<span class="hlavas-doc-item-icon">🚀</span>
					První nastavení po instalaci
				</summary>
				<div class="hlavas-doc-item-body">
					<p>Po instalaci a aktivaci pluginu postupuj v tomto pořadí:</p>
					<ol class="hlavas-doc-steps">
						<li>Přejdi na stránku <strong>Nastavení</strong> a nastav <em>Výchozí Fluent Form ID</em> (ID formuláře, kde jsou přihlášky).</li>
						<li>Na stránce <strong>Typy kurzů</strong> vytvoř typy kvalifikací, které provozuješ. Ke každému přiřaď Form ID pro kurzy a zkoušky.</li>
						<li>Na stránce <strong>Přidat termín</strong> zadej první termín kurzu nebo zkoušky.</li>
						<li>Přejdi na stránku <strong>Synchronizace s Fluent Forms</strong> a spusť synchronizaci. Zkontroluj, zda plugin pole <code>termin_kurz</code> a <code>termin_zkouska</code> ve formuláři našel.</li>
						<li>Otevři formulář na frontendu a ověř, že se termín zobrazuje ve výběru.</li>
					</ol>
					<div class="hlavas-doc-ok">✅ Pokud plugin pole nenajde, zkontroluj na stránce synchronizace v debug sekci, jak se pole ve formuláři jmenuje (atribut <code>name</code>).</div>
				</div>
			</details>

			<!-- MIGRACE NA JINÝ WEB -->
			<details class="hlavas-doc-item">
				<summary>
					<span class="hlavas-doc-item-icon">📦</span>
					Migrace na jiný web
				</summary>
				<div class="hlavas-doc-item-body">
					<ol class="hlavas-doc-steps">
						<li>Na zdrojovém webu přejdi na <strong>Nastavení</strong> → <em>Export zálohy</em> a stáhni JSON backup.</li>
						<li>Na cílovém webu nainstaluj plugin, nastav výchozí Form ID a importuj backup přes <em>Import zálohy</em>.</li>
						<li>Ověř, že Fluent Forms formuláře na cílovém webu mají stejná ID (nebo je v nastavení oprav).</li>
						<li>Spusť synchronizaci pro všechny formuláře.</li>
					</ol>
					<div class="hlavas-doc-warning">⚠️ Import zálohy při volbě „nahradit stávající data" přepíše aktuální termíny a typy kvalifikací. Přihlášky v Fluent Forms záloha neobsahuje — ty zůstanou nedotčeny.</div>
				</div>
			</details>

		</div><!-- .hlavas-docs-grid -->
	</div><!-- .hlavas-docs-section -->

	<!-- ==================== FAQ ==================== -->
	<div class="hlavas-faq-section">
		<h2>❓ Nejčastější otázky (FAQ)</h2>

		<div class="hlavas-faq-list">

			<details class="hlavas-faq-item">
				<summary>Přidal jsem termín, ale ve formuláři se nezobrazuje. Proč?</summary>
				<div class="hlavas-faq-answer">
					<p>Termín se do formuláře dostane až po spuštění synchronizace. Přejdi na stránku <strong>Synchronizace s Fluent Forms</strong> a klikni na <em>Synchronizovat vše</em>. Nebo otevři termín v editaci a použij tlačítko <em>Synchronizovat do FF</em>.</p>
					<p>Druhá možná příčina: termín má nastavenou <strong>uzávěrku přihlášek</strong> v minulosti — takové termíny se do synchronizace nezahrnují.</p>
					<p>Třetí možnost: termín není označen jako <strong>Viditelný</strong> nebo <strong>Aktivní</strong>. Ověř nastavení v editaci termínu.</p>
				</div>
			</details>

			<details class="hlavas-faq-item">
				<summary>Jak skryju termín z formuláře, aniž smažu přihlášky?</summary>
				<div class="hlavas-faq-answer">
					<p>Otevři termín v editaci a odškrtni zaškrtávátko <strong>Viditelný na webu</strong>. Ulož a poté spusť synchronizaci. Termín zmizí z dropdownu, ale všechny existující přihlášky zůstanou v databázi Fluent Forms nedotčeny — jsou vidět ve výpisu účastníků i v Fluent Forms → Entries.</p>
				</div>
			</details>

			<details class="hlavas-faq-item">
				<summary>Změnil jsem kapacitu termínu. Propíše se to do formuláře?</summary>
				<div class="hlavas-faq-answer">
					<p>Ano, ale opět až po synchronizaci. Po uložení změny kapacity spusť synchronizaci — plugin přepíše nastavení inventory ve Fluent Forms na novou hodnotu. Fluent Forms pak automaticky bude nabízet/blokovat termín podle aktuální zásoby.</p>
				</div>
			</details>

			<details class="hlavas-faq-item">
				<summary>Zobrazuje se obsazenost špatně (jiný počet než skutečnost). Proč?</summary>
				<div class="hlavas-faq-answer">
					<p>Nejčastější příčiny:</p>
					<ul>
						<li>Přihlášky z typizovaných formulářů (s vlastním Form ID u typu kvalifikace) — zkontroluj, že máš správně nastavená Form ID u každého typu v menu <strong>Typy kurzů</strong>.</li>
						<li>Stará přihláška uložila hodnotu jako <em>label</em> (text) místo <em>term_key</em> — plugin zpětnou kompatibilitu řeší, ale může být zpoždění při přepnutí režimů.</li>
						<li>Přihláška má stav <em>trashed</em> — takové záznamy se do počtu nezahrnují záměrně.</li>
					</ul>
				</div>
			</details>

			<details class="hlavas-faq-item">
				<summary>Synchronizace hlásí, že pole nebylo nalezeno. Co s tím?</summary>
				<div class="hlavas-faq-answer">
					<p>Plugin hledá pole podle atributu <code>name</code>, <code>admin_field_label</code> nebo viditelného labelu. Musí se shodovat s aliasy nastavenými v pluginu:</p>
					<ul>
						<li>Pro kurzy: <code>termin_kurz</code> nebo label <em>Vyber termín kurzu</em></li>
						<li>Pro zkoušky: <code>termin_zkouska</code> nebo label <em>Vyber termín zkoušky</em></li>
					</ul>
					<p>Ve Fluent Forms editoru otevři dané pole a zkontroluj záložku <em>Advanced</em> → pole <em>Name</em>. Nastav ho na <code>termin_kurz</code> nebo <code>termin_zkouska</code>.</p>
					<p>Případně použij ruční mapování polí v <strong>Nastavení</strong> → sekce <em>Rozšíření nastavení: ruční mapování polí</em>.</p>
				</div>
			</details>

			<details class="hlavas-faq-item">
				<summary>Mám více typů kvalifikací. Potřebuji pro každý jiný formulář?</summary>
				<div class="hlavas-faq-answer">
					<p>Ne. Jeden formulář může obsluhovat více typů zároveň. V takovém případě vyplň stejné Form ID u více typů kvalifikací a plugin při synchronizaci sloučí termíny všech přiřazených typů do jednoho pole.</p>
					<p>Pokud ale potřebuješ odlišné přihlašovací formuláře (třeba jiná pole, jiná pravidla), klidně každý typ nasměruj na jiný formulář — plugin to plně podporuje.</p>
				</div>
			</details>

			<details class="hlavas-faq-item">
				<summary>Co je term_key a proč ho nesmím měnit po synchronizaci?</summary>
				<div class="hlavas-faq-answer">
					<p><code>term_key</code> je interní identifikátor termínu, například <code>kurz_2026_05_15_17</code>. Je automaticky generovaný při vytvoření termínu z data.</p>
					<p>Po synchronizaci v režimu <em>term_key</em> je tato hodnota uložena jako <em>value</em> výběrového pole ve Fluent Forms. Pokud klíč změníš, starší přihlášky budou mít v poli jinou hodnotu, než jakou plugin nyní rozpozná — obsazenost i výpis účastníků budou nepřesné.</p>
					<p>Klíč je bezpečné měnit pouze tehdy, pokud pro daný termín ještě nebyly odeslány žádné přihlášky.</p>
				</div>
			</details>

			<details class="hlavas-faq-item">
				<summary>Jak exportovat seznam přihlášených na konkrétní termín?</summary>
				<div class="hlavas-faq-answer">
					<p>Přejdi na stránku <strong>Účastníci</strong> a použij filtr <em>Termín</em> pro výběr konkrétního termínu. Poté klikni na <em>Stáhnout CSV</em> nebo <em>Stáhnout XLSX</em> — exportují se jen filtrované záznamy.</p>
				</div>
			</details>

			<details class="hlavas-faq-item">
				<summary>Přihlašovací formulář blokuje přihlášení, i když termín ještě není plný. Co dělat?</summary>
				<div class="hlavas-faq-answer">
					<p>Možné příčiny:</p>
					<ul>
						<li>Fluent Forms Inventory je nastaveno na nulovou zásobu — spusť synchronizaci, která zásobu přepíše aktuální kapacitou.</li>
						<li>Serverová validace pluginu počítá přihlášky přes jiné Form ID než kde jsou přihlášky uloženy — zkontroluj, zda má typ kvalifikace správné Form ID v <strong>Typech kurzů</strong>.</li>
						<li>Uzávěrka přihlášek v minulosti — termín s prošlou uzávěrkou plugin nepovažuje za dostupný.</li>
					</ul>
					<p>Pro diagnostiku zapni <strong>Debug režim</strong> v nastavení a zkontroluj výpis na stránce synchronizace.</p>
				</div>
			</details>

			<details class="hlavas-faq-item">
				<summary>Deaktivoval jsem Fluent Forms. Přestal plugin fungovat?</summary>
				<div class="hlavas-faq-answer">
					<p>Plugin při deaktivaci Fluent Forms nevyhodí chybu — všechny dotazy na FF tabulky jsou chráněné kontrolou existence tabulky. Správa termínů v administraci funguje dál. Synchronizace, obsazenost ani výpis účastníků ale nebudou mít data, protože tabulky Fluent Forms neexistují. Po opětovné aktivaci Fluent Forms vše funguje znovu normálně.</p>
				</div>
			</details>

			<details class="hlavas-faq-item">
				<summary>Jak zálohovat a přenést plugin na jiný web?</summary>
				<div class="hlavas-faq-answer">
					<p>Přejdi na <strong>Nastavení</strong> → <em>Export zálohy</em> → <em>Stáhnout backup nastavení a dat</em>. Stáhne se JSON soubor se všemi termíny, typy kvalifikací a nastavením.</p>
					<p>Na novém webu nainstaluj plugin, přejdi na Nastavení → <em>Import zálohy</em> a nahraj soubor. Poté nastav Form ID pro nové formuláře a spusť synchronizaci.</p>
				</div>
			</details>

			<details class="hlavas-faq-item">
				<summary>Kde najdu logy a jak zjistím, kdo co změnil?</summary>
				<div class="hlavas-faq-answer">
					<p>Aktivitní log je dostupný na stránce <strong>Nastavení</strong> v sekci <em>Aktivitní log pluginu</em>. Zobrazuje posledních 200 záznamů (nejnovější nahoře).</p>
					<p>Každý záznam obsahuje datum a čas, kód akce, zprávu, ID a jméno uživatele, který akci provedl.</p>
					<p>Fyzický soubor leží na serveru v adresáři pluginu: <code>wp-content/plugins/hlavas-kurzy/logs/activity.log</code>.</p>
				</div>
			</details>

		</div><!-- .hlavas-faq-list -->
	</div><!-- .hlavas-faq-section -->

</div><!-- .wrap -->

<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var array<string, string> $plugin_info */
/** @var array<int, array<string, mixed>> $form_registry */
/** @var array<string, mixed> $settings_status */

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
	<p class="hlavas-page-intro">
		Centralizovaná dokumentace pluginu pro správce i programátora. Nahoře zůstává rychlý start pro každodenní práci,
		pod ním je podrobný manuál, shortcode reference, technické informace, FAQ a changelog verzí.
	</p>

	<nav class="hlavas-section-nav">
		<a href="#hlavas-info-start">Rychlý start</a>
		<a href="#hlavas-info-manual">Manuál</a>
		<a href="#hlavas-info-shortcodes">Shortcodes</a>
		<a href="#hlavas-info-tech">Technické informace</a>
		<a href="#hlavas-info-faq">FAQ</a>
		<a href="#hlavas-info-changelog">Changelog</a>
	</nav>

	<div class="hlavas-docs-section" id="hlavas-info-start">
		<h2>Rychlý start</h2>
		<div class="hlavas-docs-grid">
			<div class="hlavas-doc-card">
				<h3>1. Nastav typy a formuláře</h3>
				<p>
					V menu <strong>Typy kurzů</strong> přiřaď ke každé kvalifikaci správné Form ID pro kurz a zkoušku.
					V <strong>Nastavení</strong> nech vyplněný jen fallback formulář pro starší nebo smíšené scénáře.
				</p>
			</div>
			<div class="hlavas-doc-card">
				<h3>2. Založ nebo importuj termíny</h3>
				<p>
					Termíny můžeš vytvořit ručně přes <strong>Přidat termín</strong>, nebo je při prvním nasazení načíst z existujícího FF formuláře
					přes stránku <strong>Synchronizace s Fluent Forms</strong>.
				</p>
			</div>
			<div class="hlavas-doc-card">
				<h3>3. Proveď synchronizaci do formulářů</h3>
				<p>
					Na stránce <strong>Synchronizace</strong> zkontroluj nalezená pole, náhled termínů a spusť export z HLAVAS do Fluent Forms.
				</p>
			</div>
			<div class="hlavas-doc-card">
				<h3>4. Sleduj kapacity a účastníky</h3>
				<p>
					Stránky <strong>Obsazenost a kapacita</strong> a <strong>Účastníci</strong> vycházejí z Fluent Forms entries,
					ručního párování a kapacit vedených v pluginu.
				</p>
			</div>
		</div>
	</div>

	<div class="hlavas-docs-section" id="hlavas-info-manual">
		<h2>Kompletní manuál</h2>
		<div class="hlavas-docs-intro">
			Plugin <strong>HLAVAS – Správa termínů kurzů a zkoušek</strong> slouží ke správě termínů kurzů a zkoušek na webu HLAVAS.cz.
			Propojuje vlastní administraci termínů s přihlašovacími formuláři ve Fluent Forms, plní výběrové seznamy termínů,
			hlídá kapacity a pomáhá s párováním přihlášek na konkrétní termíny.
		</div>

		<div class="hlavas-docs-grid">
			<details class="hlavas-doc-item">
				<summary>
					<span class="hlavas-doc-item-icon">🏷️</span>
					Typy kvalifikací
				</summary>
				<div class="hlavas-doc-item-body">
					<p>Typ kvalifikace je kategorie, do které termíny patří. Například <em>75-008-N – Hlavní vedoucí zotavovací akce dětí a mládeže</em>.</p>
					<h4>Co typ kvalifikace obsahuje</h4>
					<ul>
						<li><strong>Název a číslo akreditace</strong> – používá se ve výpisech, synchronizaci i u generování labelů zkoušek.</li>
						<li><strong>Form ID pro kurz</strong> – Fluent Forms formulář, do jehož pole <code>termin_kurz</code> se synchronizují termíny kurzů tohoto typu.</li>
						<li><strong>Form ID pro zkoušku</strong> – totéž pro pole <code>termin_zkouska</code>.</li>
						<li><strong>Příznaky</strong> – zda jde o akreditovaný typ, zda obsahuje kurzy, zkoušky nebo obojí.</li>
					</ul>
					<div class="hlavas-doc-tip">Jeden formulář může obsluhovat víc typů kvalifikací najednou. Plugin je při synchronizaci sloučí automaticky.</div>
				</div>
			</details>

			<details class="hlavas-doc-item">
				<summary>
					<span class="hlavas-doc-item-icon">🗓️</span>
					Termíny kurzů a zkoušek
				</summary>
				<div class="hlavas-doc-item-body">
					<p>Termín je konkrétní datum nebo rozsah dat, kdy kurz nebo zkouška proběhne.</p>
					<ul>
						<li><strong>Typ</strong> – kurz nebo zkouška.</li>
						<li><strong>Interní klíč (term_key)</strong> – unikátní identifikátor, například <code>kurz_2026_05_15_17</code>.</li>
						<li><strong>Title</strong> – administrativní název termínu.</li>
						<li><strong>Label</strong> – text, který se zobrazuje uživateli ve formuláři.</li>
						<li><strong>Datum od / do</strong> – termín konání.</li>
						<li><strong>Uzávěrka přihlášek</strong> – po tomto datu může být termín stažen z nabídky.</li>
						<li><strong>Kapacita</strong> – maximální počet přihlášených.</li>
						<li><strong>Viditelnost, aktivita, archiv</strong> – řídí práci s termínem v pluginu i na webu.</li>
					</ul>
					<h4>Speciální pravidlo pro zkoušky</h4>
					<p>Pokud je vybraný typ <strong>zkouška</strong> a termín má navázaný typ kvalifikace s akreditačním číslem, label se generuje ve tvaru <em>Zkouška z profesní kvalifikace 75-008-N (22. dubna 2026)</em>.</p>
				</div>
			</details>

			<details class="hlavas-doc-item">
				<summary>
					<span class="hlavas-doc-item-icon">🔄</span>
					Synchronizace s Fluent Forms
				</summary>
				<div class="hlavas-doc-item-body">
					<p>Synchronizace přenáší termíny mezi pluginem a formuláři.</p>
					<h4>Dva směry práce</h4>
					<ul>
						<li><strong>Načíst z Fluent Forms do HLAVAS</strong> – vhodné při prvním nasazení nebo převzetí staršího formuláře.</li>
						<li><strong>Odeslat z HLAVAS do Fluent Forms</strong> – běžný provoz, kdy plugin přepisuje nabídku termínů a jejich kapacity ve formuláři.</li>
					</ul>
					<h4>Dva režimy hodnot</h4>
					<ul>
						<li><strong>Nový režim (term_key)</strong> – value obsahuje interní klíč termínu. Doporučeno pro nové instalace.</li>
						<li><strong>Legacy režim (label)</strong> – value obsahuje text labelu. Vhodné pro starší formuláře s navázanými podmínkami nebo e-maily.</li>
					</ul>
					<div class="hlavas-doc-warning">Synchronizace do FF nepřepisuje osobní údaje účastníků ani samotné entries. Přepisuje pouze nabídku termínů a jejich kapacity.</div>
				</div>
			</details>

			<details class="hlavas-doc-item">
				<summary>
					<span class="hlavas-doc-item-icon">👥</span>
					Kapacita, obsazenost a účastníci
				</summary>
				<div class="hlavas-doc-item-body">
					<p>Plugin pracuje s kapacitou dvojím způsobem:</p>
					<ul>
						<li><strong>Fluent Forms inventory</strong> – po synchronizaci se kapacita termínů propisuje do formuláře.</li>
						<li><strong>Serverová logika pluginu</strong> – HLAVAS dopočítává účastníky, párování legacy záznamů a ruční přesuny mezi termíny.</li>
					</ul>
					<p>Na stránce <strong>Účastníci</strong> lze ručně přepárovat nebo přesunout účastníka na jiný termín. Tato změna se potom promítne i do kapacit.</p>
				</div>
			</details>

			<details class="hlavas-doc-item">
				<summary>
					<span class="hlavas-doc-item-icon">🧰</span>
					Nastavení a servis
				</summary>
				<div class="hlavas-doc-item-body">
					<h4>Nastavení</h4>
					<ul>
						<li>fallback Form ID</li>
						<li>výchozí režim synchronizace</li>
						<li>debug režim</li>
						<li>reportovací e-mail</li>
						<li>ruční mapování polí mezi HLAVAS a Fluent Forms</li>
					</ul>
					<h4>Servis / Diagnostika</h4>
					<ul>
						<li>export a import backupu pluginu</li>
						<li>aktivitní log</li>
						<li>technická kontrola stavu</li>
						<li>raw debug formulářů</li>
					</ul>
				</div>
			</details>
		</div>
	</div>

	<div class="hlavas-docs-section" id="hlavas-info-shortcodes">
		<h2>Shortcodes pro frontend</h2>
		<p class="hlavas-page-intro">
			Vkládej shortcodes do stránek, článků nebo page builderů. Zobrazují pouze veřejné informace, nikoli osobní data účastníků.
		</p>

		<div class="hlavas-docs-grid">
			<div class="hlavas-shortcode-card">
				<div class="hlavas-shortcode-tag"><code>[hlavas_terms]</code></div>
				<p class="hlavas-shortcode-desc">Tabulka nadcházejících termínů s kapacitami.</p>
				<table class="hlavas-shortcode-attrs">
					<thead><tr><th>Atribut</th><th>Hodnoty</th><th>Výchozí</th></tr></thead>
					<tbody>
						<tr><td><code>type</code></td><td><code>kurz</code> | <code>zkouska</code> | <code>all</code></td><td><code>all</code></td></tr>
						<tr><td><code>show</code></td><td><code>upcoming</code> | <code>all</code></td><td><code>upcoming</code></td></tr>
						<tr><td><code>limit</code></td><td>číslo</td><td><code>0</code> (vše)</td></tr>
						<tr><td><code>show_capacity</code></td><td><code>yes</code> | <code>no</code></td><td><code>yes</code></td></tr>
						<tr><td><code>qualification</code></td><td>type_key</td><td>— (vše)</td></tr>
						<tr><td><code>class</code></td><td>CSS třída</td><td>—</td></tr>
					</tbody>
				</table>
				<p class="hlavas-shortcode-example"><strong>Příklady:</strong></p>
				<code class="hlavas-shortcode-snippet">[hlavas_terms type="kurz" limit="5"]</code>
				<code class="hlavas-shortcode-snippet">[hlavas_terms type="zkouska" show_capacity="no"]</code>
			</div>

			<div class="hlavas-shortcode-card">
				<div class="hlavas-shortcode-tag"><code>[hlavas_term_capacity]</code></div>
				<p class="hlavas-shortcode-desc">Odznak volných míst pro konkrétní termín.</p>
				<table class="hlavas-shortcode-attrs">
					<thead><tr><th>Atribut</th><th>Hodnoty</th><th>Výchozí</th></tr></thead>
					<tbody>
						<tr><td><code>term_key</code></td><td>klíč termínu</td><td>— (povinné)</td></tr>
						<tr><td><code>format</code></td><td><code>badge</code> | <code>text</code> | <code>number</code></td><td><code>badge</code></td></tr>
					</tbody>
				</table>
				<code class="hlavas-shortcode-snippet">[hlavas_term_capacity term_key="kurz_2026_05_15_17"]</code>
				<code class="hlavas-shortcode-snippet">[hlavas_term_capacity term_key="kurz_2026_05_15_17" format="text"]</code>
			</div>

			<div class="hlavas-shortcode-card">
				<div class="hlavas-shortcode-tag"><code>[hlavas_waitlist]</code></div>
				<p class="hlavas-shortcode-desc">Formulář čekací listiny pro plný termín.</p>
				<table class="hlavas-shortcode-attrs">
					<thead><tr><th>Atribut</th><th>Hodnoty</th><th>Výchozí</th></tr></thead>
					<tbody>
						<tr><td><code>term_key</code></td><td>klíč termínu</td><td>— (povinné)</td></tr>
						<tr><td><code>show_always</code></td><td><code>yes</code> | <code>no</code></td><td><code>no</code></td></tr>
					</tbody>
				</table>
				<code class="hlavas-shortcode-snippet">[hlavas_waitlist term_key="kurz_2026_05_15_17"]</code>
				<p class="description" style="margin-top:6px;">Záznamy čekací listiny jsou uložené v databázi a přístupné pouze v adminu.</p>
			</div>
		</div>
	</div>

	<div class="hlavas-docs-section" id="hlavas-info-tech">
		<h2>Technické informace</h2>
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
					<td><a href="<?php echo esc_url( $plugin_info['plugin_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $plugin_info['plugin_url'] ); ?></a></td>
				</tr>
				<tr>
					<th>Licence</th>
					<td><?php echo esc_html( $plugin_info['license'] ); ?></td>
				</tr>
				<tr>
					<th>Aktuální prostředí</th>
					<td>WordPress <?php echo esc_html( $plugin_info['current_wp'] ); ?>, PHP <?php echo esc_html( $plugin_info['current_php'] ); ?></td>
				</tr>
				<tr>
					<th>Minimální kompatibilita</th>
					<td>WordPress <?php echo esc_html( $plugin_info['min_wp'] ); ?>+, PHP <?php echo esc_html( $plugin_info['min_php'] ); ?>+</td>
				</tr>
				<tr>
					<th>Databázové tabulky</th>
					<td><code><?php echo esc_html( $plugin_info['table'] ); ?></code><br><code><?php echo esc_html( $plugin_info['types_table'] ); ?></code></td>
				</tr>
				<tr>
					<th>Stav tabulek a FF</th>
					<td>
						Termíny: <?php echo ! empty( $settings_status['terms_table_exists'] ) ? 'Ano' : 'Ne'; ?>,
						Typy: <?php echo ! empty( $settings_status['types_table_exists'] ) ? 'Ano' : 'Ne'; ?>,
						FF formuláře: <?php echo ! empty( $settings_status['fluent_forms_table_exists'] ) ? 'Ano' : 'Ne'; ?>,
						FF entries: <?php echo ! empty( $settings_status['fluent_subs_table_exists'] ) ? 'Ano' : 'Ne'; ?>
					</td>
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

		<div class="hlavas-doc-card" style="margin-top:16px;">
			<h3>Propojené Fluent Forms formuláře</h3>
			<?php if ( empty( $grouped_forms ) ) : ?>
				<p class="description">Žádný formulář zatím není propojen.</p>
			<?php else : ?>
				<ul class="hlavas-flat-list">
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
		</div>
	</div>

	<div class="hlavas-docs-section" id="hlavas-info-faq">
		<h2>FAQ</h2>
		<div class="hlavas-docs-grid">
			<div class="hlavas-doc-card">
				<h3>Kde nastavím Form ID?</h3>
				<p>Konkrétní Form ID pro kurz nebo zkoušku patří do <strong>Typů kurzů</strong>. V <strong>Nastavení</strong> je jen fallback formulář pro starší scénáře.</p>
			</div>
			<div class="hlavas-doc-card">
				<h3>Kde řeším chybu synchronizace?</h3>
				<p>Běžnou kontrolu najdeš na stránce <strong>Synchronizace</strong>. Pro backup, log a raw debug jdi na <strong>Servis / Diagnostika</strong>.</p>
			</div>
			<div class="hlavas-doc-card">
				<h3>Smaže synchronizace účastníky nebo osobní údaje?</h3>
				<p>Ne. Synchronizace do FF mění volby termínových polí a jejich kapacity. Entries a osobní údaje zůstávají ve Fluent Forms.</p>
			</div>
			<div class="hlavas-doc-card">
				<h3>Co dělat při legacy datech?</h3>
				<p>Na stránce <strong>Synchronizace</strong> spusť nejdřív opravu legacy entries na nový formát a potom rebuild účastníků a kapacit.</p>
			</div>
		</div>
	</div>

	<div class="hlavas-docs-section" id="hlavas-info-changelog">
		<h2>Changelog a release log</h2>
		<p class="hlavas-page-intro">
			Níže je souhrn vývoje pluginu podle dosavadních verzí. Část raných meziverzí je dopočítaná z historie úprav a slouží jako interní orientační přehled.
		</p>

		<div class="hlavas-docs-grid">
			<div class="hlavas-doc-card">
				<h3>1.3.1</h3>
				<ul class="hlavas-flat-list">
					<li>Návrat bohatšího textového obsahu stránky Nápověda / O pluginu.</li>
					<li>Obnovená a rozšířená shortcode reference.</li>
					<li>Synchronizace znovu obsahuje rebuild účastníků a opravu legacy entries.</li>
					<li>Vylepšené vysvětlení režimů synchronizace a vizuální úprava sync stránky.</li>
					<li>Přidán changelog přímo do pluginu.</li>
				</ul>
			</div>

			<div class="hlavas-doc-card">
				<h3>1.3.0</h3>
				<ul class="hlavas-flat-list">
					<li>Přeorganizovaná informační architektura adminu.</li>
					<li>Oddělení stránek <strong>Synchronizace</strong>, <strong>Nastavení</strong>, <strong>Servis / Diagnostika</strong> a <strong>Nápověda</strong>.</li>
					<li>První verze nové servisní stránky s logem, backupy a debugem.</li>
				</ul>
			</div>

			<div class="hlavas-doc-card">
				<h3>1.2.7</h3>
				<ul class="hlavas-flat-list">
					<li>Možnost ručně měnit termín i u už spárovaných účastníků.</li>
					<li>Přesun účastníka mezi termíny s dopadem do kapacit.</li>
					<li>Sloupec <strong>Přihlášeno</strong> v přehledu termínů.</li>
				</ul>
			</div>

			<div class="hlavas-doc-card">
				<h3>1.2.6</h3>
				<ul class="hlavas-flat-list">
					<li>Nástroje pro rebuild účastníků a kapacit z FF entries.</li>
					<li>Migrace legacy entries na nový HLAVAS formát.</li>
					<li>Indikace legacy/new formátu u účastníků.</li>
					<li>Rozšířená práce s ručním mapováním formulářových polí.</li>
				</ul>
			</div>

			<div class="hlavas-doc-card">
				<h3>1.2.0 – 1.2.5</h3>
				<ul class="hlavas-flat-list">
					<li>Rozšíření synchronizace na více formulářů podle typu kvalifikace.</li>
					<li>Načítání účastníků z Fluent Forms, filtry a detail účastníka.</li>
					<li>CSV/XLS exporty, e-mail reporty a tiskové výstupy.</li>
					<li>Backup export/import, aktivitní log pluginu, uninstall cleanup a upgrade rutiny.</li>
					<li>Ruční mapování HLAVAS ↔ Fluent Forms a diagnostika polí.</li>
				</ul>
			</div>

			<div class="hlavas-doc-card">
				<h3>1.1.2</h3>
				<ul class="hlavas-flat-list">
					<li>Stabilizace integrace s účastníky a FF entries.</li>
					<li>Rozšířená práce s informacemi o pluginu a propojených formulářích.</li>
					<li>Další ladění admin výpisů a synchronizace.</li>
				</ul>
			</div>

			<div class="hlavas-doc-card">
				<h3>1.1.0</h3>
				<ul class="hlavas-flat-list">
					<li>Přidány typy kvalifikací a samostatná databázová tabulka pro jejich správu.</li>
					<li>Termíny navázané na typ kvalifikace, uzávěrku přihlášek a viditelnost na webu.</li>
					<li>Nové menu: typy kurzů, účastníci, synchronizace, obsazenost.</li>
					<li>Základ pro párování různých Fluent Forms formulářů ke kurzům a zkouškám.</li>
				</ul>
			</div>

			<div class="hlavas-doc-card">
				<h3>1.0.2</h3>
				<ul class="hlavas-flat-list">
					<li>Upravená metadata pluginu, autor, kontakty a organizační údaje.</li>
					<li>Sjednocená verze v hlavičce pluginu a interních konstantách.</li>
				</ul>
			</div>

			<div class="hlavas-doc-card">
				<h3>1.0.0 – 1.0.1</h3>
				<ul class="hlavas-flat-list">
					<li>První vydání pluginu pro správu termínů kurzů a zkoušek.</li>
					<li>Základní přehled termínů, editace, kapacity a první synchronizace s Fluent Forms.</li>
					<li>Počáteční stabilizace pro PHP 8.4 a WordPress admin.</li>
				</ul>
			</div>
		</div>
	</div>
</div>

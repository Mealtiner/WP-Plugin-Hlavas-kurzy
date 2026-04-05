<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return [
	[
		'version' => '1.3.4',
		'date'    => '2026-04-05',
		'summary' => 'Kontrola závislosti na Fluent Forms při aktivaci a nové instalační upozornění v administraci.',
		'items'   => [
			'Plugin při aktivaci nově kontroluje dostupnost Fluent Forms a při chybějící závislosti uloží administrační upozornění.',
			'Byl doplněn klasický WordPress admin notice s informací, že HLAVAS potřebuje Fluent Forms a správně nastavený formulář.',
			'Pokud základní Fluent Forms chybí, notice nabízí přímé tlačítko pro instalaci bezplatné verze; pokud je jen neaktivní, nabídne její aktivaci.',
			'Do hlavičky pluginu byl doplněn standardní údaj Requires Plugins: fluentform pro lepší kompatibilitu s moderním WordPress lifecycle.',
			'Ve verzování pluginu byly sjednoceny hlavička pluginu, interní konstanta i readme na verzi 1.3.4.',
		],
	],
	[
		'version' => '1.3.3',
		'date'    => '2026-04-05',
		'summary' => 'Přeskupení sekcí nápovědy a oddělení dokumentačních bloků do samostatných souborů.',
		'items'   => [
			'Stránka Nápověda / O pluginu byla přeskládána do pořadí: úvod, rychlý start, technické informace, dokumentace, FAQ a changelog.',
			'Blok Dokumentace byl vytažen do samostatného partial souboru bez změny jeho obsahu a grafiky.',
			'Blok FAQ byl vytažen do samostatného partial souboru bez změny jeho obsahu a grafiky.',
			'Byl upraven úvod changelogu tak, aby sekce popisovala obsah release logu místo technického způsobu načítání.',
			'Ve verzování pluginu byly sjednoceny hlavička pluginu, interní konstanta i readme na verzi 1.3.3.',
		],
	],
	[
		'version' => '1.3.2',
		'date'    => '2026-04-05',
		'summary' => 'Finální UX/UI doladění nápovědy a nastavení.',
		'items'   => [
			'Na stránce Nápověda byly přesunuty Technické informace mezi Rychlý start a hlavní dokumentaci.',
			'Byla sjednocena šířka technické tabulky s kartami rychlého startu a upraven styl navigačních tlačítek.',
			'Sekce Propojené Fluent Forms formuláře byla přesunuta z nápovědy do Nastavení, kde tematicky lépe zapadá.',
			'Shortcodes v Nastavení byly rozloženy do tří hlavních sloupců vedle sebe pro rychlejší orientaci.',
		],
	],
	[
		'version' => '1.3.1',
		'date'    => '2026-04-05',
		'summary' => 'Návrat rozsáhlé nápovědy, obnovy FAQ a oddělený changelog.',
		'items'   => [
			'Stránka Nápověda / O pluginu byla vrácena do bohatší textové podoby s rozsáhlou dokumentací.',
			'Byla obnovena kompletní FAQ sekce podle dřívější podoby a screenshotů z adminu.',
			'Rychlý start zůstal zachován nahoře a byl doplněn navazující strukturou celé dokumentace.',
			'Changelog byl vytažen do samostatného zdroje, aby další release nevyžadoval zásah do info stránky.',
			'Shortcode dokumentace byla přesunuta z Nápovědy do stránky Nastavení.',
		],
	],
	[
		'version' => '1.3.0',
		'date'    => '2026-04-05',
		'summary' => 'Přeuspořádání informační architektury administrační části pluginu.',
		'items'   => [
			'Bylo odděleno Nastavení, Synchronizace s Fluent Forms, Servis / Diagnostika a Nápověda / O pluginu.',
			'Vznikla samostatná servisní obrazovka pro log, backupy, technickou kontrolu a debug výstupy.',
			'Proběhlo první větší UX/UI sjednocení pracovních stránek pluginu.',
			'Byly připraveny nové textové úvody a orientační bloky pro běžnou práci administrátora.',
		],
	],
	[
		'version' => '1.2.5',
		'date'    => '2026-04-04',
		'summary' => 'Frontend shortcodes, čekací listina a kritické opravy synchronizace.',
		'items'   => [
			'Byla opravena kritická chyba synchronizace, kdy se ve Fluent Forms resetovala inventory na plnou kapacitu místo zbývajících míst.',
			'Na základě implementace prováděné přes Claude Code byly přidány frontend shortcodes pro veřejný výpis termínů a kapacit bez osobních údajů.',
			'Byl doplněn shortcode a backend logika čekací listiny pro plné termíny včetně GDPR-safe ukládání kontaktu.',
			'Byl přidán automatický archiv prošlých termínů přes WP-Cron.',
			'Vznikl čistý instalační balíček pluginu bez vývojových souborů a byla ověřena syntaxe celého pluginu na PHP 8.4.',
		],
	],
	[
		'version' => '1.2.4',
		'date'    => '2026-04-04',
		'summary' => 'Legacy data, rebuild kapacit a ruční párování účastníků.',
		'items'   => [
			'Byl přidán nástroj pro opravu legacy entries na nový HLAVAS formát bez destruktivního přepisu původních dat.',
			'Na stránce Synchronizace vznikla akce Rebuild účastníků a kapacit z Fluent Forms entries.',
			'Do přehledu účastníků byla přidána informace, zda je záznam legacy nebo new.',
			'Bylo doplněno ruční párování nepárovaných účastníků na konkrétní termín a následně i možnost přesunu už spárovaných účastníků.',
			'Došlo k lepšímu napárování starších textových hodnot termínů na současné term_key a labels.',
		],
	],
	[
		'version' => '1.2.3',
		'date'    => '2026-04-04',
		'summary' => 'Rozšíření práce s účastníky a mapováním formulářů.',
		'items'   => [
			'Stránka Účastníci začala načítat reálné entries z navázaných Fluent Forms formulářů místo placeholderu.',
			'Byla doplněna filtrace podle akreditace, typu přihlášky a konkrétního termínu včetně rozbalovacího detailu účastníka.',
			'Na základě dodaných exportů a následných úprav byly přidány legacy aliasy formulářových polí a volnější párování termínů.',
			'Bylo doplněno ruční mapování HLAVAS ↔ Fluent Forms jako rozšířené nastavení v administraci.',
			'Přibylo řazení účastníků podle hlaviček tabulky a vizuální úprava filtračního panelu.',
		],
	],
	[
		'version' => '1.2.2',
		'date'    => '2026-04-04',
		'summary' => 'Dokumentace, instalační balíčky a konsolidace verzování.',
		'items'   => [
			'Byla rozšířena stránka Nápověda / O pluginu o komplexní uživatelskou dokumentaci a technické informace.',
			'Vznikly opakovaně čisté instalační ZIP balíčky bez vývojových souborů a s korektní vnitřní strukturou pro WordPress upload.',
			'Byla srovnána verze pluginu mezi hlavním souborem a readme.txt a zkontrolována metadata pro distribuci.',
			'Do dokumentace a adminu se propsaly nové informace o konfiguraci, synchronizaci a migraci pluginu.',
		],
	],
	[
		'version' => '1.2.1',
		'date'    => '2026-04-03',
		'summary' => 'Kritické opravy po hloubkovém auditu Claude Code.',
		'items'   => [
			'Na základě detailního auditu byly opraveny serverové kontroly kapacity tak, aby braly v úvahu všechny nakonfigurované formuláře, ne jen fallback Form ID.',
			'Submit validator začal správně validovat i typizované formuláře navázané na jednotlivé kvalifikace.',
			'Availability service byl upraven pro práci s více form_ids, doplněny table_exists kontroly a bezpečnější SQL placeholdery.',
			'Byla zpřesněna lifecycle část pluginu kolem aktivace, aktualizací a čištění interních dat.',
			'Do verze byly zahrnuty opravy z Claude Code zaměřené na stabilitu a kompatibilitu více formulářů.',
		],
	],
	[
		'version' => '1.2.0',
		'date'    => '2026-04-03',
		'summary' => 'Velké překopání nastavení, sync workflow a exportních nástrojů.',
		'items'   => [
			'Byla zásadně přepracována stránka Nastavení pluginu.',
			'Byly přidány exporty CSV/XLS, tiskové sestavy a odesílání reportů na centrálně nastavený e-mail.',
			'Vznikl backup export/import provozních dat a pluginových tabulek.',
			'Rozšířila se stránka Synchronizace s Fluent Forms o přehled navázaných formulářů a náhled zapisovaných termínů.',
			'Došlo k většímu odladění synchronizace a ověřování dopadu změn do FF formulářů.',
		],
	],
	[
		'version' => '1.1.5',
		'date'    => '2026-04-03',
		'summary' => 'Údržba pluginu, uninstall a update rutiny.',
		'items'   => [
			'Byla doplněna bezpečná odinstalace pluginu se smazáním pluginových tabulek, options, transientů a logů bez zásahu do Fluent Forms dat.',
			'Byla rozšířena update logika tak, aby se při nahrání nové verze správně spouštěly migrace a dbDelta aktualizace.',
			'Byl doplněn aktivitní log pluginu do souboru a jeho náhled v administraci.',
			'Do pluginu se propsala lepší evidence interní verze a provozních zásahů.',
		],
	],
	[
		'version' => '1.1.4',
		'date'    => '2026-04-03',
		'summary' => 'Diagnostika synchronizace a výpis ovlivněných formulářů.',
		'items'   => [
			'Byla rozšířena stránka Synchronizace o výpis konkrétních formulářů, na které se změna termínů propisuje.',
			'Vznikla lepší diagnostika nalezených a chybějících polí ve formulářích.',
			'Bylo zpřesněno párování sync polí podle name, admin field label i labelu.',
			'Přibyly pomocné informace pro zjištění, který formulář obsluhuje který typ kvalifikace.',
		],
	],
	[
		'version' => '1.1.3',
		'date'    => '2026-04-03',
		'summary' => 'Správa propojených formulářů a první rozsáhlejší admin informační vrstva.',
		'items'   => [
			'Byla přidána stránka Info o HLAVASovi s technickými údaji o pluginu, prostředí a napojených formulářích.',
			'Na admin obrazovkách se začaly zobrazovat informace o konfiguraci synchronizace a nalezených polích.',
			'Proběhly první větší UX/UI úpravy admin tabulek a rozložení výpisů.',
			'Zpřesnilo se dohledání formulářů a metadat pro další provozní diagnostiku.',
		],
	],
	[
		'version' => '1.1.2',
		'date'    => '2026-04-03',
		'summary' => 'Účastníci, kapacity a data z Fluent Forms.',
		'items'   => [
			'Byla přidána plnohodnotná stránka Účastníci.',
			'Plugin začal zpracovávat Fluent Forms entries pro výpis účastníků a kontrolu obsazenosti.',
			'Došlo k úpravě synchronizace kapacit a výpisů termínů podle reálných registrací.',
			'Byly doplněny první filtry a reportovací pohledy nad účastníky a obsazeností.',
		],
	],
	[
		'version' => '1.1.1',
		'date'    => '2026-04-03',
		'summary' => 'Vyladění typu kurzů a admin tabulek.',
		'items'   => [
			'Byla upravena stránka Typy kurzů tak, aby formulář pro přidání a editaci běžel logičtěji pod tabulkou.',
			'Do výpisu typů přibyly samostatné počty termínů kurzů a termínů zkoušek ve formátu budoucí / celkem.',
			'Byly doladěny tabulky Přehled termínů a Obsazenost tak, aby lépe pracovaly s dlouhými názvy a sloupci.',
			'Po uložení existujícího termínu začal plugin vracet uživatele do odpovídajícího seznamu kurzů nebo zkoušek.',
		],
	],
	[
		'version' => '1.1.0',
		'date'    => '2026-04-03',
		'summary' => 'Zavedení typů kvalifikací a nové datové struktury pluginu.',
		'items'   => [
			'Byla přidána samostatná databázová tabulka pro typy kvalifikací.',
			'Termíny byly rozšířeny o vazbu na typ kvalifikace, uzávěrku přihlášek a viditelnost na webu.',
			'Do pluginu byly vloženy čtyři výchozí kvalifikační typy jako seed data.',
			'Vznikla nová menu struktura s Typy kurzů, Termíny kurzů, Termíny zkoušek, Obsazeností a Účastníky.',
			'Byl položen základ pro párování různých Fluent Forms formulářů ke konkrétním kurzům a zkouškám.',
		],
	],
	[
		'version' => '1.0.5',
		'date'    => '2026-04-03',
		'summary' => 'Stabilizace editace termínů a zlepšení provozních přehledů.',
		'items'   => [
			'Do přehledů termínů byla přidána informace o poslední FF synchronizaci.',
			'Do editace termínu přibylo tlačítko Synchronizace do FF s vysvětlením dopadu na formulář.',
			'Do přehledu termínů byl přidán sloupec počtu přihlášených vedle kapacity.',
			'Byl doladěn návrat po editaci termínu zpět do správného seznamu kurzů nebo zkoušek.',
		],
	],
	[
		'version' => '1.0.4',
		'date'    => '2026-04-03',
		'summary' => 'Nastavení pluginu, informační stránka a práce s fallback formulářem.',
		'items'   => [
			'Vznikla první stránka Nastavení pluginu se správou fallback Form ID a debug režimu.',
			'Byla přidána interní informační stránka pro správce a programátora.',
			'Do menu pluginu byly přidány nové položky Nastavení a Info o HLAVASovi.',
			'Začala se zobrazovat základní metadata pluginu, licence, kompatibilita a provozní informace.',
		],
	],
	[
		'version' => '1.0.3',
		'date'    => '2026-04-03',
		'summary' => 'Kompatibilita s PHP 8.4+ a opravy editorových chyb.',
		'items'   => [
			'Proběhla kontrola a oprava chyb napříč soubory hlášených ve VS Code / Intelephense.',
			'Byly doplněny explicitní require vazby a anotace pro lepší statickou analýzu a PHP 8.4 kompatibilitu.',
			'Celý plugin byl opakovaně lintován proti Local PHP 8.4 a 8.2.',
			'Došlo k prvním opravám synchronizace a dohledávání závislostí napříč pluginem.',
		],
	],
	[
		'version' => '1.0.2',
		'date'    => '2026-04-03',
		'summary' => 'Metadata pluginu, autor a kontaktní informace.',
		'items'   => [
			'Byla upravena hlavička pluginu s konečnými údaji o autorovi, JRDM a projektu HLAVAS.cz.',
			'Do pluginu byly zapsány kontaktní e-maily autora i organizace.',
			'Verze v plugin headeru a interní konstantě byla sjednocena.',
		],
	],
	[
		'version' => '1.0.1',
		'date'    => '2026-04-03',
		'summary' => 'První provozní úpravy adminu a názvu pluginu.',
		'items'   => [
			'Proběhly první změny názvu pluginu a základního menu v administraci.',
			'Byly laděny texty v plugin headeru a první provozní metadata.',
			'Došlo k prvotní kontrole syntaxe pluginu a ověření, že se načítá ve WordPress adminu.',
		],
	],
	[
		'version' => '1.0.0',
		'date'    => '2026-04-03',
		'summary' => 'První vydání pluginu HLAVAS – Správa termínů kurzů a zkoušek.',
		'items'   => [
			'Vznikla základní struktura pluginu, hlavní bootstrap a repository vrstva.',
			'Byla zavedena databázová tabulka termínů a základní admin správa termínů.',
			'Plugin získal první integraci s Fluent Forms pro synchronizaci termínů do formuláře.',
			'Vznikly první výpisy kurzů, zkoušek a kapacit v administraci.',
		],
	],
];

=== HLAVAS - Správa termínů ===
Contributors: hlavas
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.4
Stable tag: 1.3.5
License: GPLv2 or later

Centrální správa termínů kurzů a zkoušek se synchronizací do Fluent Forms.

== Popis ==

Plugin přesouvá správu termínů kurzů a zkoušek z ručního nastavování uvnitř
Fluent Forms do samostatného administračního rozhraní. Fluent Forms pak slouží
pouze jako prezentační a sběrná vrstva.

== Architektonický návrh ==

=== Klíčové třídy ===

1. **Hlavas_Terms_Activator** – vytvoření DB tabulky, seed data
2. **Hlavas_Terms_Repository** – CRUD operace nad tabulkou `wp_hlavas_terms`
3. **Hlavas_Terms_Label_Builder** – generování českých labelů z datumů
4. **Hlavas_Terms_Fluent_Sync_Service** – čtení/zápis options + inventory do Fluent Forms
5. **Hlavas_Terms_Availability_Service** – výpočet obsazenosti z Fluent Forms entries
6. **Hlavas_Terms_Submit_Validator** – serverová validace kapacity při odeslání formuláře
7. **Hlavas_Terms_Admin** – admin menu, stránky, zpracování formulářů

=== Data flow ===

  [Admin: správa termínů]
        ↓
  [wp_hlavas_terms tabulka] ← source of truth
        ↓
  [Sync Service] → přepíše options + inventory v Fluent Forms (form_fields JSON)
        ↓
  [Fluent Forms] ← zobrazuje dropdown, počítá inventory
        ↓
  [Submit Validator] ← záložní kontrola kapacity při odeslání

== Datový model ==

Tabulka: `wp_hlavas_terms`

| Sloupec     | Typ              | Popis                                  |
|-------------|------------------|----------------------------------------|
| id          | bigint PK AI     |                                        |
| term_type   | varchar(20)      | 'kurz' nebo 'zkouska'                 |
| term_key    | varchar(100) UQ  | Stabilní ID, např. kurz_2026_04_17_19 |
| title       | varchar(255)     | Interní admin název                    |
| label       | varchar(255)     | Text pro uživatele v dropdownu         |
| date_start  | date             | Datum zahájení                         |
| date_end    | date NULL        | Datum ukončení (kurzy)                 |
| capacity    | int unsigned     | Maximální počet účastníků              |
| is_active   | tinyint(1)       | Aktivní/neaktivní                      |
| is_archived | tinyint(1)       | Archivováno                            |
| sort_order  | int              | Pořadí v dropdownu                     |
| notes       | text NULL        | Interní poznámky                       |
| created_at  | datetime         |                                        |
| updated_at  | datetime         |                                        |

Indexy:
- PRIMARY KEY (id)
- UNIQUE KEY (term_key) – pro stabilní identifikaci
- KEY (term_type, is_active, is_archived) – pro filtrování syncable termínů
- KEY (date_start) – pro řazení a filtrování budoucích
- KEY (sort_order) – pro řazení

== Synchronizační strategie ==

=== Přístup: Cesta C – přímý zápis do Fluent Forms ===

Plugin při synchronizaci:
1. Načte formulář z `fluentform_forms` (ID 3)
2. Parsuje `form_fields` JSON
3. Najde pole `termin_kurz` a `termin_zkouska` podle `admin_field_label`
4. Přepíše `settings.advanced_options` – array options s label/value/calc_value
5. Přepíše `settings.inventory_settings` – Simple režim se stock_quantity per option
6. Uloží JSON zpět do `fluentform_forms.form_fields`

=== Co synchronizace NEPŘEPISUJE ===

- Conditional logic (zůstává na poli termin_zkouska / termin_kurz)
- Ostatní pole formuláře
- Nastavení formuláře (emaily, PDF, integrace)
- Stávající entries/submissions

=== Režimy hodnot (value) ===

**Nový režim (doporučeno):**
- option value = term_key (např. `kurz_2026_04_17_19`)
- Stabilní, nezávislé na textu labelu

**Legacy režim:**
- option value = label text (např. `kurz: 17. - 19. dubna 2026`)
- Kompatibilní se stávajícími záznamy

== Strategie kapacit ==

=== Varianta A (primární): Fluent Forms Inventory ===

Synchronizace zapisuje do `inventory_settings` v každém dropdown poli:
- enabled: "simple"
- stock_quantity: [{ value, quantity }] pro každý termín
- stock_out_message: české hlášení o plném termínu

Fluent Forms pak sám hlídá kapacity při zobrazení formuláře.

=== Varianta B (záložní): Submit Validator ===

Třída `Hlavas_Terms_Submit_Validator` se hookuje na `fluentform/validation_errors`
a při odeslání formuláře kontroluje:
1. Zda vybraný termín existuje v naší tabulce
2. Zda má ještě volnou kapacitu (počet entries < capacity)

Tato validace funguje jako dvojitá pojistka i při použití Varianty A.

== Zpětná kompatibilita ==

Stávající entries obsahují textové hodnoty labelů. Při synchronizaci v "legacy"
režimu se value = label, takže stávající data zůstanou konzistentní.

Při přechodu na nový režim (value = term_key):
1. Staré entries budou mít jiný formát value
2. Availability Service prohledává jak term_key, tak label
3. Doporučeno: při přechodu spustit jednorázovou migraci entries

== Rizika a místa k ověření ==

1. **Interní struktura form_fields**: Plugin přistupuje k JSON struktuře
   `settings.advanced_options` a `settings.inventory_settings`. Při upgradu
   Fluent Forms na novou major verzi může dojít ke změně struktury.
   → Použijte debug režim synchronizace k ověření.

2. **Inventory formát**: Struktura `inventory_settings` je odvozena ze
   screenshotů a reverzního inženýrství. Fluent Forms Pro toto nedokumentuje.
   → Vždy nejprve spusťte Preview, pak teprve Execute.

3. **Conditional logic**: Synchronizace nepřepisuje conditional logic.
   Pokud se změní element name dropdown polí, může se logic rozbít.
   → Nepřejmenovávejte admin_field_label polí ve Fluent Forms.

4. **Vnořená pole**: Pokud jsou dropdown pole uvnitř kontejnerů (columns),
   sync service prochází i vnořené fields. Pokud jsou ale hlouběji,
   může být potřeba rozšířit rekurzivní vyhledávání.

5. **Počítání obsazenosti**: Availability Service hledá v `fluentform_entry_details`
   nebo ve `fluentform_submissions.response` JSON. Pokud Fluent Forms
   neukládá entry_details pro dropdown pole, použije se fallback na response JSON.

6. **Conversational form**: Formulář používá `[fluentform type="conversational"]`.
   Synchronizace pracuje se stejnými form_fields, ale renderování v
   conversational módu může mít odlišnosti – otestujte po synchronizaci.

== Implementační kroky ==

1. ✅ Plugin bootstrap a DB tabulka
2. ✅ CRUD administrace termínů
3. ✅ Servis pro načítání budoucích aktivních termínů
4. ✅ Preview synchronizace
5. ✅ Zápis do Fluent Forms options
6. ✅ Inventory synchronizace (Varianta A)
7. ✅ Submit validace kapacity (Varianta B záloha)
8. ✅ Obsazenostní report

== Struktura souborů ==

hlavas-terms-manager/
├── hlavas-terms-manager.php      # Hlavní plugin soubor
├── readme.txt                     # Tento soubor
├── includes/
│   ├── class-activator.php        # DB tabulka + seed data
│   ├── class-repository.php       # CRUD operace
│   ├── class-label-builder.php    # Generování českých labelů
│   ├── class-fluent-sync-service.php # Sync do Fluent Forms
│   ├── class-availability-service.php # Výpočet obsazenosti
│   └── class-submit-validator.php # Záložní validace kapacity
├── admin/
│   ├── class-admin.php            # Admin controller
│   ├── views/
│   │   ├── list.php               # Seznam termínů
│   │   ├── edit.php               # Editace termínu
│   │   ├── sync.php               # Synchronizační stránka
│   │   └── availability.php       # Přehled obsazenosti
│   └── assets/
│       └── css/
│           └── admin.css          # Admin styly
└── languages/                     # Překlady (připraveno)

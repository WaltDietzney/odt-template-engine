# H01 — Native Section-Erzeugung: Abschlussnachweis

Stand: 17.11.2026. Baseline: `180d423` auf `feature/section-authoring-01`.
Kein Commit, Push oder Merge aus diesem Nachweis.

## Scope und Wiederverwendung

H01 verwendet die bereits angenommenen SA-01/SA-02-Oberflächen und die
SECTION-03-Collection-Semantik. Es wurde keine neue API eingeführt und keine
declarative `foreach`- oder Mapping-Semantik ergänzt.

Die relevante Kette ist:

```text
PHP erzeugt verschachtelte SectionElement-Struktur
    → Section-Styles mit Spalten werden document-local materialisiert
    → ODT speichern
    → Writer öffnen/speichern
    → OdtTemplate laden und inspect()
    → optionale Gruppe mit 0/1/n Stationen finalisieren
    → speichern, erneut laden, inspizieren
```

## Nachweis

`tests/Integration/H01SectionAuthoringClosureTest.php` erzeugt aus dem
sauberen Minimal-Shell des C06-Templates ein isoliertes Template mit:

- `H01StationsGroup` (2 Spalten, Gap, Hintergrund, Balancing);
- verschachteltem `H01Station` (3 Spalten, relative Breiten, Gap);
- einem Absatz mit dem vorhandenen Writer-Absatzstil `Heading 1` innerhalb des
  wiederholbaren Station-Subtrees. Die öffentliche Element-API bietet hierfür
  keinen separaten nativen `text:h`-Konstruktor; der Nachweis bezeichnet ihn
  deshalb ausdrücklich als styled paragraph.

Der immer laufende Engine-Test und der optionale Writer-Test sind getrennt. Die
Style-Prüfung löst für jede Section deren tatsächliches
`text:style-name` per namespace-aware DOMXPath auf und prüft anschließend die
zugehörigen `style:section-properties`: Spaltenanzahl, normalisierte Gewichte,
Gap, Hintergrund, Balancing und die verschachtelte Section. Sie verlässt sich
nicht auf die vor Writer vergebenen Style-Namen; Writer darf diese etwa in
`Sect1`/`Sect2` umbenennen.

Der Engine-Test befüllt 0, 1 und 3 Stationen, speichert jede Variante, lädt sie
erneut und prüft dann die exakten Stationsdaten und Überschriften, keine
unaufgelösten Platzhalter im gesamten `office:text` sowie leere Diagnosen. Die
Prüfung auf fehlende `STATION HEADING` läuft nur für den Nullfall. Ein leerer
äußerer Collection-Lauf entfernt zusätzlich die komplette optionale Gruppe.

Die bestehenden SECTION-03-Tests bleiben der Nachweis für 0/1/n-, N×M-,
Rollback-, Save/Reopen- und Owner-Isolation-Semantik. S01b bleibt der CV-
Referenzfall; seine Samples, Locks und vorhandenen `tmp/`-Artefakte wurden
nicht verändert.

## Ausführung und Grenze

Fokussierter Test:

```sh
./vendor/bin/phpunit tests/Integration/H01SectionAuthoringClosureTest.php
```

Der Engine-Test lief am 17.11.2026 mit 115 Assertions erfolgreich. Der
separate Writer-Test ist in dieser Umgebung mit 0 Assertions übersprungen,
weil LibreOffice wegen schreibgeschütztem dconf unter `/run/user/1000` keinen
Writer-Roundtrip erzeugen konnte. Das ist ein Umgebungsblocker; eine Writer-
Abnahme wird daraus nicht abgeleitet. Unabhängige Host-Prüfung:

```sh
export H01_LO_PROFILE="$(mktemp -d /tmp/h01-writer-profile.XXXXXX)"
export H01_HOME="$(mktemp -d /tmp/h01-writer-home.XXXXXX)"
export H01_XDG="$(mktemp -d /tmp/h01-writer-xdg.XXXXXX)"
HOME="$H01_HOME" XDG_CONFIG_HOME="$H01_XDG" \
  ./vendor/bin/phpunit tests/Integration/H01SectionAuthoringClosureTest.php
```

Für reproduzierbare, nicht destruktive visuelle Kandidaten erzeugt der
Engine-Test mit einem expliziten Zielverzeichnis je eine neue Datei für 0, 1
und 3 Stationen:

```sh
H01_VISUAL_DIR=tmp/visual-regression/candidates \
  ./vendor/bin/phpunit --filter testEngineLifecyclePopulatesZeroOneAndThreeStationsAfterSaveAndReopen \
  tests/Integration/H01SectionAuthoringClosureTest.php
```

Die erzeugten Dateien liegen unter `tmp/visual-regression/candidates/` mit
Zeit-/Zufallssuffix; bestehende Samples und Visualisierungsartefakte werden
nicht überschrieben. Die Pagination bleibt Writer-Verantwortung.

## Abgrenzung

H01 umfasst native Sections, Verschachtelung, document-local Section-Styles,
Spalten, Collection-Lifecycle und Writer-Roundtrip. H02 (Seitenlayout und
Frame-Komposition) ist nicht Teil dieses Nachweises. B5-
Template-Structure-Convenience, automatische rekursive Datenmappings,
Collection-Handles, spätere Fields/Foreach-Erweiterungen sowie DOE-spezifische
Engine-Klassen bleiben deferred.

## Referenzfixture: Human Gate

`tests/fixtures/libreoffice-reference/odt/SECTION-AUTHORING-01-reference.odt`
wurde nicht verändert. Vor einer öffentlichen Freigabe muss ein Mensch die
eingebettete PNG-Ressource auf Provenienz/Lizenz und die `meta.xml`-Daten auf
persönliche oder umgebungsbezogene Metadaten prüfen. Bis zu dieser Entscheidung
ist die Fixture ein lokaler Forschungsnachweis und kein unbesehenes
Public-Release-Asset.

## Independent final acceptance — 2026-11-17

Host Writer round-trip and engine lifecycle: 2 tests, 295 assertions, no
failures or skips. Writer renames automatic styles and normalizes hexadecimal
color case; assertions resolve actual references and compare color semantically.
Column gaps are checked, including Writer length rounding within 0.002 cm.
Full host `composer test`: 1037 tests, 8276 assertions, exit 0; existing one
warning, four deprecations and eight PHPUnit deprecations. PHP lint: 371 files,
exit 0. Composer strict validation and git diff --check: passed. Zensical is
unavailable; documentation build remains unverified.

Visual inspection of all three complete Writer-derived PNG pages under
`tmp/visual-regression/images/` (h01-writer-0/1/3-20261117184230): one page each;
zero stations leaves a blank colored outer band, with no heading or placeholder.
One and three stations show all headings and details in order, without clipping
or overlap. Remove the outer optional group to suppress its background as well.
These are first acceptance candidates, not pixel comparisons against a baseline.
No production engine code changed in closeout. Technical GO for internal Forgejo
integration into develop; user authorized PR preparation, merge remains manual.
Public mirror/release remains gated by reference fixture provenance and metadata.

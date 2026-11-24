# P2-I01a — Native User-Field-Konstruktion umgesetzt

24.11.2026. Baseline f8679a782a23b82ca2b9a0536d7affff6554301c,
architecture/p2-capability-audit. Status: **im P2-Worktree implementiert und auf
PHP 8.5.4 technisch geprüft**.
Der Bericht wurde vor Versionierung erstellt. Der Owner hat anschließend die
Aufteilung in Konsolidierungs- und Implementierungscommit sowie den Branch-Push
zu Forgejo freigegeben. Merge/Deployment bleiben ausgeschlossen.
Owner-Auftrag: den besprochenen Vorschlag umsetzen. Maßgeblich ist CC01.

## Ergebnis

```php
$template->declareUserField('contact.email', 'ada@example.invalid');
$template->appendBody((new Paragraph())
    ->addText('E-Mail: ')
    ->addUserField('contact.email', ['color' => '#375E80'])
    ->addText(' / erneut: ')
    ->addUserField('contact.email'));
$template->setUserField('contact.email', 'neu@example.invalid');
```

OdtTemplate delegiert die Deklaration an den bestehenden UserFieldBinder, der
UserFieldAnalyzer und dieselben Dokumentregionen wiederverwendet. Es gibt keinen
neuen Binder, keine neue Registry und keine neue Produktionsklasse. Neue String-
Deklarationen liegen im Body; vorhandene kompatible Deklarationen in Body/Kopf-/
Fußzeilen werden bei identischem Wert wiederverwendet. Abweichende Werte werden
nicht überschrieben; explizite Änderungen bleiben Aufgabe von setUserField.
Ungültige XML-Zeichen/UTF-8 und leere Namen werden vor Mutation abgewiesen.
Konflikte, verwaiste Referenzen und nicht unterstützte Typen werden nicht repariert.

Paragraph serialisiert Referenzen als eigene geordnete Textteile mit vorhandener
Textstilverwaltung. addText/addUserField/addText erhält die Reihenfolge. Referenzen
haben zunächst keinen Anzeigecache; Writer wertet die Deklaration aus. applyTextStyle
berücksichtigt Felder. Das Legacy-Verhalten eingebetteter Elemente bleibt erhalten.
Ein beim Randwerttest belegter Fehler im bestehenden Analyzer wurde eng korrigiert:
PHP macht numerische String-Arraykeys zu Integern; Feldnamen werden vor dem typisierten
Semantikpfad wieder als Strings behandelt. 0, 123 und 01 bleiben getrennte Identitäten.
Diese Korrektur ist bewusst enthalten und durch einen Regressionstest abgesichert.

## Nachweise

- Neue fokussierte Integrationstests: **10 Tests / 55 Assertions**. Konstruktion,
  wiederholte Deklaration, Konflikte ohne Teilmutation, Reopen/Bind, mehrere Referenzen,
  Unicode/XML-Zeichen, lange/leere Werte, Textstile, numerische Namen und Body/Header/Footer.
- Vollständiges composer test: **1112 Tests / 8990 Assertions**, keine Fehler.
  PublicSampleSmokeTest ist enthalten und wurde auch separat protokolliert.
- Vorhandene Hinweise bleiben offen: eine mkdir-Warnung aus S03-Testkopierlogik,
  vier PHP-Deprecation-Quellen (nullable Legacy-Signatur/Reflection auf PHP 8.5),
  acht PHPUnit-Hinweise zu bestehenden Docblock-Metadaten. Kein stilles Bereinigen
  dieser unabhängigen Legacy-Stellen. Details: user-fields/full-suite.log.
- PHP-Lint src/tests: **388 Dateien**, kein Syntaxfehler; composer validate und
  git diff --check bestanden. Strikter Dokumentations-Build separat protokolliert.
- Direkte öffentliche Konstruktion mit Body/Kopf-/Fußzeile: drei Lifecycle-Dokumente,
  je drei Deklarationen/fünf Referenzen; vollständige PDFs, native Writer-Erhaltung,
  XML-stabile Wiederholung und drei bildidentische Roundtrip-Seiten.
- Alle **10 Kontaktvarianten** isoliert auf öffentliche Feldkonstruktion migriert.
  Vollständige Feldinhalte, fehlende Zeilen, Ressourcen, wiederholtes Speichern und
  56.4pt zusätzlicher Fluss bei langen Daten geprüft. Zehn Roundtrip-Seiten sowie
  alle zehn gespeicherten Owner-Referenzen sind bildidentisch. Sidebar, horizontale
  Leiste und Langtext-Render wurden zusätzlich technisch visuell angesehen.
- Sämtliche vor Konsolidierung inventarisierten Research-Dateien unverändert.
  Keine Sample-Outputs und kein ursprünglicher Projektworktree geändert.

## Bewusste Grenzen und verbleibende Gates

Fachliche Kontaktklassen bleiben Consumer-Verantwortung. Die isolierte Migration
ersetzt Felddeklaration/-serialisierung; DrawingParagraph dekoriert nur noch native
Paragraphen. FlowTextBox, ForegroundImage und DrawingGroup bleiben als bestehende
Research-Hilfen bis zu den dafür vorgesehenen P2-Paketen erhalten. Der vorhandene
Name-Header im Kontaktseed bleibt authored; alle Kontaktdeklarationen entstehen
über die neue Engine-Methode. Kein nachträgliches Patchen gerenderter Ausgaben.

Metadaten bleiben unabhängig. Keine numerischen/Datumsfelder, automatische
Zeilenentfernung bei leerem Binding oder foreach-lokalen Felddefinitionen.
Referenzserialisierung registriert kein Feld implizit; zuerst deklarieren.

Runtime-Ausführung auf PHP 8.2 ist noch offen (Patil stellt hier PHP 8.5 bereit).
Interaktive Writer-Bearbeitung/GUI-Abnahme ist ebenfalls nicht durchgeführt:
kein aktiver Desktop in dieser Sitzung. Native Roundtrip-/PDF-Nachweise und
technische Sichtprüfung sind keine behauptete manuelle GUI-Prüfung. H10 bleibt
ein separater Integrations-/Human-Gate-Schritt. Der Slice ist implementiert,
aber dadurch nicht automatisch release- oder mergefreigegeben.

## Artefakte und Fortsetzung

research/p2-capability-audit/user-fields/ enthält Konstruktion/Metadatenkontrolle,
Tests, Render und Preflight. integration/ enthält die isolierte Kontaktmigration.
Reproduktion im Worktree: `research/p2-capability-audit/user-fields/README.md`.
Nächster Funktionsslice: P2-I01b gemäß CC02, native Heading-Konstruktion.
Die übrigen P2-Verträge bleiben bestehen; keine erneute Shapes-/Kontaktforschung.

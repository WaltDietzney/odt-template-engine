# P2-I01b — Native Überschriften-Konstruktion

Stand: 24.11.2026. Baseline 473ea17705288ad431c3960835fafa05ec2d5340,
Branch architecture/p2-capability-audit, P2-Worktree auf Patil.
Status: **implementiert und technisch geprüft; noch nicht committet/gepusht**.
Maßgeblich ist CC02; H09-Gestaltung und native Machbarkeit bleiben abgenommen.

## Implementierung

```php
$heading = (new Paragraph(null, ['font-size' => '15pt', 'bold' => true]))
    ->setOutlineLevel(1)
    ->addText('Berufserfahrung');
$template->appendBody($heading);
```

Die einzige geänderte Produktionsklasse ist Paragraph. setOutlineLevel akzeptiert
1–10 oder null. Ein gesetzter Wert erzeugt ein natives text:h mit outline-level;
null erzeugt wieder text:p. Ungültige Ebenen werden vor Änderung abgewiesen.
Keine neue Heading-Klasse, kein Constructor-Wechsel, keine Stilnamenheuristik.
Stile und vorhandene Subklassen-/Listen-/Textboxpfade bleiben erhalten.
Ein bestehender Heading-Stil erzeugt ohne explizite Ebene weiterhin text:p.
Writer-eigene Stilvorgaben werden nicht heimlich umgeschrieben.

## Nachweise

- Vier Characterization-Tests vor Änderung: 18 Assertions, bisheriges Absatz-,
  Stil-, Listen-, Textbox- und Subklassenverhalten charakterisiert.
- Fokussiert danach: **11 Tests / 170 Assertions**. Ebenen 1–10, Stilunabhängigkeit,
  ungültige Ebenen ohne Teilmutation, null-Reset, RichText, native Felder,
  Placeholder-Materialisierung, Inline-Frame in text:h, Header/Footer-Serialisierung,
  wiederholtes Speichern und Reopen.
- Vollständiges composer test: **1123 Tests / 9160 Assertions**, keine Fehler;
  PublicSampleSmokeTest enthalten. Bekannte Warnquellen bleiben dokumentiert:
  eine S03-mkdir-Warnung, vier PHP-Deprecation-Quellen und acht PHPUnit-Hinweise.
- Fünf native ODT-Lifecycle-Dokumente, je vollständige PDFs und echte PDF-Outlines.
  Fünf Writer-Roundtrip-Seiten sind bildidentisch; alle drei dekorierten Varianten
  entsprechen zusätzlich pixelgenau der akzeptierten H09-Referenz.
- Die fünf dekorativen Shapes sind unverändert und textlos. Labels erscheinen
  einmal als sichtbarer Body-Text. Der Ebenen-Nachweis erhält auch eine native
  Feldreferenz, deren aktueller Wert im PDF-Lesezeichen erscheint.
- PHP-Lint, Composer-Validierung, Python-Syntax, Diff-Prüfung und strikter Docs-Build
  sind im zugehörigen preflight.json bzw. den Logs einzeln protokolliert.
- Abgenommene Forschungsdateien und Sample-Outputs bleiben unangetastet.

## Grenzen

Der Research-Adapter AcceptedHeadingDecoration transportiert ausschließlich die
bereits abgenommene native Dekoration aus dem vorbereiteten Input. Die Engine
konstruiert die Überschriften selbst. Keine XML-Nachbearbeitung gespeicherter Outputs.
Die allgemeine Grafikkomposition bleibt CC03/CC04; diese Probe ist keine neue
Produktions-DrawingGroup-API. Der bekannte 0.45cm-Offset bleibt referenzspezifisch.

Das Body-Heading ist der Gliederungsträger. Heading-Serialisierung in Kopf-/Fußzeilen,
Textframes oder Shapes bedeutet keine zugesicherte Aufnahme in die Body-Gliederung.
Kein automatisches Fit/Shrink bei langen Labels, keine PDF/UA-Zertifizierung.

PHP 8.2 wurde hier noch nicht ausgeführt; Patil stellt PHP 8.5 bereit. Interaktive
Writer-Bearbeitung und weitere Owner-/H10-Gates sind nicht als bestanden behauptet.
Die Renderer-Sichtprüfung und pixelgenauen Referenzen ersetzen keine GUI-Abnahme.
Commit, Push, Merge und Deployment wurden für diesen Slice nicht ausgeführt.

## Übergabe

Reproduktion und Artefakte:
`research/p2-capability-audit/headings/README.md` und die dortigen Skripte,
ODT/Writer/PDF/PNG-Dateien, verification.json und preflight.json.
Nächster Funktionsslice ist **P2-I02 / CC03: geordnete Komposition und Writer-Rahmen-Sizing**.
Keine erneute Untersuchung der abgenommenen H09-Geometrie oder User Fields.

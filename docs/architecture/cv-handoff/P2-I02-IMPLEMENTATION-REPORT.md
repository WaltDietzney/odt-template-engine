# P2-I02 — Geordnete Inline-Komposition und Writer-Rahmen

24.11.2026. Baseline 473ea17705288ad431c3960835fafa05ec2d5340 plus erhaltenem,
noch nicht committetem P2-I01b. Branch architecture/p2-capability-audit.
Status: **im P2-Worktree implementiert; keine neuen Commits/Pushes**.
Contract: CC03. Keine Wiederholung abgenommener Shape-/Tag-/Kontakt-Geometrien.

## Implementierung in zwei getrennten Schritten

1. Paragraph::addInlineElement fügt as-char Bilder/Frames an der aktuellen Position
   der Textteile ein. Native Felder bleiben über addUserField geordnet. addElement
   bleibt Legacy-Tail und wird nicht umsortiert. Die vorhandene Ownership liefert
   Stile, Bilder und Fill-Ressourcen auch für neue Inline-Kinder, ohne doppelte
   Mutable-State-Ablage. Anker und resultierender Frame werden vor Ausgabe geprüft.
2. DrawTextBox::setTextBoxSizing und setFrameSpacing ergänzen native Mindestgrößen,
   Auto-Grow-Flags und explizite Seitenabstände. Objektmaße bleiben bei FrameLayout.
   Keine neue Produktionsklasse, keine neue Layoutengine oder RichText-Duplikation.
   Alte Raw-Optionen, Default-Serialisierung und protected Style-Hooks bleiben erhalten.

```php
$frame->setFrameLayout(['anchor' => 'as-char', 'width' => '4cm', 'height' => '0.1cm'])
    ->setTextBoxSizing(['min-height' => '0.1cm', 'auto-grow-height' => true])
    ->setFrameSpacing(['padding' => '0.152cm', 'margin-left' => '0.08cm']);
$paragraph->addText('Vor ')->addInlineElement($frame)->addText(' nach');
```

## Evidenzbedingte Begrenzung: keine verlässlichen Maximalmaße

ODF 1.2 Part 1, 10.4.3 erlaubt min/max auf draw:text-box. Die tatsächliche Writer-
Verarbeitung ist enger: 4cm min/max plus draw:auto-grow-width=false verlor den
Maximalwert und verbreiterte den Rahmen über den Absatz. Das ist als negativer
Nachweis mit Original-ODT, Writer-Datei, PDF und PNG erhalten. Keine zugesicherte
Maximalbegrenzung wird aus bloßer Schema-Gültigkeit abgeleitet.

Die neue API unterstützt daher positive min-width/min-height und boolesche
Auto-Grow-Flags; max-width/max-height werden abgewiesen. Ein natives Minimum
mit explizitem false-Grow derselben Achse wird ebenfalls als widersprüchlich
abgewiesen. Für feste Breite: svg:width ohne fo:min-width; Mindesthöhe lässt den
Text natürlich umbrechen und wachsen. Kapazitäts-/Regionprüfung bleibt Aufgabe
des Consumer-Renderfeedbacks, nicht einer geratenen PHP-Zeilenhöhenberechnung.
Dieser Teil des ursprünglichen Maximum-Wunsches ist bewusst nicht als implementiert
oder Writer-seitig zuverlässig unterstützt markiert.

## Nachweise

- Vor Sizing-Änderung: zwei Characterization-Tests / acht Assertions für Default-
  Wrapper, feste Maße und unveränderte Raw-Carrier. Geordneter Pfad separat geprüft.
- Fokussiert: **10 Tests / 56 Assertions**, einschließlich Legacy-Reihenfolge,
  A–Bild/Frame/Feld–B, Ressourcen/Stile, native Heading-Carrier, Body/Header/Footer,
  Mutations-/Ankervalidierung, Ownership-Zyklen, Fehler ohne Teilmutation,
  Minima/Flags, Seitenabstände, Shorthand-Vorrang und Overlay-Reset.
- Vollständiges composer test: **1133 Tests / 9216 Assertions**, keine Fehler;
  PublicSampleSmokeTest enthalten. Bekannte Hinweise: eine S03-mkdir-Warnung,
  vier PHP-Deprecation-Quellen, acht PHPUnit-Deprecations; keine Nebenbereinigung.
- Fünf direkte ODT-Proben / sechs Writer-Roundtrip-Seiten bildidentisch. Alle
  90 Tags sind vollständig und atomar über zwei Seiten vorhanden; explizite
  Padding-/Margin-Werte bleiben effektiv erhalten. Langer Feldtext bewegt den
  Folgetext um 57.75pt. Positive PDFs ohne überlappende/off-page Wörter geprüft.
- Zehn Kontaktvarianten ohne FlowTextBox-Override: alle zehn Roundtrip-Seiten
  und gespeicherten Owner-Referenzen bildidentisch; Langtext bewegt Folgetext
  weiterhin um 56.4pt. Native Felder und vollständiges Weglassen bleiben erhalten.
- PHP-Lint, Composer-Validierung, Python-Syntax, Diff und strikter Docs-Build
  werden einzeln in flow/preflight.json protokolliert.
- Mehrseitige Tags und der breite Umbruchfall wurden technisch visuell angesehen.
  Bestehende abgenommene Research-Dateien und Sample-Outputs unverändert.

## Grenzen und Übergabe

Neue Bounds/Spacing sind ein expliziter Overlay-Pfad; [] entfernt den Overlay.
Schriftgrößen, Regionbreiten und Inhaltspolitik bleiben beim Consumer. Keine
allgemeine automatische Überlaufvermeidung, kein Shrink, keine Rundungs-Erweiterung.
Ein einzelner überfüllter Frame kann nicht beliebig über Seiten wachsen; das muss
Consumer-seitig erkannt und anders komponiert werden. Disabled ImageElements mit
Legacy-Leerabsatz-Ausgabe werden nicht als Inline-Frame akzeptiert und sind wegzulassen.

PHP-8.2-Ausführung und interaktive Writer-Bearbeitung bleiben offen. Native
Roundtrips/technische Sichtprüfung sind keine behauptete neue Owner-GUI-Abnahme.
Der negative Maximalmaß-Nachweis ist kein unterstützter Produktionspfad.

Artefakte/Reproduktion: `research/p2-capability-audit/flow/README.md`.
P2-I01b bleibt vollständig erhalten. Als Nächstes folgt **P2-I03 / CC04:
Grafikkontext, Zeichenreihenfolge und generische Komposition**; verbleibende
DrawingParagraph/ForegroundImage/DrawingGroup-Adapter nicht ungeprüft übernehmen.

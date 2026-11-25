# P2 — Konsolidierter Fähigkeits- und Übergabestand

## Aktueller Abschluss — 25.11.2026

P2-I01–I07 einschließlich H10 sind im vereinbarten Umfang umgesetzt und abgenommen. Beide Review-v2-Templates sind visuell akzeptiert; der Owner bestätigt die interaktive Writer-Prüfung. Versionierung/Veröffentlichung bleiben separat offen. Verbindlicher Status: [P2-Implementierungsabschluss](P2-CLOSEOUT-2026-11-25.md) und [maschinenlesbarer Stand](P2-CLOSEOUT-STATE.json). Offene Folgepunkte werden dort ausdrücklich aufgeführt.

### Historische Planung und Fortschreibung

Die nachfolgenden älteren Status- und Arbeitsaufträge sind historische Quellen; aktuelle Arbeit wird vom oben verlinkten Abschluss aus gewählt.


Stand: 24.11.2026. **Machbarkeit und Gestaltung H05–H09 sind abgenommen;
die notwendige Engine-Integration ist noch nicht vollständig implementiert.**

Diese Konsolidierung ist der aktuelle Einstieg. Historische Untersuchungen
und frühere offene Abnahmen werden durch spätere Owner-Entscheidungen nur
im ausdrücklich abgenommenen Umfang abgelöst. Keine erneute Grundlagenforschung
zu abgenommenen Shapes, Skillbars, Tags, Timelines oder Überschriften.

## Baseline und Freigabegrenzen

- Patil: /srv/projects/odt-template-engine-p2.
- Branch: architecture/p2-capability-audit.
- HEAD und abgefragtes Forgejo develop:
  f8679a782a23b82ca2b9a0536d7affff6554301c.
- Forgejo Walt/odt-template-engine ist der Entwicklungsmaßstab.
  GitHub WaltDietzney/odt-template-engine bleibt der separate öffentliche Spiegel.
- H00–H04 sind bestehende Grundlagen. Der ursprüngliche Worktree bleibt
  unverändert; P2-Artefakte und neue Dokumentation sind noch uncommittet.
- Die letzte Owner-Nachricht nimmt auch H09 ausdrücklich ab und beauftragt
  diese Konsolidierung. Sie genehmigt keinen Commit, Push, Merge oder Deployment.
- Ein akzeptierter nativer ODT-Nachweis ist weder eine vorhandene öffentliche
  Engine-API noch ein vollständiger Produktions-/Release-Preflight.

## Implementierungsfortschritt nach Konsolidierung

P2-I01a ist im Worktree implementiert, noch uncommittet: native String-Deklaration
über declareUserField und geordnete Paragraph-Referenzen über addUserField.
[Abschluss und Prüfgrenzen](P2-I01A-IMPLEMENTATION-REPORT.md).
G01 ist damit im vereinbarten Umfang integriert; die übrigen Integrationslücken
bleiben bestehen. Die folgenden Tabellen dokumentieren den Konsolidierungsstand
vor diesem ersten Implementierungsslice. Kontaktvarianten wurden isoliert migriert,
nicht die abgenommenen Originalartefakte überschrieben.

P2-I01b ist ebenfalls im Worktree implementiert: explizite native Heading-
Konstruktion über Paragraph::setOutlineLevel. [Bericht](P2-I01B-IMPLEMENTATION-REPORT.md).
G02 ist im vereinbarten Umfang integriert; allgemeine Grafikkomposition bleibt offen.

P2-I02 ist im Worktree umgesetzt: geordnete as-char-Komposition, native
Mindestgrößen und explizite Rahmenabstände. [Bericht](P2-I02-IMPLEMENTATION-REPORT.md).
Writer-Maximalmaße sind nach negativem Nachweis bewusst nicht als verlässliche
API freigegeben. G03/G04 sind im beschriebenen Umfang integriert; CC04 bleibt offen.

Spätere Owner-Präzisierung: abgerundete Auto-Grow-Tags sind benötigter Umfang.
[H08-R](H08-ROUNDED-TAGS-NATIVE-PROOF.md) liefert einen positiven nativen
Preset-Nachweis, vom Owner am 24.11.2026 visuell abgenommen. Historische Optional-Vermerke gelten
für diese neue Anforderung nicht weiter; Engine-Integration ist noch offen.

## Ergebnis je Bereich

| Bereich | Abgenommen und nachgewiesen | Bereits vorhandene Grundlage | Verbleibender Integrationsbedarf |
| --- | --- | --- | --- |
| H05 Foto/Überlagerung | Proportionale und explizite Bildgeometrie, native Bitmap-Füllungen, isolierte Füllressourcen und sichtbare Reihenfolge | ImageElement, CircularImageElement, ShapeAppearance, Ressourcen-/Fill-Dienste | Bitmap-Austausch-Lifecycle und Ressourcenisolation; konsistente Reihenfolge. Crop-Semantik ist kein abgeschlossenes Ergebnis. |
| H06 Indikatoren | Sterne, Segmente, voller Ring, nativer 70%-Ring | ShapeGeometry, ShapeElement, ShapeAppearance; implementierte SkillBarGroup | Begrenzte Teilring-Geometrie und Randwerte; keine erneute Entwicklung der vorhandenen Formen. Bewertungssemantik liegt beim Verbraucher. |
| H07 Timeline | Lineare und alternierende native Sections einschließlich mehrseitigem Fluss | SectionElement, Absatzfluss, Spalten und native Marker | Wiederverwendbares Consumer-Rezept, nachvollziehbare Markerposition, Lifecycle/0/1/n. Keine Tabellen- oder Fixed-Frame-Timeline. |
| H07 Kontakte | Kompakte native Feldkomposition, Sidebar-Formen, Beschriftungen, horizontale Varianten, lange/fehlende Werte | DrawTextBox, Paragraph, ImageElement, Shapes, setUserField | Native Felder konstruieren, Mindesthöhe, Inline-Reihenfolge und kontextgerechte Grafikstile; Research-Klassen sind noch keine öffentliche API. |
| H08 Tags | Rechteckige as-char Frames mit automatischem Wachstum, expliziten Innen- und Außenabständen | DrawTextBox, Paragraph/RichText, Inline-Insertion | Semantischer Sizing-/Padding-Pfad, geordneter Inline-Inhalt, Überbreite/Seitenwechsel/Lifecycle. Runde Auto-Grow-Tags bleiben optional zurückgestellt. |
| H09 Überschriften | Drei grafische Varianten und echte native Überschriften mit PDF-Gliederung, ohne doppelte Texte | Shapes, ShapeTextLayout; bestehende Verarbeitung von text:h | Begrenztes programmgesteuertes Heading-Authoring und Dekorationskomposition; kein neues Shape-System. |

Verbindliche Einzelrezepte: H05-CONSTRUCTION-RECIPE.md bis
H09-CONSTRUCTION-RECIPE.md. H07 und H09 besitzen zusätzliche explizite
[H07-Abschlussdokumentation](H07-CLOSEOUT-2026-11-24.md) und
[H09-Abschlussdokumentation](H09-CLOSEOUT-2026-11-24.md).

## Wichtige gemeinsame Architekturentscheidungen

1. **Bestehende Engine-Bausteine verwenden.** Keine parallele Dokumentengine,
   kein globaler Mutable State und kein pauschales Umschreiben bestehender Klassen.
2. **Fachliche Inhalte und visuelle Auswahl bleiben beim CV Generator Pro.**
   Kontaktrollen, Symbolfamilien, Bildanalyse, konkrete Maße, Farben,
   Bewertungsregeln und DOE gehören in die Consumer-/VTD-Schicht.
3. **Die Engine liefert generische native Fähigkeiten:** strukturierter Text,
   User Fields, Überschriften, Text-/Bildrahmen, Shapes, Layout, Stile und Ressourcen.
4. **DrawingGroup und Writer-Textrahmen sind unterschiedliche Carrier.**
   Die konkrete Probe verliert native User Fields in Shape-Text/draw:g,
   erhält sie im Writer-Textrahmen. Symbol und Hintergrund können gruppiert
   werden; Feldtext wird nicht stillschweigend durch normalen Text ersetzt.
5. **Grafische Beschriftung ist keine Dokumentüberschrift.**
   Für Gliederung: sichtbarer Body-text:h plus textlose Dekoration.
   Kein unsichtbares zweites Heading und kein doppelter Labeltext.
6. **Writer besitzt Textfluss und Pagination.** Keine geratenen Textzeilenhöhen,
   stillen Kürzungen oder automatischen Schriftverkleinerungen.
   Verfügbare Breiten sind bisher explizite Consumer-Layoutwerte.
7. **Style-/Ressourcenverantwortung ist kontextbezogen.** Ein Bild im
   Writer-Rahmen und ein Bild innerhalb einer Zeichengruppe können unterschiedliche
   effektive Style-Scopes benötigen. Bestehende Standalone-Pfade bleiben erhalten.
8. **Refactoring und Verhaltensänderung trennen.** Überraschendes Legacy-Verhalten
   charakterisieren; neue Semantik opt-in oder über einen klar abgegrenzten Pfad.
   Öffentliche und geschützte Erweiterungspunkte bleiben kompatibel.

## Bestätigte konkrete Lücken

| ID | Befund | Vertrag |
| --- | --- | --- |
| G01 | setUserField bindet vorhandene String-Felder; es erstellt weder Deklaration noch Referenz. appendBody erlaubt bisher keinen Textcache direkt im user-field-get. | CC01 |
| G02 | Paragraph mit Heading-Stil erzeugt text:p; vorhandenes text:h wird bereits verarbeitet. | CC02 |
| G03 | Paragraph serialisiert Textteile vor embeddedElements; beliebige Interleaving-Reihenfolge ist nicht zugesichert. | CC03 |
| G04 | DrawTextBox stellt fo:min-height am text-box-Carrier nicht bereit; raw Auto-Grow und semantische Eigenschaften sind nicht durchgängig dieselbe Fähigkeit. | CC03 |
| G05 | Kontext für Grafikstile, Foreground/Background und lokale Gruppenplatzierung wird bisher teilweise durch spezialisierte Komposition gelöst. | CC04 |
| G06 | Auf PHP 8.5.4 erkennt getimagesize die SVGs, der gemappte SVG-Preflight lehnt dieselben SVGs wegen des MIME-Switches ab. PHP 8.2 ist nicht nachgewiesen. | CC05 |
| G07 | Native Füllungen sind gezeigt, ihr sicherer Austausch mit gemeinsam genutzten Ressourcen und wiederholtem Speichern ist nicht fertig integriert. | CC05 |
| G08 | 70%-Ring-Pfad existiert nativ, ist aber keine allgemeine öffentliche Teilring-Fähigkeit. | CC06 |

Die Research-Adapter UserFieldParagraph, FlowTextBox, ForegroundImage und
DrawingGroup zeigen diese Integrationsstellen. Ihre Namen und Verteilung
werden nicht automatisch als neue Produktionsarchitektur übernommen.
ContactItem, ContactBlock und ContactStrip sind zunächst Consumer-Verantwortungen.

## Reproduzierbarkeit und Herkunft

Ein neuer isolierter Replay-Einstieg liegt im Repository unter
research/p2-capability-audit/consolidation/reproduce.py. Er transportiert die
historischen Skripte in einen neuen Ausgabebaum, pinnt den lokalen E011-Input
und verwendet die aktuelle Source-Baseline. Die abgenommenen Dateien werden
weder neu generiert noch überschrieben.

Ergebnis der Konsolidierungsprüfung:

- **19 ODT-Pakete** sind nach enger, definitionsbasierter Normalisierung
  zufälliger Legacy-Absatzstilnamen strukturell gleich.
- **18 Renderdokumente / 21 Seiten** bleiben im frischen Writer-Roundtrip
  bildidentisch.
- **17 gespeicherte Renderreferenzen / 20 Seiten** stimmen auch mit dem
  isolierten Replay bildidentisch überein.
- Für p2-v01-padding-spacing war kein ursprüngliches PDF auffindbar.
  Das abgenommene ODT ist vorhanden und strukturell reproduziert. Frische
  PDF-/PNG-Nachweise sind ausdrücklich als erneut erzeugt gekennzeichnet.
- **52 historische PDF-/PNG-Dateien** wurden aus dem alten Renderer-Verzeichnis
  geborgen und per SHA-256 gegen die Quelle geprüft.
- Ein lokales Checkpoint-Archiv mit **967 einzeln verifizierten Dateien**
  bewahrt den Zustand vor Konsolidierung. Das ersetzt keine bestätigte
  externe/off-host Sicherung.
- Der manuell in Writer geänderte Padding-Input ist byteidentisch zur
  gespeicherten Writer-Referenz. Seine Abweichung vom früheren temporären
  Generatoroutput ist nachvollziehbare Historie, kein still korrigierter Fehler.

StyleMapper erzeugt para_…-Namen mit random_bytes(). Ein neuer Lauf ist deshalb
nicht allgemein ZIP-/XML-byteidentisch. Der Vergleich normalisiert ausschließlich
diese bekannten generierten Identitäten anhand der exakten Definition und der
typisierten Referenzen; Inhalte, Größen, Reihenfolge und Bildbytes bleiben
vergleichspflichtig. Dies ist Vergleichslogik, keine Engine-Änderung.

Maschinelle Nachweise: evidence-before.json, latest-replay.json,
recovered-render-provenance.json, regenerated-render-provenance.json und der
darüber adressierte replay-report.json im Konsolidierungsverzeichnis.

## Bewusst offene Grenzen

- Keine automatische verfügbare-Breite-Ermittlung oder beliebige responsive Leiste.
- Kontaktleiste vorerst 1–3 Einträge; weitere Reihen verteilt der Consumer.
- Native Felder in Zeichnungstext/-gruppen bleiben ausgeschlossen.
- Ein bestehendes leeres Feld entfernt seine Zeile nicht automatisch;
  vollständiges Weglassen geschieht beim Aufbau bzw. bei expliziter Neukomposition.
- Physischer Überlauf muss im Render-/Exportprozess geprüft werden.
  Ein überfüllter Frame ist als Negativnachweis erhalten.
- Crop/Contain/Cover nicht pauschal als unterstützt erklären; konkrete Teilfälle
  benötigen ihre eigenen begrenzten Nachweise.
- Keine universelle freie ODF-Pfad-, SVG-, Connector- oder Chart-API.
- Abgerundete Auto-Grow-Tags sind inzwischen ausdrücklich benötigt. Der
  [H08-R-Nachweis](H08-ROUNDED-TAGS-NATIVE-PROOF.md) ist technisch positiv;
  visuelle Abnahme am 24.11.2026 erhalten; Integration nach CC09/P2-I03R bleibt offen.
- Interaktive Writer-Bearbeitung und H10-Human-Gate sind nicht automatisiert abgenommen.
- Herkunft/Nutzungsrechte ursprünglicher gekaufter CV-Referenzen und eine
  unabhängige externe Sicherung bleiben vor öffentlicher Weitergabe zu prüfen.

## Verbindlicher nächster Arbeitsmodus

[Change Contracts](P2-CHANGE-CONTRACTS.md) beschreiben Invarianten und Grenzen;
[Implementierungsplan](P2-IMPLEMENTATION-PLAN.md) bündelt sie in kleine Pakete.
Die fachlichen Abnahmen sind bestehende Vorgaben. Öffentliche API-Entscheidungen,
konkrete Implementierungsfreigaben und spätere Merges bleiben separate Schritte.
H10 folgt nach Integration der tatsächlich benötigten Fähigkeiten.


P2-I03/I03R ist am 24.11.2026 im P2-Worktree implementiert: [Bericht, Evidenz und offene Gates](P2-I03-I03R-IMPLEMENTATION-REPORT.md). Grafikkontext und abgerundete Tags verwenden bestehende Shapes sowie eine neutrale DrawingGroup; Kontaktsemantik bleibt beim Consumer. Nächster Implementierungsschritt ist P2-I04. Kein neuer Commit/Push.


Aktualisierung 25.11.2026: P2-I04 ist im P2-Worktree implementiert. [SVG-/Grafikressourcenbericht](P2-I04-IMPLEMENTATION-REPORT.md) mit freigegebener Bitmap-Austausch-API, isolierten Ressourcen, PHP-8.2-/8.5- und Writer-Nachweisen. Die neue visuelle Owner-Abnahme/interaktive Bearbeitung bleibt getrennt. Nächster Funktionsschritt: P2-I05. Kein Commit/Push.

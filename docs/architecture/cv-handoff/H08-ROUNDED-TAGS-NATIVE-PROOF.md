# H08-R — Abgerundete, mitwachsende native Tags

Stand 24.11.2026. Vom Owner ausdrücklich als benötigter Umfang bestätigt.
Status: **native Machbarkeit technisch nachgewiesen und vom Owner visuell abgenommen**.
Keine neue Produktionsklasse/API, kein Commit/Push/Merge/Deployment.
P2-I01b und P2-I02 bleiben unverändert im Worktree erhalten.

## Visuelle Owner-Abnahme

24.11.2026: Der Owner hat den vorgelegten PDF-/ODT-Nachweis ausdrücklich
mit „Das sieht sehr gut aus. Perfekt“ abgenommen. Die Abnahme umfasst den
gezeigten nativen Rundungs-, Text-, Farb- und Seitenfluss-Nachweis. Sie ist
keine bereits abgeschlossene Engine-Integration, GUI-Bearbeitungsprüfung
oder Commit-/Push-/Merge-Freigabe. Weiter mit P2-I03/I03R gemäß CC04/CC09.

## Ergebnis

Das bestehende Round-Rectangle-Preset der Engine funktioniert mit nativem Text,
Auto-Grow, Padding, Außenabständen und as-char-Textfluss. ShapeElement,
ShapeGeometry, Paragraph und StyleRequirement werden wiederverwendet.
Der frühere handgebaute Custom-Shape-Pfad wurde nicht erneut untersucht.

Die funktionierende Kombination übernimmt das komplette Preset einschließlich
Enhanced Path, Modifiers, Equations, Text-Areas und Stretchpoints. Daraus wird
keine isolierte Aussage über eine einzelne Ursache des früheren Fehlers abgeleitet.

| Pfad | Tatsächliches Writer-Ergebnis | Entscheidung |
| --- | --- | --- |
| Writer-Textrahmen plus draw:corner-radius am text-box | Radius ignoriert und beim Speichern entfernt; gleiche Pixel wie rechteckiger Kontrollfall | Nicht als Rundungsfähigkeit anbieten |
| Vorhandenes natives draw:rect plus Auto-Grow | Radius bleibt, getestete Breiten bleiben circa 1cm; lange Texte laufen über | Für mitwachsende Tags verworfen |
| Vorhandenes round-rectangle-Preset plus geeignete Text-/Grafikprojektion | Proportionale Textbreiten, echte Rundung, stabiler Umbruch und Roundtrip | Positiver nativer Kandidat |

## Konkrete Nachweise

- Showcase: 18 Tags in drei Varianten, Sans Serif/Serif, 9/10pt, unterschiedliche
  Füll-/Textfarben einschließlich weißer Schrift auf dunklem Hintergrund.
- Zusätzlicher nativer Seitenfluss: 90 eindeutige Tags über drei Seiten, alle
  vollständig vorhanden und ohne überlappende oder außerhalb der Seite liegende Wörter.
- Vier positive Lifecycle-Dokumente mit insgesamt sechs bildidentischen
  Writer-Roundtrip-Seiten; wiederholtes Engine-Speichern XML-stabil, Reopen erhalten.
- Font-/Farbwerte nach Writer-Ausgabe gezielt an den Shape-Textspannen geprüft.
- Der erste Stilversuch verwendete Common-Styles und zeigte falsche Standardschrift
  in Zeichnungstext. Dieser Versuch ist aufbewahrt. Die korrigierte Projektion nach
  content automatic styles ist eine konkrete Integrationsstelle für CC04.
- Technische Sichtprüfung des Showcase. Die Owner-Abnahme ist oben dokumentiert; eine interaktive
  Writer-Bearbeitungsprüfung wird nicht als durchgeführt behauptet.

## Konstruktionsrezept

- ShapeGeometry::preset('round-rectangle') unverändert verwenden, einschließlich
  draw:path-stretchpoint-x/y=10800 und aller vorgegebenen Equations/Text-Areas.
- Native draw:custom-shape mit as-char-Anker; Text direkt als text:p/text:span
  innerhalb der Shape. Kein Bild, kein verstecktes zweites Label, keine Textüberlagerung.
- Graphic properties: draw:auto-grow-width/height=true, solid fill, kein Stroke,
  vier Padding-Werte 0.152cm, horizontale Margins 0.08cm und vertikale 0.06cm.
- Initial 1cm × 0.2cm; tatsächliche Größe bestimmt Writer anhand des Textes.
- Drawing-Text-Stile im richtigen Scope projizieren; draw:text-style-name und
  Paragraph-/Textanforderungen konsistent bereitstellen.
- Maße sind Referenzwerte, keine neuen Produktdefaults. Radius folgt zunächst
  dem vorhandenen Preset-Modifier 3600; keine neue beliebige Radius-API zugesichert.

## Integration nach visueller Abnahme

H08-R wird als benötigter Ergänzungsslice P2-I03R nach bzw. mit P2-I03 geführt.
Wiederverwendung vorhandener Klassen ist verbindlich. Die Research-Adapter werden
nicht als drei neue Engine-Klassen übernommen.

Zu integrieren sind begrenzte as-char-Shape-Komposition, Drawing-Text-Style-Scope
und explizites Shape-Text-Auto-Grow. Der öffentliche addInlineElement-Pfad aus I02
akzeptiert bislang ausschließlich Frames; diese Erweiterung braucht ihren eigenen
validierten Carrier-Vertrag. Legacy-Shape-/Skillbar-Ausgabe bleibt erhalten.

Tests müssen kurze/lange Labels, Stile, 0/1/n, Wrap/Seitenwechsel, Wiederholung,
Reopen und Writer/PDF einschließen. Kein allgemeines Shrink/Fit, keine Freigabe
beliebig breiter Labels und keine native User-Field-Unterstützung in Shape-Text.
Für Kontakte gilt weiterhin der Writer-Frame-Vertrag.

## Artefakte

`research/p2-capability-audit/rounded-tags/` enthält Skripte, ODTs, Writer-Dateien,
PDFs, PNGs, XML-Nachweise, negative Kontrollen und verification.json.
PHP 8.2 und interaktive Writer-Bearbeitung bleiben offene Integrationsgates.


## Engine integration

P2-I03R now reproduces the accepted proof using production APIs without research serializers. See [implementation report](P2-I03-I03R-IMPLEMENTATION-REPORT.md). Original accepted artifacts remain unchanged; interactive editing is a separate gate.

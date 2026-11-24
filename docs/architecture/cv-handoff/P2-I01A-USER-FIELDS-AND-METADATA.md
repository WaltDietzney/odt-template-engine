# P2-I01a — User Fields und Dokumentmetadaten

Stand: 24.11.2026. Source-Baseline f8679a782a23b82ca2b9a0536d7affff6554301c.
Status: bestehende Fähigkeiten geprüft; begrenzter nativer Metadatenvergleich bestanden.
Noch keine neue öffentliche API implementiert oder freigegeben. Kein Commit/Push.

## Spätere Umsetzung

Der Owner hat anschließend den API-Vorschlag ausdrücklich zur Umsetzung beauftragt.
Die beiden Methoden sind nun im Worktree implementiert; siehe
[Implementierungsbericht](P2-I01A-IMPLEMENTATION-REPORT.md).
Die nachfolgenden Bestandsangaben und API-Vorschläge bleiben als Voruntersuchung erhalten.

## Tatsächlicher Bestand

| Aufgabe | Vorhandener Pfad | Grenze |
| --- | --- | --- |
| Native Writer String-User-Fields erkennen | UserFieldAnalyzer / inspectTemplate | vorhandene Deklarationen und Referenzen |
| Vorhandene Werte setzen | OdtTemplate::setUserField / UserFieldBinder | schreibt authoritative Deklarationen, erzeugt kein neues Feld; Cache bleibt bis Writer unverändert |
| Neue Referenzen technisch erzeugen | bestehender OdtElement-/Paragraph-Erweiterungspfad, Research UserFieldParagraph | DOM-Adapter statt fertiger öffentlicher Komfortmethode |
| Neue Deklarationen technisch erzeugen | H07 seed.py | Research-Paketautor, keine dokumentbezogene Engine-Registrierung |
| Standard- und eigene Dokumenteigenschaften | setMeta/getMeta, MetadataManager | meta.xml; custom unterstützt Stringwerte, auch YAML als String |
| Dokumenteigenschaft sichtbar anzeigen | ODF text:user-defined | Writer-Nachweis funktioniert; kein spezialisierter öffentlicher Konstruktor/Binding-Vertrag in der Engine |

Die Engine kann native Felder bereits verarbeiten. Die Lücke ist der vollständige,
öffentliche Konstruktionspfad ohne eigene DOM-/Paketbearbeitung, nicht eine neue
User-Field-Implementierung. Bestehende Binding-Semantik wird weiterverwendet.

## Drei unterschiedliche ODF-Strukturen

1. Writer User Field: text:user-field-decl (office:string-value) und
   text:user-field-get, gemäß ODF 1.2 Part 1, Abschnitte 7.4.8/7.4.9.
2. Benutzerdefinierte Dokumenteigenschaft: meta:user-defined in meta.xml,
   Abschnitt 4.3.3. Sichtbare Anzeige durch text:user-defined, Abschnitt 7.5.6;
   text:name und meta:name verknüpfen die beiden Elemente.
3. Strukturierte Profildaten als YAML/JSON-String in einer Dokumenteigenschaft:
   bereits speicherbar, aber Unterfelder werden nicht automatisch zu sichtbaren
   Feldern. Das Datenmodell/Mapping gehört zum Consumer.

Grundlage sind die im Projekt bereitgestellten OASIS ODF-1.2-Part-1-Unterlagen,
nicht eine Gleichsetzung ähnlich benannter XML-Elemente. Dokumenteigenschafts-
Felder sind nativ, aber ein anderer Feldtyp als die für H07 vereinbarten User Fields.
Ihre Nutzung als Ersatz würde eine bewusste fachliche Entscheidung erfordern.

## Neuer begrenzter ODT-Nachweis

research/p2-capability-audit/user-fields enthält einen synthetischen Vergleich
mit absichtlich identischem Namen contact.email und verschiedenen Werten.
Fünf Fälle: Ausgangszustand, nur Metadaten geändert, nur User Field geändert,
beide geändert sowie Dokumenteigenschaft ohne User-Field-Deklaration.

Ergebnis: fünf Writer-Roundtrips und PDF-Exporte erhalten jeweils den richtigen
Wert und nativen Feldtyp; fünf Roundtrip-PNG-Seiten sind byteidentisch. Beide
Speicher bleiben unabhängig. Ohne User-Field-Deklaration wirft setUserField
NOT_FOUND, obwohl eine gleichnamige Dokumenteigenschaft existiert. Drei Fälle
haben XML-stabile wiederholte Engine-Saves. Writer aktualisiert die Anzeigecaches.
Das ist ein technischer, kein neuer manueller GUI-/Owner-Abnahmenachweis.

Bestehende Tests erneut bestanden: 7 Binding-Tests/164 Assertions und
9 Package-/Metadaten-Lifecycle-Tests/52 Assertions. PHP-Lint der neuen Probe bestanden.
Kein vollständiger Implementierungs-Preflight, da src und öffentliche APIs unverändert sind.
Der erste Probe-Generator verlor Namespace-Bindungen aus Attributwerten;
dieser ungültige Versuch ist unter failed-initial dokumentiert. Korrigierter
Generator erhält die Bindungen; dafür war keine Engine-Änderung erforderlich.

## Konkreter kleiner API-Vorschlag zur Diskussion

```php
$template->declareUserField('contact.email', 'ada@example.invalid');
$paragraph = (new Paragraph())->addUserField('contact.email');
$template->appendBody($paragraph);
$template->setUserField('contact.email', 'neu@example.invalid');
```

Die Namen sind ein Vorschlag, keine beschlossene öffentliche API.

- Dokumentmethode registriert eine native String-Deklaration. Wiederholung mit
  identischem Wert darf idempotent sein; abweichender Wert/Typ oder widersprüchliche
  vorhandene Deklarationen werden vor Mutation zurückgewiesen. Für Wertänderungen
  bleibt setUserField zuständig. Kein implizites Überschreiben.
- Paragraph-Methode fügt eine echte native Referenz als geordneten Inline-Textteil
  hinzu. Kein normaler Text als Ersatz und zunächst kein eigener Anzeigecache.
  addText/addUserField/addText muss die Aufrufreihenfolge bewahren; die bisherige
  Sonderreihenfolge eingebetteter Bilder wird dabei nicht ungefragt geändert.
- Felddefinition dokumentbezogen, Referenz absatzbezogen. Keine Contact-Klassen,
  zweite Binding-Engine oder globale Registry. Typen bleiben auf String begrenzt.
- Feldformatierung nutzt bestehende Text-/Absatzstile. Vor Implementierung ist die
  genaue Style-Signatur am Paragraph-Pfad festzulegen, statt eine zweite Style-API
  einzuführen. Body/Header/Footer und Wiederholungs-/Kollisionsvertrag bleiben CC01.
- Metadaten werden nicht stillschweigend gespiegelt. Soll CV Generator Pro später
  Profildaten und sichtbare Felder gemeinsam befüllen, führt dessen Mapping beide
  explizit aus einer gemeinsamen Datenquelle zusammen.

Empfehlung: H07 auf den vereinbarten User Fields belassen und diese kleine
Konstruktionslücke schließen. Sichtbare Dokumenteigenschaften als belegte zusätzliche
Option dokumentieren; keine parallele Metadaten-Synchronisierung in P2 einführen.

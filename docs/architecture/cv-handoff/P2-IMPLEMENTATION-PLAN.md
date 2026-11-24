# P2 — Implementierungsplan nach visueller Abnahme

Stand: 24.11.2026. Grundlage: [Konsolidierung](P2-CONSOLIDATION-2026-11-24.md)
und [Change Contracts](P2-CHANGE-CONTRACTS.md). H05–H09 sind als native
Machbarkeit/Gestaltung abgenommen. Diese Pakete integrieren die belegten Lücken;
sie eröffnen keine erneute Grundlagenuntersuchung. Öffentliche API-Namen sind
noch zu prüfen. Kein Commit, Push, Merge oder Deployment ist hiermit freigegeben.

| Paket | Ziel und Wiederverwendung | Abhängigkeit | Fertig wenn |
| --- | --- | --- | --- |
| P2-I01 Native Textsemantik | CC01/02: vorhandene Field-Analyzer/Binding- und Paragraph-Pfade um begrenzte Konstruktion nativer String-Felder und expliziter Überschriften ergänzen | Baseline H00–H04 | Deklaration/Referenz, Kollisionen, text:h und PDF-Outline, Wiederholung/Reopen geprüft; normale Paragraphen unverändert |
| P2-I02 Textfluss und Rahmen | CC03: geordneter Inline-Inhalt und begrenzte Writer-Frame-Mindesthöhe; Paragraph/RichText/DrawTextBox wiederverwenden | I01 für Feldfixtures | Legacy-Reihenfolge charakterisiert; neuer Pfad opt-in; lange Kontakte, Tags und Folgetext ohne Überdeckung |
| P2-I03 Grafikkontext | CC04: vorhandene DrawingLayout-/Style-Projektion für Carrier, lokale Koordinaten und Zeichenreihenfolge vervollständigen | I02 für Kompositionsfixtures | Standalone und Zeichnungskontext, content.xml/styles.xml, Lifecycle und Writer nachgewiesen |
| P2-I04 Grafikressourcen | CC05: erst konkreten SVG-Preflight-Widerspruch beheben, dann begrenzte Ressourcen-/Bitmap-Austauschoperationen | I03 soweit Kontext betroffen | PHP 8.2/8.5, Seitenverhältnis, Manifest, Ressourcenisolation und Roundtrip geprüft; Crop bleibt eigener Bedarf |
| P2-I05 Teilring | CC06: vorhandene Geometrien nutzen, begrenzte Teilring-Erweiterung nur im erforderlichen Umfang | I03 | 0/1/70/99/100 sowie ungültige Werte, Konturen, Wiederholung und Writer geprüft |
| P2-I06 Consumer-Komposition | CC07: Kontaktvarianten, Timeline, Tags und dekorierte semantische Überschriften in CV Generator Pro zusammensetzen | jeweils benötigte I01–I05 | 0/1/n, kurze/lange Werte, fehlende Zeilen und Seitenwechsel; explizite Breiten und fachliche Zuständigkeit dokumentiert |
| P2-I07 H10 | CC08: ausgewählte S01b-/LOCAL-039-Kombination als nativer Integrationsnachweis | I06 und erforderliche Primitiven | Gesamt-Preflight, Writer-Bearbeitung und separate visuelle Owner-Abnahme bestanden |

I01 enthält zwei getrennte, reviewbare Schritte: String-Feld-Konstruktion und
Heading-Konstruktion. I02 trennt Reihenfolge von Sizing. I04 trennt den
konkreten SVG-Fehler von weitergehenden Ressourcenoperationen. Kein Paket
rechtfertigt eine pauschale Neufassung der beteiligten Klassen.

## Erster konkreter Arbeitsauftrag: P2-I01a

Arbeite ausschließlich in /srv/projects/odt-template-engine-p2 auf
architecture/p2-capability-audit, Ausgangsstand
f8679a782a23b82ca2b9a0536d7affff6554301c. Tatsächlichen Stand zuerst prüfen;
unversionierte Forschung erhalten. Originalworktree und Sample-Outputs nicht ändern.

Lies CC01, H07-CLOSEOUT-2026-11-24.md und die vorhandene
v04/composition/UserFieldParagraph-/Lifecycle-Evidenz. Untersuche danach gezielt
OdtTemplate::setUserField, Native User Field Analyzer/Binding, appendBody-
Validierung und bestehende strukturierte Textserialisierung. Characterization
Tests müssen vorhandene Bindung und das bisherige Cache-Verhalten absichern.

Entwerfe den kleinsten dokumentbezogenen Konstruktionspfad für native
String-Field-Deklaration und Referenz. Keine Kontaktrollen, Symbolfamilien,
Template-Conditions oder numerische Felder integrieren. Vor neuen öffentlichen
Signaturen den konkreten API-Diff mit CC01 abgleichen und zur Entscheidung
vorlegen; die Machbarkeitsabnahme legt keine Methodennamen fest.

Nach festgelegter API in kleinem Schritt implementieren. Prüfen: leere/lange/
Unicode-/XML-Zeichen, mehrere Referenzen, Dokumentkollisionen ohne Teilmutation,
content.xml/styles.xml im vereinbarten Umfang, wiederholtes render/save und
Reopen. Bestehende Bindung bleibt kompatibel. Danach fokussierte Tests,
Integration/PublicSampleSmokeTest, vollständiges composer test, PHP-Lint,
git diff --check und relevante Dokumentation; Writer/PDF anhand der bestehenden
Kontaktfixture. Ausfälle ausdrücklich offenhalten. Nicht committen oder pushen.

## Übergaberegel

Jeder Schritt nennt Contract, tatsächliche Source-Baseline, betroffene öffentliche
und geschützte Erweiterungspunkte, Tests und verbleibende Grenzen. Forschungsklassen
werden nicht ungeprüft in src kopiert. ContactItem/ContactBlock/ContactStrip und
Bild-zu-Design-Zuordnung bleiben Consumer-Verantwortung. Engine-Integration und
visuelle Freigabe sind getrennte Nachweise. Zielbranch bleibt develop.

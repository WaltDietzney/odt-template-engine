# P2 — Implementierungsplan nach visueller Abnahme

## Aktueller Abschluss — 25.11.2026

P2-I01–I07 einschließlich H10 sind im vereinbarten Umfang umgesetzt und abgenommen. Beide Review-v2-Templates sind visuell akzeptiert; der Owner bestätigt die interaktive Writer-Prüfung. Versionierung/Veröffentlichung bleiben separat offen. Verbindlicher Status: [P2-Implementierungsabschluss](P2-CLOSEOUT-2026-11-25.md) und [maschinenlesbarer Stand](P2-CLOSEOUT-STATE.json). Offene Folgepunkte werden dort ausdrücklich aufgeführt.

### Historische Planung und Fortschreibung

Die nachfolgenden älteren Status- und Arbeitsaufträge sind historische Quellen; aktuelle Arbeit wird vom oben verlinkten Abschluss aus gewählt.


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
| P2-I03R Abgerundete Tags | CC09: bestehendes Round-Rectangle-Preset mit Auto-Grow und Inline-Textfluss integrieren | I03 und visuelle Abnahme des H08-R-Nachweises | Native Rundung, Fonts/Farben, kurze/lange Labels und Seitenfluss stabil; keine neue Geometrie |
| P2-I04 Grafikressourcen | CC05: erst konkreten SVG-Preflight-Widerspruch beheben, dann begrenzte Ressourcen-/Bitmap-Austauschoperationen | I03 soweit Kontext betroffen | PHP 8.2/8.5, Seitenverhältnis, Manifest, Ressourcenisolation und Roundtrip geprüft; Crop bleibt eigener Bedarf |
| P2-I05 Teilring | CC06: vorhandene Geometrien nutzen, begrenzte Teilring-Erweiterung nur im erforderlichen Umfang | I03 | 0/1/70/99/100 sowie ungültige Werte, Konturen, Wiederholung und Writer geprüft |
| P2-I06 Consumer-Komposition | CC07: Kontaktvarianten, Timeline, Tags und dekorierte semantische Überschriften in CV Generator Pro zusammensetzen | jeweils benötigte I01–I05 | 0/1/n, kurze/lange Werte, fehlende Zeilen und Seitenwechsel; explizite Breiten und fachliche Zuständigkeit dokumentiert |
| P2-I07 H10 | CC08: ausgewählte S01b-/LOCAL-039-Kombination als nativer Integrationsnachweis | I06 und erforderliche Primitiven | Gesamt-Preflight, Writer-Bearbeitung und separate visuelle Owner-Abnahme bestanden |

I01 enthält zwei getrennte, reviewbare Schritte: String-Feld-Konstruktion und
Heading-Konstruktion. I02 trennt Reihenfolge von Sizing. I04 trennt den
konkreten SVG-Fehler von weitergehenden Ressourcenoperationen. Kein Paket
rechtfertigt eine pauschale Neufassung der beteiligten Klassen.

## Fortschritt

P2-I01a ist nach explizitem Owner-Auftrag im Worktree implementiert;
[Bericht und verbleibende Gates](P2-I01A-IMPLEMENTATION-REPORT.md).
Der folgende ursprüngliche Auftrag bleibt als Scope-Referenz erhalten.
P2-I01b ist nun ebenfalls im Worktree implementiert;
[Heading-Bericht](P2-I01B-IMPLEMENTATION-REPORT.md).
P2-I02 ist ebenfalls im Worktree umgesetzt; [Bericht und Maximum-Grenze](P2-I02-IMPLEMENTATION-REPORT.md).
P2-I03 und P2-I03R sind im Worktree umgesetzt; [Grafikkontext-/Rounded-Tag-Bericht](P2-I03-I03R-IMPLEMENTATION-REPORT.md).
P2-I04 ist im Worktree umgesetzt; [SVG-/Ressourcenbericht einschließlich PHP 8.2](P2-I04-IMPLEMENTATION-REPORT.md).
P2-I05 ist im Worktree umgesetzt; [Teilring-Bericht und visuelle Gates](P2-I05-IMPLEMENTATION-REPORT.md).
P2-I06/CC07 ist im isolierten CV-Consumer-Worktree umgesetzt; [Bericht und offene Abnahme](P2-I06-IMPLEMENTATION-REPORT.md). Nächster Integrationsschritt nach Abnahme: P2-I07/H10. Interaktive Writer-Gates bleiben getrennt.

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


25.11.2026: P2-I04 wurde vom Owner visuell abgenommen (“Sehr schön, abgenommen”). Interaktive Writer-Bearbeitung bleibt separat. Nächster Funktionsschritt: P2-I05/CC06. Keine Git-Veröffentlichungsfreigabe.


Owner-Abnahme 25.11.2026: P2-I05 visuell ACCEPTED (“Perfekt, abgenommen”). Interaktive Writer-Bearbeitung bleibt separat offen. Nächster geplanter Schritt: P2-I06/CC07 Consumer-Komposition. Keine Commit-/Push-/Merge-Freigabe.


25.11.2026: P2-I06 im Consumer implementiert und automatisch geprüft (16 native Kandidaten, 21 Roundtrip-Seiten); visuelle Owner-Abnahme OPEN. Siehe [P2-I06-Bericht](P2-I06-IMPLEMENTATION-REPORT.md). Keine Engine-Quellcodeänderung, keine Veröffentlichungsfreigabe.


## Owner visual acceptance — 2026-11-25

Owner: “Sehr schön, abgenommen, wie geht es weiter?” P2-I06 including corrected inline strip, station spacing, A/B/C authored headings and the RichText timeline extension is visually ACCEPTED. This supersedes earlier OPEN visual-owner status in this document. Evidence remains 20 native candidates / 26 Writer-roundtrip pages and focused tests on PHP 8.2/8.5. Interactive Writer editing is a separate gate and remains OPEN. The characterized nested-Section/native-field identity issue is not resolved by visual acceptance. No commit/push/merge/deployment is authorized. Next: P2-I07/H10 under CC08, checking whether the known section/field issue affects the chosen integration before claiming a completed overall preflight.


## P2-I07/H10 — 25.11.2026

S01b-nahe native Gesamtkomposition im Consumer umgesetzt: [Integrationsbericht](P2-I07-H10-INTEGRATION-REPORT.md). Fünf Dokumente/neun Seiten; technische Verifikation abgeschlossen, begrenzte Symbol-Rasterabweichungen dokumentiert. Visuelle Owner-Abnahme und interaktive Writer-Bearbeitung OPEN. Keine Engine-Quellcodeänderung, keine Git-Veröffentlichungsfreigabe.


## Owner visual approval — 2026-11-25

Owner explicitly confirmed: “Ja absolut, visuelle Zustimmung erteilt. Und das ist schon ein Premium Template.” The presented S01b-like H10 composition is visually APPROVED WITH OPEN MINOR CORRECTION: alignment/spacing of “Englisch · B2” beside the sidebar partial ring. This supersedes earlier OPEN owner-visual status for the presented composition, but does not assert that the correction has already been implemented or accepted. Interactive Writer editing remains OPEN. H10 overall closeout remains pending these remaining checks. No commit, push, merge or deployment is authorized by this visual approval.


## Final owner visual acceptance of both variants — 2026-11-25

Owner: “Okay, beide Exemplare sind abgenommen.” Both review-v2 band and fold variants are visually ACCEPTED, including the English/B2 caption correction. This supersedes earlier OPEN variant-review and minor-caption-correction status. Interactive Writer editing remains a separate OPEN gate; owner visual approval is not represented as that edit test. Premium positioning remains planned for a later version. The existing nested-Section/User-Field failure remains characterized and unresolved. Next: interactive edit evidence, consolidate the P2 completion/remaining-issues records, then prepare bounded commits and publication only after explicit Git authorization. No commit/push/merge/deployment is authorized by visual acceptance.

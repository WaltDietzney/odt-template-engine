# CV Generator Pro – Eingang der Engine-Übergabe
17.11.2026. Projektwechsel autorisiert; keine Implementierungs-, Merge- oder Deploymentfreigabe.
CV-Quelle: Forgejo Walt/cv-generator-pro, planning/visual-template-designer, cad1e1c8c42329ef52c92cb67f60c06e549ba3fb. Engine-Ausgangsstand: b304419efc407ab7f6277712f17c1a34d9a4c95c (feature/page-style-authoring-01). Separater Planungsworktree; Integrationsziel vor Codearbeit klären.
## Quellen und Nachweise
Im CV-Repository am genannten Commit: research/engine-handoff/UEBERGABE-AUFTRAEGE.md (H01–H10), ENGINE-ANFORDERUNGSLISTE.md, DOE-DESIGNLABOR.md, E011-BEFUND.md, E007-MERIDIAN-ANFORDERUNG.md; research/E009-ARCHITEKTUR-UND-ENGINE-HANDOFF-2026-11-17.md und E009-POC-2026-11-17.md.
Hier byteidentisch übernommen: [E009 ODF-Auszug](E009-ODF-EVIDENCE.json), [E011 XML](E011-ODF-EVIDENCE.xml). E009 nativer Writer-PoC mit visueller Abnahme, keine Engine-Implementierung; E011 begrenzter Roundtrip ohne visuelle Abnahme. Originale auf Patil im CV-Workspace unter research/visual-template-references/prototypes/E009/preview und E011; keine Binärdateien übernommen, Backup offen.
## Arbeitsstart
H01 Section-Authoring, H02 Seitenlayout/Frames, H03 Geometrie, H04 Tabellen-Spaltenidentität sind P1-Kandidaten. H05–H09 folgen abhängig von Capability-Abgleich, H10 Integration. Vor Auswahl eines Slices Engine-Integrationsstand (develop/master/Feature), vorhandene APIs und Tests prüfen; keine bereits vorhandenen Fähigkeiten erneut entwickeln. DOE: parametrisierbare Schnittstellen, reproduzierbare Fixtures und Rendernachweise; statistische Planung und visuelle Bewertung außerhalb der Engine. Kein neuer API-Vertrag aus PoC ableiten.

## H04 – Table-column identity
- [H04-A characterization evidence](H04-A-TABLE-COLUMN-IDENTITY-EVIDENCE-2026-11-24.md)
- [H04-B proposed change contract](H04-B-TABLE-COLUMN-IDENTITY-CHANGE-CONTRACT.md) (not approved)

# Forschungs- und Fähigkeitsindex
Stand: 17.11.2026

- [Frame Layout Closeout](FRAME_LAYOUT_01_CLOSEOUT.md): COMPLETE / FINAL GO; DrawingLayout als gemeinsame Autorität, setFrameLayout und Anchor/Alignment/Offset/Wrap für DrawTextBox und ImageElement. Kein Nachweis beliebiger Zeichenobjekte.
- [Page Style Authoring](PAGE_STYLE_AUTHORING_01.md): native Layout-/Master-Page-Erzeugung und Absatzzuordnung; Writer bleibt Pagination-Instanz.
- [S01b Regression](S01B_PAGE_FLOW_REGRESSION.md): DocumentStyles::updateParagraph und Keep-with-next-Ketten; verschachtelte Listen/Sections können unabhängige Flusseinheiten bilden. Verbindlicher CV-Referenzfall, keine universelle Vorlage.
- [Declarative Section Fixture](RESEARCH-01A_DECLARATIVE_SECTION_FIXTURE.md): empirischer Nachweis nativer Section-Namen, keine freigegebene Syntax oder API.
- [Mapping & Automation](../advanced/mapping-automation.md) und [API](../api-reference/mapping-automation/automation.md): explizite Zuordnung und Preflight; nicht mit visuellem Mapping oder imperativem Authoring gleichsetzen.
- [API-Referenz](../api-reference/index.md): vorhandene öffentliche Fähigkeiten vor Erweiterungen prüfen.
- [Konsolidierung](REPOSITORY_CONSOLIDATION_2026_11_17.md): lokale Nachweise und Teststand.

E009: nativer Writer-PoC visuell abgenommen; Engine-Implementierung und deren eigenständiger Nachweis offen. E011: begrenzter Writer-Roundtrip, visuelle Abnahme und Engine-Integration offen. Neue Erkenntnisse mit Baseline, Quelle, Versuch, Ergebnis, Grenzen und Status ergänzen; keine Freigaben aus bloßen PoCs ableiten.

- [CV-Engine-Übergabe](cv-handoff/README.md): versionierte Herkunft, E009/E011-ODF-Nachweise und DOE-Anschluss; Implementierung offen.

- [ODT Construction](ODT_CONSTRUCTION_01_PLAN.md): controlling post-1.0 plan; [H00 crosswalk](H00_CV_BASELINE_INTEGRATION.md) links CV requirements without replacing the roadmap.

- [Section Authoring reference](SECTION_AUTHORING_01_REFERENCE.md): inspected native Writer fixture, H01 implementation handoff; reuse completed Section and style architecture, no repeated general research.
- [Section Authoring SA-01 contract](SECTION_AUTHORING_01_CONTRACT.md): approved minimal working-document construction surface and validation/rollback contract.
- [Section Authoring SA-02 contract](SECTION_AUTHORING_02_CONTRACT.md): bounded document-local Section styles, columns, balancing, gaps, separators, validation, and lifecycle semantics.
- [H01 Section Authoring closeout](H01_SECTION_AUTHORING_CLOSEOUT.md): generated nested styled Sections, Writer-roundtrip integration test, 0/1/n station collections, sandbox boundary, and fixture human gate.

SA-02-R1 correction pass (baseline `84a3d99`, 2026-11-17): unequal Writer
columns now use explicit native column indents with exact adjacent-gap splits;
balance is materialized on `style:section-properties`; separator ordering and
positive bounded ratio normalization are covered by scoped integration tests.
LibreOffice headless conversion was attempted with isolated profile/output
directories but is unavailable in this restricted sandbox even for the
unchanged reference fixture (environment blocker, not a package regression).

# Changelog

All notable changes to ODT Template Engine will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project uses [Semantic Versioning](https://semver.org/) for public releases.

## [Unreleased]

## [1.0.0] - 2026-09-26

### Added

- Three complementary authoring models: simple template processing, structured native ODT construction, and bounded Writer-native document addressing.
- Native named Section and bookmark inspection/mutation, nested Section collections, Writer table population, named frame inspection, and string Writer User Field binding.
- Source-oriented template contract inspection plus optional application-data mapping, concrete preflight, and atomic automation.
- Document-local semantic style handling, named paragraph-style authoring, paragraph-flow controls, advanced table geometry, and shared frame/image layout semantics.
- Canonical L/C/B/S sample and learning path, including professional CV and report showcases.

### Changed

- Public API guidance now distinguishes Recommended, Advanced, Compatibility, Deprecated, and infrastructure-only surfaces.
- Documentation and samples now use structure ownership — Writer or PHP — as the primary architectural decision.
- Composer-installed canonical samples now bootstrap correctly both from a repository checkout and from a consumer project's `vendor/` directory.
- Release documentation defines PHP 8.2+ with DOM and ZIP as runtime requirements; LibreOffice is an authoring and visual-validation tool rather than a PHP runtime dependency.

### Compatibility

- Existing compatibility and deprecated entry points required by the 1.0 contract are retained and documented rather than removed during finalization.
- PHP 8.2, 8.3, and 8.4 are exercised by CI.
- Generated documents remain native editable ODT; DOCX export/interoperability is not part of the 1.0 contract.

### Validation

- Repository finalization completed with 995 PHPUnit tests and 7,656 assertions passing, including public sample smoke coverage.
- A clean external Composer consumer exercised the Simple, Structured, and Writer-native models.
- The professional multi-page S01b CV passed generation, LibreOffice open/save/reopen, visual regression, and PDF export checks during the 1.0 integration pre-flight.

## [0.9.0] - 2026-08-22

### Added

- Public-facing repository README with installation, quick-start, feature overview, project status and real-world usage.
- Responsive Sample Explorer showcase with search, feature categories, source inspection and ODT generation.
- Real-world project and project-support sections in the Sample Explorer.
- Automated PHPUnit test suite with unit/regression and ODT package integration coverage.
- GitHub Actions CI matrix for PHP 8.2, 8.3 and 8.4.
- Security policy and contribution guide.
- Structured GitHub issue forms for bug reports and feature requests.
- Composer discovery keywords for ODT, OpenDocument, LibreOffice and document generation.

### Changed

- Composer package description now reflects editable ODT generation and the engine's richer document features.
- Repository structure and Composer package metadata were cleaned up for clearer separation between core library, samples, tests, documentation and demo tooling.
- Sample Explorer runtime paths were adapted to its dedicated `demo/sample-explorer/` location.
- RichTable style handling was simplified to remove an obsolete duplicate style-injection path and related PHP warnings.

### Security

- Dependency and repository security hygiene was reviewed as part of the public-release preparation.
- Sample Explorer documentation explicitly distinguishes local/demo usage from production-ready deployment.

### Compatibility

- Requires PHP 8.2 or newer.
- CI validates PHP 8.2, 8.3 and 8.4.
- Requires the PHP DOM and ZIP extensions.
- Generated documents target the OpenDocument Text format and are primarily exercised with LibreOffice-oriented templates and workflows.

[Unreleased]: https://github.com/WaltDietzney/odt-template-engine/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/WaltDietzney/odt-template-engine/compare/v0.9.0...v1.0.0
[0.9.0]: https://github.com/WaltDietzney/odt-template-engine/releases/tag/v0.9.0

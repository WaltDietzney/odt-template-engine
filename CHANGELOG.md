# Changelog

All notable changes to ODT Template Engine will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project uses [Semantic Versioning](https://semver.org/) for public releases.

## [Unreleased]

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

[Unreleased]: https://github.com/WaltDietzney/odt-template-engine/compare/v0.9.0...HEAD
[0.9.0]: https://github.com/WaltDietzney/odt-template-engine/releases/tag/v0.9.0

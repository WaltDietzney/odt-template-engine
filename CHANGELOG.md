# Changelog

All notable changes to ODT Template Engine will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project intends to use [Semantic Versioning](https://semver.org/) for public releases.

## [Unreleased]

### Added

- Public-facing repository README with installation, quick-start, feature overview, project status and real-world usage.
- Responsive Sample Explorer showcase with search, feature categories, source inspection and ODT generation.
- Real-world project and project-support sections in the Sample Explorer.
- Automated PHPUnit test suite with unit/regression and ODT package integration coverage.
- GitHub Actions CI matrix for PHP 8.2, 8.3 and 8.4.
- Security policy and contribution guide.

### Changed

- Repository structure and Composer package metadata were cleaned up for clearer separation between core library, samples, tests, documentation and demo tooling.
- Sample Explorer runtime paths were adapted to its dedicated `demo/sample-explorer/` location.
- RichTable style handling was simplified to remove an obsolete duplicate style-injection path and related PHP warnings.

### Security

- Dependency and repository security hygiene was reviewed as part of the public-release preparation.

## Public release preparation

The next planned milestone is the first explicitly versioned public preview release. Until that version is tagged, changes remain under `Unreleased` rather than assigning a version number retroactively.

When the public preview is prepared, this section can be promoted to a release such as `0.9.0` with the release date and corresponding Git tag.

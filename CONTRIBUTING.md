# Contributing to ODT Template Engine

Thank you for considering a contribution to ODT Template Engine.

The project aims to keep ODT document generation practical, predictable and compatible with real OpenDocument workflows. Focused bug reports, tests, documentation improvements and pull requests are welcome.

## Before opening an issue

Please check whether the problem can be reproduced with the current `master` branch and whether a similar issue already exists.

For ODT rendering problems, a small reproducible example is especially valuable. ODT files are ZIP packages containing XML, and behavior can depend on how an office application generated the document structure.

## Bug reports

A useful bug report should include, where relevant:

- PHP version;
- ODT Template Engine version or commit;
- operating system;
- LibreOffice or other office-suite version;
- a minimal PHP example;
- the relevant template structure or a minimal ODT template;
- expected behavior;
- actual behavior and any PHP warning, exception or stack trace.

Please remove personal, confidential and production data from example documents before attaching them.

Security vulnerabilities should **not** be reported through a public issue. Follow [`SECURITY.md`](SECURITY.md) instead.

## Development setup

Clone the repository and install development dependencies:

```bash
git clone https://github.com/WaltDietzney/odt-template-engine.git
cd odt-template-engine
composer install
```

Run the test suite:

```bash
composer test
```

The project requires PHP 8.2 or newer with the DOM and ZIP extensions.

## Coding conventions

- Target PHP 8.2+.
- Follow PSR-12 style conventions.
- Use strict types in new PHP files where appropriate.
- Prefer clear English comments and PHPDoc for public APIs or non-obvious behavior.
- Keep changes focused. Avoid unrelated refactoring in bug-fix pull requests.
- Preserve ODT/ODF semantics rather than relying on output that only happens to render correctly in one test document.

## Tests

Changes that fix a bug or add behavior should normally include a regression test or integration test when practical.

Before opening a pull request, run:

```bash
composer test
```

GitHub Actions additionally validates Composer metadata, lints PHP files and runs the test suite on PHP 8.2, 8.3 and 8.4.

For document-generation changes, automated XML/package tests should be complemented by a manual LibreOffice smoke test when visual layout or office-suite behavior is involved.

## Working with ODT files

An `.odt` document is a ZIP package. The engine primarily works with XML files such as:

- `content.xml` — document body and automatic content styles;
- `styles.xml` — document styles and style definitions;
- `meta.xml` — document metadata;
- `META-INF/manifest.xml` — package manifest.

When debugging generated documents, inspect both the visible LibreOffice result and the relevant XML. A document that opens successfully is not automatically structurally correct, and structurally valid XML does not guarantee the intended visual result.

## Pull requests

A typical contribution workflow is:

```text
fork → feature/fix branch → implementation → tests → pull request
```

Please describe:

- what problem the change solves;
- the approach used;
- how the change was tested;
- whether it changes public API behavior or generated ODT structure.

Small, reviewable pull requests are preferred.

## Samples and documentation

If a new feature is visible to library users, consider adding or extending a sample under `samples/` and documenting the behavior in `docs/README.md`.

Samples should demonstrate practical usage and should remain runnable from a normal repository checkout after `composer install`.

## Scope

ODT Template Engine is intentionally focused on generating and manipulating editable OpenDocument Text files. New features should strengthen that purpose rather than turn the library into a general office-suite automation framework.

Thank you for helping improve the project.

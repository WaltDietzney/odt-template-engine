# F5.1 — Package Audit

**Status:** COMPLETE — static package/release-surface audit; runtime consumer proof remains F5.2

## Objective

Verify the release-facing Composer/package surface before the clean consumer installation. This slice does not introduce package restructuring or change engine behavior.

## Composer metadata

The current `composer.json` is structurally appropriate for a PHP library:

- package name: `waltdietzney/odt-template-engine`;
- type: `library`;
- license: MIT, matching the repository `LICENSE`;
- runtime PHP constraint: `^8.2`;
- required extensions: `ext-dom` and `ext-zip`;
- PSR-4 runtime namespace: `OdtTemplateEngine\\` → `src/`;
- PHPUnit is development-only;
- no hard LibreOffice runtime dependency is declared;
- support links point to the GitHub repository/issues.

No `version` field should be added for the 1.0 release. Composer/Packagist derives release versions from VCS tags; the eventual release tag is expected to establish `1.0.0`.

## Environment consistency

README, installation documentation, Composer metadata, and CI agree on PHP 8.2+ with DOM and ZIP. CI covers PHP 8.2, 8.3, and 8.4 and runs strict Composer metadata validation, PHP lint, and the normal test suite on pull requests.

LibreOffice is correctly described as an authoring/visual-validation tool, not a runtime package dependency.

## Archive policy

The `composer.json` `archive.exclude` configuration excludes development/demo surfaces including `demo/`, `docker/`, root `output/`, root `qrcode/`, and `tests/` from archives created through Composer's archive mechanism.

The public canonical `samples/` surface and documentation are not excluded. This is consistent with the sample registry's `distribution = composer` classification and with the project using samples as executable public documentation.

**F5.2 correction:** the real consumer installation used a GitHub-generated dist zipball, whose contents are not defined by Composer's `archive.exclude` setting. That installed dependency included `demo/`, `docker/`, and `tests/`. See `F5_2_CLEAN_CONSUMER_INSTALLATION.md` for the runtime evidence.

## Public installation path / Packagist

The public documentation intentionally teaches `composer require waltdietzney/odt-template-engine`.

**F5.2 correction:** the package was already registered on Packagist. The clean consumer test established that it is publicly discoverable and that the documented command installs normally; the current stable public version during that exercise was `v0.9.0`. Packagist also exposed `dev-develop` for the release-candidate consumer exercise, so no explicit VCS repository override was required.

The earlier F5.1 statement that public search had not established an existing package entry was incorrect and is superseded by the direct installation evidence in `F5_2_CLEAN_CONSUMER_INSTALLATION.md`.

The `1.0.0` tag is not required merely to register/test the package. The tag belongs to release creation after the candidate has passed the required gates.

## Repository/release references

`CONTRIBUTING.md` still told contributors to reproduce issues against `master`, while active development is based on `develop`. F5.1 corrects that bounded release-facing inconsistency.

`SECURITY.md` currently states that, until stable 1.0, security fixes apply to `master`. That describes the conservative public/release line and is not silently changed during F5.1. The supported-version wording must be reviewed when the stable 1.0 release is actually published.

## No package-version field

Do not add a `version` field such as `"version": "1.0.0"` to `composer.json`. The release version is represented by the Git tag. Keeping the version out of `composer.json` avoids duplicating VCS version authority.

## F5.1 result

**GO to F5.2.**

F5.2 subsequently established the public Packagist installation path, inspected the real installed distribution, proved the documented Recommended workflow, and corrected the two static assumptions noted above. See `F5_2_CLEAN_CONSUMER_INSTALLATION.md`.

No engine/API behavior change or package restructuring was justified by the static F5.1 audit.

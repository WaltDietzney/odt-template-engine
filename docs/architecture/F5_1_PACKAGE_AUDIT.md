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

The Composer archive excludes development/demo surfaces including `demo/`, `docker/`, root `output/`, root `qrcode/`, and `tests/`.

The public canonical `samples/` surface and documentation are not excluded. This is consistent with the sample registry's `distribution = composer` classification and with the project using samples as executable public documentation.

The exact generated Composer archive contents must still be exercised during F5.2 on the clean consumer machine. In particular, F5.2 must verify that all resources needed by the documented consumer path are present and that no workflow depends on repository-only files.

## Public installation path / Packagist prerequisite

The public documentation intentionally teaches `composer require waltdietzney/odt-template-engine`. That is the correct final 1.0 installation path, but it requires the package to be registered and available through Packagist. During this audit, public Packagist search did not establish an existing package entry for this package name.

This is a **distribution prerequisite**, not an engine defect. Before the final public 1.0 installation test, either:

1. register/connect the GitHub repository on Packagist and use the appropriate development/pre-release constraint for F5.2; or
2. perform the first F5.2 clean-room exercise through an explicit Composer VCS repository configuration, then repeat the final installation path after Packagist publication.

The `1.0.0` tag is not required merely to register/test the package. The tag belongs to release creation after the candidate has passed the required gates.

## Repository/release references

`CONTRIBUTING.md` still told contributors to reproduce issues against `master`, while active development is based on `develop`. F5.1 corrects that bounded release-facing inconsistency.

`SECURITY.md` currently states that, until stable 1.0, security fixes apply to `master`. That describes the conservative public/release line and is not silently changed during F5.1. The supported-version wording must be reviewed when the stable 1.0 release is actually published.

## No package-version field

Do not add a `version` field such as `"version": "1.0.0"` to `composer.json`. The release version is represented by the Git tag. Keeping the version out of `composer.json` avoids duplicating VCS version authority.

## F5.1 result

**GO to F5.2**, with two explicit external/runtime checks:

- establish the pre-release Composer source (Packagist registration or bounded VCS configuration);
- on the clean machine, inspect the actual installed package and prove the documented Recommended workflow without relying on development-checkout knowledge.

No engine/API behavior change or package restructuring is justified by the static F5.1 audit.

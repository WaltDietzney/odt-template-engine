# F5.3 — Supported Environment Check

**Status:** COMPLETE — package constraints, CI coverage, public documentation, and clean-consumer evidence reconciled

## Objective

Reconcile the environment promised by the release-facing package surface with the environments actually exercised by CI and by the F5.2 clean consumer installation.

This slice defines no new runtime architecture and does not broaden the public API. It records the supported 1.0 environment from existing Composer metadata, CI, documentation, and external consumer evidence.

## Supported runtime contract

The release-facing PHP runtime contract is:

- PHP `^8.2`;
- DOM extension (`ext-dom`);
- ZIP extension (`ext-zip`);
- Composer as the documented package installation path.

These requirements are declared in `composer.json` and are stated consistently in the README and installation documentation.

The PHP constraint and the currently verified PHP-version set are related but not identical:

- `^8.2` is the Composer package constraint;
- PHP 8.2, 8.3, and 8.4 are the versions currently exercised by project CI.

The documentation must not turn the current CI matrix into an artificial upper runtime bound. Conversely, a future PHP version satisfying the Composer constraint is not automatically equivalent to having dedicated CI evidence for that version.

## CI evidence

The project CI matrix currently runs on PHP:

- 8.2;
- 8.3;
- 8.4.

For each matrix version, CI provisions DOM and ZIP and performs:

- strict Composer metadata validation;
- dependency installation;
- PHP lint for `src/` and `tests/`;
- the normal `composer test` suite.

This is consistent with the runtime requirements declared by the package.

## Clean-consumer evidence

F5.2 supplied an independent consumer proof on Linux Mint 22 using PHP 8.3 with the required DOM/XML and ZIP support.

That exercise established that a fresh external project can:

- install the public package through Packagist;
- install `dev-develop` for release-candidate verification;
- use the documented Recommended render/save workflow;
- produce editable ODT output;
- execute representative canonical samples for all three 1.0 product models from the installed Composer dependency location.

F5.3 treats that evidence as runtime confirmation of the documented PHP/extension contract, not as a claim that Linux Mint is the only supported operating system.

## Composer and Packagist

Composer is the documented installation mechanism.

F5.2 established that `waltdietzney/odt-template-engine` is publicly registered and discoverable on Packagist and that the documented command works:

```bash
composer require waltdietzney/odt-template-engine
```

The engine does not require repository-checkout knowledge for normal consumer installation.

The distro-specific Composer diagnostic warning observed in F5.2 about a non-standard Composer `installed.json` was an environment packaging detail and did not affect installation or execution. It is not part of the engine's runtime contract.

## LibreOffice boundary

LibreOffice is **not** a PHP runtime dependency of the engine.

The public documentation correctly separates these concerns:

- LibreOffice is the normal visual authoring environment for Writer/ODT templates;
- LibreOffice is recommended for opening and visually validating generated documents;
- the PHP engine itself operates on native ODT packages without requiring LibreOffice to be installed as a runtime dependency.

F5.2 used LibreOffice 24.2.7.2 to open the generated Quick Start result and confirmed that it remained an editable Writer document. This is consumer-validation evidence, not a minimum LibreOffice-version declaration.

No specific LibreOffice version range is established by F5.3.

## Filesystem and output assumptions

Normal template processing requires filesystem access to:

- read the source ODT package;
- create temporary/package working data as required by the engine;
- write the destination supplied to `save()`.

The Quick Start deliberately tells the consumer to create its example `output/` directory before saving into it. That is part of the documented example setup and does not imply that `save()` owns arbitrary parent-directory creation.

Canonical public samples have their own sample-output handling. F5.2 demonstrated that the installed canonical sample workflow could produce its `samples/output/` result without consumer-side manual directory preparation. That sample behavior must not be generalized into a different contract for arbitrary application save paths.

The package does not declare a LibreOffice executable, web server, database, external service, or operating-system-specific runtime dependency.

## Documentation reconciliation

The current release-facing surfaces are consistent on the environment relevant to normal consumers:

| Surface | PHP | Required extensions | LibreOffice |
| --- | --- | --- | --- |
| `composer.json` | `^8.2` | DOM, ZIP | no runtime dependency |
| README | 8.2+ | DOM, ZIP | authoring/visual verification |
| Installation guide | 8.2+ | DOM, ZIP | not required for installation/runtime |
| CI | 8.2, 8.3, 8.4 | DOM, ZIP | not required |
| F5.2 clean consumer | 8.3 | DOM/XML, ZIP | used for visual validation |

No public documentation contradiction requiring an F5.3 code or documentation correction was found.

## Security-policy timing

`SECURITY.md` still states that, until stable 1.0, security fixes are applied to the current `master` branch.

That is explicitly pre-1.0 release wording and is not an environment inconsistency. It remains a release-time review item when stable 1.0 is published; F5.3 does not rewrite release-policy semantics from an environment audit.

## F5.3 result

**PASS / COMPLETE.**

The supported environment is coherent across Composer metadata, CI, public documentation, and the clean-consumer proof:

- runtime package constraint: PHP `^8.2`;
- currently CI-verified PHP versions: 8.2, 8.3, 8.4;
- required PHP extensions: DOM and ZIP;
- documented installation: Composer / Packagist;
- LibreOffice: authoring and visual-validation tool, not engine runtime dependency;
- filesystem: source/template readability and destination/working-path write access are application/runtime prerequisites.

No engine/API change, dependency change, package restructuring, or new architecture is justified by F5.3.

With F5.1, F5.2, and F5.3 complete, the F5 Distribution & Consumer Readiness block has satisfied its planned gates. FINALIZATION-01 may proceed to F6 closeout/preflight.

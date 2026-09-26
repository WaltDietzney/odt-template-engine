# F5.2 — Clean Consumer Installation

**Status:** COMPLETE — clean consumer installation and representative Composer-distribution workflows passed

## Objective

Prove that the project behaves as a consumable Composer package outside the development checkout, using the documented installation and Recommended API path. This is the runtime counterpart to the static F5.1 package audit.

The exercise was performed on a fresh Linux Mint 22 consumer machine rather than in the repository development environment.

## Clean consumer baseline

The machine initially had no PHP or Composer installation. The consumer environment was established with:

- PHP 8.3 CLI;
- DOM/XML support;
- ZIP support;
- Composer 2.7.1;
- Git 2.43.0;
- LibreOffice 24.2.7.2.

Composer diagnostics confirmed working Packagist and GitHub access. The distro-packaged Composer installation reported its own non-standard `installed.json` warning; this did not affect package installation and is not an engine defect.

## Public Packagist installation

The documented public package name was tested directly:

```bash
composer require waltdietzney/odt-template-engine
```

The installation succeeded from Packagist without repository-specific configuration. At the time of the exercise, the current stable public version was `v0.9.0`.

This establishes that:

- `waltdietzney/odt-template-engine` is registered on Packagist;
- the package is publicly discoverable;
- the documented normal Composer installation path works.

For testing the current release-candidate development state, a separate consumer project installed `dev-develop`. Packagist exposes that development branch, so an explicit Composer VCS repository override was not required.

## Consumer-project initialization finding

An early development-branch test attempted to run:

```bash
composer config repositories.odt-template-engine vcs https://github.com/WaltDietzney/odt-template-engine.git
```

before a consumer `composer.json` existed. Composer correctly rejected that command.

This was a defect in the F5.2 test procedure, not in the engine or package. If an explicit VCS repository is ever needed, the consumer project must first be initialized, for example with `composer init --no-interaction`. For the actual F5.2 test, direct installation of `dev-develop` through Packagist made the VCS override unnecessary.

## Installed distribution observation

The actual `dev-develop` dependency was installed from the GitHub API zipball selected by Composer. The installed package contained, among other project surfaces, `src/`, `samples/`, `docs/`, `demo/`, `docker/`, and `tests/`; root `output/` was absent.

This demonstrates that `composer.json` `archive.exclude` must not be treated as a description of GitHub-generated dist zipball contents. It controls Composer-created archives, while the tested dependency installation used GitHub's generated distribution archive.

The broader installed surface did not prevent normal consumer use. F5.2 therefore records it as a distribution observation rather than introducing package restructuring without a demonstrated 1.0 blocker.

## Documented Recommended workflow

The documentation Quick Start was reproduced outside the repository checkout.

A small Writer template containing `{{customer_name}}` and `{{total}}` was created manually. The documented lifecycle was then used:

```php
$template = new OdtTemplate(__DIR__ . '/templates/example.odt');
$template->assign([
    'customer_name' => 'Jane Smith',
    'total' => '129.90',
]);
$template->render();
$template->save(__DIR__ . '/output/example-result.odt');
```

The generated ODT opened successfully in LibreOffice as an editable Writer document. Authored formatting was preserved, including bold labels and red replacement values.

A second consumer-authored template exercised `assignRepeating()` with a Writer-authored `foreach` block. Three rows were rendered, the control markers disappeared, and the authored formatting was retained.

These tests establish the documented Recommended Simple Template Processing path independently of the packaged samples.

## Composer sample defect discovered and fixed

The first unchanged canonical Composer sample initially failed from the real dependency location:

```bash
php vendor/waltdietzney/odt-template-engine/samples/sample_L01_variables_filters.php
```

The sample assumed repository-checkout geometry and tried to load a package-local `vendor/autoload.php`. That file does not exist when the package itself is installed below a consumer project's `vendor/` directory.

The accepted change contract was:

> A sample with `distribution = composer` must run unchanged both from a normal repository checkout and from the real Composer installation position below `vendor/waltdietzney/odt-template-engine/`.

PR #130 introduced a bounded shared `samples/bootstrap.php`, switched the canonical L/C/B/S sample entry points to it, and added regression coverage for Composer consumer directory geometry. An existing isolated S03 integration fixture was also updated to copy the new bootstrap dependency into its temporary sample environment.

No engine semantics or public API changed.

CI and documentation checks passed after the fix on PHP 8.2, 8.3, and 8.4.

## External regression proof after the fix

After PR #130 was merged, the clean consumer updated `dev-develop` from commit `e093545` to merge-state commit `1ec515771a32a2f0212b10e14506664ed888bbc9`.

The originally failing L01 command was then repeated unchanged. It exited successfully and generated:

```text
vendor/waltdietzney/odt-template-engine/samples/output/output_L01_variables_filters.odt
```

The generated file size was 8,538 bytes.

The installed package had not contained `samples/output/` before sample execution. Successful generation therefore also proves that the sample workflow can establish the required output path at runtime; no consumer-side manual directory preparation was required.

## Representative three-model consumer proof

F5.2 then exercised one installed canonical sample for each 1.0 product model.

| Product model | Canonical sample | Result |
| --- | --- | --- |
| Simple Template Processing | L01 — Variables & Filters | PASS |
| Structured ODT Construction | B01 — Invoice Template Builder | PASS |
| Writer-native Document Model | L09 — Native Objects | PASS |

B01 generated `output_B01_invoice_template_builder.odt` at approximately 18 KB.

L09 generated `output_L09_native_objects.odt` at approximately 8.4 KB and reported the expected native structure:

```text
Table ProjectMilestones: 3 rows, 2 columns
Frame ProjectNote: text-box (5cm × 1.2cm)
```

This is representative external evidence that Composer autoloading, packaged templates/assets, structured construction, Writer-native inspection, and ODT output work from the real installed dependency position.

## Corrections to F5.1 audit assumptions

Runtime evidence from F5.2 supersedes two static assumptions recorded in `F5_1_PACKAGE_AUDIT.md`:

1. Packagist availability is established. The package is registered, publicly discoverable, and normal `composer require waltdietzney/odt-template-engine` installation succeeded.
2. `archive.exclude` does not describe the actual GitHub-generated dist zipball installed in this exercise. The installed distribution included several paths listed in that Composer archive configuration.

The F5.1 record is corrected alongside this closeout so that the architecture history does not retain statements contradicted by the consumer evidence.

## F5.2 result

**PASS / COMPLETE.**

A fresh external consumer can:

- install the public package through Composer;
- install the current `dev-develop` state through Packagist for pre-release verification;
- follow the documented Recommended render/save workflow;
- create editable ODT output;
- execute canonical Composer samples from the installed dependency location;
- exercise representative Simple Template, Structured ODT Construction, and Writer-native workflows without development-checkout knowledge.

The Composer-sample bootstrap defect discovered by the clean-room exercise was characterized, fixed with bounded regression coverage, merged, and externally re-tested using the originally failing command.

F5.2 introduces no new architecture. F5.3 remains the next F5 gate.

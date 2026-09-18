# TEMPLATE-AUTHORING-01E6 — Automation Atomicity + Integration Closure

**Status:** implementation complete / automated preflight green / manual LibreOffice regression pending

**Branch:** `architecture/template-authoring-01e6-atomicity-integration`

**Base:** `1140da2a771d532df8fa3d69661ae76aa9cdbf15`
**Implementation commit:** `cae6c0fe2f98b99da8fc6a0c9f82ab4cfe67a426` (`feat: add atomic Phase-E automation invocation`)

## Implementation and state boundary

`OdtTemplate::automate(TemplateContract, ConcretePreflightResult)` is the
common Phase-E invocation. It accepts the already inspected contract required
by E3 and the complete READY preflight; it does not inspect the template or
resolve mappings again. The additive contract argument avoids a second
`TemplateContractInspector` pass. The invocation coordinates the existing E4,
E3, then E5 facades under one `PhaseEAutomationExecutor` rollback boundary.

The characterized E3–E5 mutable state is:

- live `content.xml`: classic and structural dependency execution, plus
  Section/Bookmark actions;
- live `styles.xml`: supported header/footer dependency and native-action
  execution;
- live `meta.xml`: E5 metadata mutation;
- all files/directories in the extracted package workspace: E4 Frame image
  replacement and structured Section content can add package resources;
- document-local `StyleContext`, font-face requirements, and fill-image
  requirements: these are not mutated by the current E3–E5 paths, but are
  captured/restored because their pending state affects later save/materialize
  behavior and belongs to `OdtDocumentContext`.

`OdtPackage::snapshotWorkingState()` copies the complete package workspace to a
temporary snapshot directory and asks `OdtDocumentContext` to snapshot cloned
DOMs and the context registries. On rollback, package files are restored and
the original live `DOMDocument`, `StyleContext`, and registry instances remain
authoritative; DOM roots are restored in place. This is one E6-specific,
document/package-scoped mechanism, not a general transaction framework. The
snapshot is outside the package workspace and is removed after the invocation.

The common technical order is **E4 → E3 → E5**. Characterization showed that
E3 can remove/materialize native Sections and thereby shift source-order
evidence used by E4 to localize native targets in the Working DOM. E4 therefore
localizes and executes while that source order is intact. E3 retains its
internal User Fields → structural controls → remaining Classic consumers
order. E5 runs last because it has no target-localization dependency on E3/E4;
this order is technical integration behavior, not a general capability-family
priority.

On ordinary failure, rollback succeeds and the original execution Throwable
is rethrown unchanged. If rollback also fails,
`PhaseEAutomationRollbackException` exposes both the original execution and
rollback failures, with the execution failure retained as the previous
exception. Successful common invocation is marked on the `OdtTemplate`
instance; `load()` resets the marker. A failed, restored invocation does not
consume the lifecycle, so corrected retry remains possible. Specialized E3/E4/E5
facades and imperative methods remain independently callable.

## Compatibility and limits

The common invocation requires the existing `TemplateContract` parameter
because E3's established executor consumes it. Passing that source-derived
object is preferable to a hidden second inspection. The invocation calls no
`render()`, `save()`, refresh, finalization, export, or close operation. Later
imperative operations and explicit save remain supported. The new one-success
gate applies only to the common E6 facade; it does not change specialized
facade semantics.

This is the only additive public `OdtTemplate` method. Its signature includes
the existing `TemplateContract` because E3 consumes that already-inspected
scope/consumer evidence; this avoids hidden source reinspection.

No E7, Phase F, FINALIZATION-01, new mapping/capability semantics, or E6 work
inside individual E3/E4/E5 transactions was introduced. `samples/output/` and
other pre-existing local sample/research artifacts were not changed or
committed. No temporary Git worktree was created under `/tmp` and this branch
was not pushed.

## Changed implementation files

- `src/Document/PhaseEAutomationExecutor.php` and `PhaseEAutomationRollbackException.php` — common READY gate, outer rollback boundary, and dual-failure reporting.
- `src/OdtPackage.php` and `OdtPackageSnapshot.php` — complete extracted-workspace snapshot/restore.
- `src/OdtDocumentContext.php` and `OdtDocumentContextSnapshot.php` — live DOM and document-local collaborator snapshots.
- `src/Style/StyleContext.php`, `src/Document/FontFaceRequirementRegistry.php`, and `src/Document/FillImageRequirementRegistry.php` — restoration in existing owner instances.
- `src/OdtTemplate.php` — one-success-per-load lifecycle marker and common E4→E3→E5 facade.
- `tests/Integration/TemplateAuthoring01E6AutomationAtomicityTest.php` — cross-family success, rollback, package/resource, state, retry, lifecycle, and compatibility characterization.
- `docs/advanced/template-inspection.md`, `docs/architecture/TEMPLATE_AUTHORING_01E_CHANGE_CONTRACT.md`, `docs/ROADMAP.md`, and `docs/FUTURE_DEVELOPMENT.md` — public invocation guidance and factual Phase-E status.

## Verification

- `vendor/bin/phpunit tests/Integration/TemplateAuthoring01E6AutomationAtomicityTest.php --display-warnings` — 4 tests / 50 assertions, passed.
- Focused E6, E3, E4, E5, D4 atomicity, and Mapping regressions — 90 tests / 752 assertions, passed.
- `vendor/bin/phpunit tests/Integration/PublicSampleSmokeTest.php --display-warnings` — 1 test / 199 assertions, passed.
- `composer test` — 938 tests / 6,588 assertions, passed; 8 existing PHPUnit metadata deprecations, no failures/errors.
- `find src tests -name '*.php' -print0 | xargs -0 -n1 php -l` — all PHP files passed.
- `composer validate --no-check-publish` — passed.
- `git diff --check` and base-relative diff check — passed.
- `/tmp/metadata-semantics-docs-venv/bin/zensical build --strict` — passed, no issues found.

The generated regression package
`/tmp/TEMPLATE-AUTHORING-01E6-manual-regression.odt` passed `unzip -t`.
LibreOffice 24.2.7.2 headlessly opened and round-tripped it to
`/tmp/e6-lo-roundtrip/TEMPLATE-AUTHORING-01E6-manual-regression.odt`; that
package also passed `unzip -t` and engine reopen retained Creator and all three
Keywords. PDF rendering succeeded at
`/tmp/e6-lo-pdf/TEMPLATE-AUTHORING-01E6-manual-regression.pdf` (one A4 page);
extracted text contains the dependency replacement, both collection items,
and Section replacement. LibreOffice emitted the environment warning
`failed to launch javaldx`; conversions succeeded. A first sandboxed attempt
also encountered read-only dconf and was rerun successfully outside the
sandbox.

The required human LibreOffice Writer open/save/close/reopen inspection is
**PENDING**. Headless package/render checks do not replace that gate. No
rendering repair warning was reported by the headless conversion, but only the
manual Writer inspection can complete the gate.

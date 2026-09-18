# TEMPLATE-AUTHORING-01E5 — Document Capability Automation Completion

**Branch:** `architecture/template-authoring-01e5-document-capabilities`
**Base:** `02868882db7b66aa419b29ee783f54a3a33bf05b`
**Implementation commit:** `72e1eb87a6f560cce5b4e13442d2d2c87ef7bf97`
**Status:** implementation complete / automated preflight green / manual LibreOffice regression pending

## Implementation

`OdtTemplate::automateDocumentCapabilities(ConcretePreflightResult)` is the
narrow E5 facade. It passes the current document's `MetadataManager` and the
already resolved/preflighted result to the stateless
`DocumentCapabilityAutomationExecutor`.

The executor requires a fully READY result, selects only
`document_capability` operations, and builds the complete metadata write set
before mutation. It verifies each operation's readiness, concrete resolution
membership in the supplied `MappingResolution`, explicit provenance, canonical
`metadata` target identity, capability/payload evidence, applicability, and
PRESENT non-item data state. It rejects repeated canonical targets before any
write; this also covers identical-source duplicates that the existing static
validator does not classify as competing sources. Different targets are
delegated together in one `MetadataManager::set()` call.

The executor performs no application-data traversal, mapping resolution,
payload revalidation, ODF/XML mutation, or execution of dependency/native-action
operations. `MetadataManager` remains the sole metadata mutation owner. Its
canonical creator names, keyword-list semantics, aliases, and imperative
unknown-key behavior remain unchanged. The public facade does not render, save,
or finalize; the caller retains lifecycle control.

## Scope and compatibility

Only explicit READY metadata capabilities execute. Dependency and native
action operations in the same READY preflight are ignored by E5. A non-READY
preflight or contradictory/missing/duplicate E5 evidence fails before metadata
mutation. There is no last-wins rule.

No E3/E4 behavior, imperative `setMeta()` / `getMeta()` behavior, render/save
lifecycle, or public/protected compatibility facade was changed. E5 does not
add E6 rollback, cross-family orchestration, custom metadata, or a generic
capability dispatcher. **E6 has not started.**

## Verification

- `vendor/bin/phpunit tests/Document/DocumentCapabilityAutomationTest.php --display-warnings` — 8 tests / 58 assertions, no warnings.
- Focused E5, metadata, mapping, preflight, E1, E3, and E4 regressions — 105 tests / 912 assertions, no warnings.
- `vendor/bin/phpunit tests/Integration/PublicSampleSmokeTest.php --display-warnings` — 1 test / 199 assertions.
- `composer test` — 934 tests / 6,538 assertions; 8 PHPUnit deprecations.
- `find src tests -name '*.php' -print0 | xargs -0 -n1 php -l` — all 310 PHP files passed.
- `composer validate --no-check-publish` — passed.
- `git diff --check 02868882db7b66aa419b29ee783f54a3a33bf05b...HEAD` — passed for the final commits.
- `zensical build --strict` — passed, no documentation issues.

The temporary E5 regression document was generated through real
TemplateContract inspection, concrete preflight, E5 execution, and explicit
save at:

`/tmp/TEMPLATE-AUTHORING-01E5-manual-regression.odt`

`unzip -t` passed. LibreOffice headless opened and round-tripped it to
`/tmp/TEMPLATE-AUTHORING-01E5-lo-odt/TEMPLATE-AUTHORING-01E5-manual-regression.odt`
and rendered
`/tmp/TEMPLATE-AUTHORING-01E5-lo-pdf/TEMPLATE-AUTHORING-01E5-manual-regression.pdf`.
The round-trip ODT passed ZIP integrity and engine reload checks; canonical
creator, initial creator, three separate keywords, coverage, language, both
dates, editing cycles, and editing duration were retained. LibreOffice
updated the generator field. Headless runs warned that `javaldx` could not be
launched; conversion and round-trip still succeeded.

The required manual LibreOffice Writer File Properties inspection and
save/close/reopen cycle have not been performed. E5 is therefore not declared
fully GREEN pending that user-run visual/manual gate. Open the source ODT
above in Writer and verify no repair warning, Creator, Initial Creator, the
three separate Keywords, Coverage, and representative typed metadata; save,
close, reopen, and confirm stability.

## Changed files

- `src/Document/DocumentCapabilityAutomationExecutor.php`
- `src/OdtTemplate.php`
- `tests/Document/DocumentCapabilityAutomationTest.php`
- `tests/Support/MappingTemplateFixture.php` (declares namespaces used by its generated fixture so E5 tests remain warning-free)
- `docs/architecture/TEMPLATE_AUTHORING_01E5_COMPLETION.md`

No sample output or local regression artifact is part of the change.

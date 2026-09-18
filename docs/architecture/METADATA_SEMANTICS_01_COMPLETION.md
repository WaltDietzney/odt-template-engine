# METADATA-SEMANTICS-01 Completion

**Branch:** `architecture/template-authoring-01e4-native-object-actions`
**Base:** `7c0033b4702dab4b17ae5becfb6f50e02e3f2298`
**Status:** COMPLETE / GREEN

## Outcome

`MetadataManager` remains the sole document-local mutation owner. The public
`OdtTemplate::setMeta()` / `getMeta()` facades remain in place. Canonical
identities are `creator` and `initial_creator`; imperative input aliases
`author` and `initial_author` remain accepted. `getMeta()` returns the
canonical keys and also retains the historically observed alias keys with the
same values.

Pre-change characterization established that `getMeta()` returned
`author` / `initial_author` (not canonical creator keys), and that it exposed
only the first existing keyword even when `meta.xml` contained multiple
`meta:keyword` elements. The compatibility decision is additive for creator
keys: no existing returned key is removed. Keyword reads intentionally become
a complete list, as required by the accepted contract.

Keywords now use a list of strings to represent repeated ODF elements. Setting
a list replaces the complete keyword collection while preserving unrelated
metadata. The legacy string input remains exactly one keyword without
delimiter parsing. Unknown imperative keys remain ignored. `coverage` is
read/written through the existing `dc:coverage` element as supported
LibreOffice / Dublin Core extended metadata; no additional extended fields
were added.

## Phase-E capability and preflight

The capability catalog now exposes canonical `creator` and `initial_creator`
targets with target-specific payload semantics:

- strings: title, subject, description, creator, initial_creator, generator,
  coverage;
- list of strings: keywords;
- XML Schema `dateTime`: creation_date, date;
- ODF language tag: language;
- non-negative integer: editing_cycles;
- XML Schema `duration`: editing_duration.

The bounded `MetadataPayloadValidator` is used by `ConcreteMappingPreflight`;
booleans/floats are not accepted generically. Capability projection and
preflight expose the corresponding semantic payload kind. No metadata
mutation/executor was added. ODF datatype references are documented against
the OASIS OpenDocument 1.2 specification.

## Compatibility and scope

Imperative singular-field string-writing behavior, input aliases, unknown-key
behavior, save/reload lifecycle, and existing public methods remain. The
imperative keyword string remains accepted as one element. Phase-E preflight
is stricter and target-specific. No E5 Document Capability executor,
E3/E4 orchestration, or E6 atomicity was started. No automatic metadata update
occurs during save.

Updated public samples use canonical creator names and list-valued multiple
keywords. `docs/advanced/metadata.md` documents aliases, collection semantics,
the Phase-E value types, and the bounded coverage support.

## Verification

- Pre-change compatibility characterization: `OdtTemplatePackageLifecycleTest`
  passed, 7 tests / 37 assertions.
- Focused metadata, mapping, preflight, and document-service regression:
  79 tests / 653 assertions passed.
- `PublicSampleSmokeTest`: 1 test / 199 assertions passed.
- `composer test`: 926 tests / 6,480 assertions passed; 8 PHPUnit metadata
  deprecations remain.
- PHP lint across `src/` and `tests/`: 241 PHP files, all syntax checks passed.
- `composer validate --no-check-publish`: passed.
- `git diff --check`: passed.
- Strict docs build: passed with Zensical (`zensical build --strict`;
  no documentation issues).
- Temporary ODT ZIP integrity, engine save/reopen, LibreOffice headless PDF
  conversion, and LibreOffice ODT round trip passed. LibreOffice updated the
  generator field during its round trip; both LibreOffice commands emitted the
  environment warning that `javaldx` could not be launched.
- Manual LibreOffice Writer regression: GREEN. The updated Sample 04 opened
  without a repair warning and displayed the canonical Creator and Initial
  creator values, all three keywords, and the expected typed metadata values.
  LibreOffice's document properties showed the expected creator/initial-creator
  lifecycle information and keyword collection while preserving unrelated
  template metadata. A manual save, close, and reopen cycle remained stable.

## Closure

The implementation diff `7c0033b4702dab4b17ae5becfb6f50e02e3f2298` →
`485d77392fda365b1e7a9ebb23f279e5c0fca48c` received final architecture
review with no blocking findings. Automated validation and the required manual
LibreOffice regression are GREEN.

**METADATA-SEMANTICS-01 is COMPLETE.** The metadata semantics cleanup no longer
blocks TEMPLATE-AUTHORING-01E5.

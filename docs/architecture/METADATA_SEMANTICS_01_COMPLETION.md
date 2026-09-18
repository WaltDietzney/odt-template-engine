# METADATA-SEMANTICS-01 Completion

**Branch:** `architecture/template-authoring-01e4-native-object-actions`
**Base:** `7c0033b4702dab4b17ae5becfb6f50e02e3f2298`
**Status:** implementation complete / automated preflight green / manual LibreOffice regression pending

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

A temporary ODT was saved at
`/tmp/METADATA-SEMANTICS-01-manual.odt`. ZIP integrity and engine
save/reopen checks passed. LibreOffice headless converted it successfully to
`/tmp/METADATA-SEMANTICS-01-manual.pdf` and performed an ODT round trip at
`/tmp/METADATA-SEMANTICS-01-lo-roundtrip/METADATA-SEMANTICS-01-manual.odt`.
The round-tripped package retained creator, initial creator, all three
keywords, coverage, language, dates, editing cycles, and editing duration;
LibreOffice updated the generator field. Both LibreOffice commands emitted
the environment warning that `javaldx` could not be launched. The packages
passed ZIP integrity checks. This does not verify Writer's GUI metadata
dialog, repair-warning behavior, or a manual Writer save/close/reopen cycle.

## Remaining manual gate

Open `/tmp/METADATA-SEMANTICS-01-manual.odt` in LibreOffice Writer and verify:

1. It opens without a repair warning.
2. Creator and initial creator show the intended values where Writer exposes
   them.
3. The three keywords (`finance`, `report`, `2026`) remain separate.
4. Coverage remains present where Writer exposes it.
5. Save, close, and reopen the document; verify those values remain stable.

Record any LibreOffice normalization that materially changes the metadata
semantics. The manual regression is pending; this record does not declare the
slice fully GREEN or authorize E5.

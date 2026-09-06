# D5G-C — Lifecycle Compatibility Narrowing

Status: implementation record

## Scope

D5G-C narrows the document-local finalization decisions made by
`OdtTemplate::save()` after the D5G-A audit and D5G-B characterization. It does
not replace the legacy `assign()` / `render()` lifecycle, remove compatibility
APIs, or redesign `StyleWriter`.

The modern semantic path remains authoritative. The legacy path remains a
separate compatibility lifecycle, including its two-DOM processing and
protected hook dispatch.

## Baseline and audit

The implementation baseline is `f1703fd6` (`architecture/d5g-change-contract`).
Before this slice, `legacyStructuredValuesMaterialized` was written when any
`OdtElement` value entered `setValuesInDom()`, before placeholder replacement
could succeed. It was reset by `load()`. `save()` used it as one switch for:

* legacy image/fill-image injection;
* legacy frame-style finalization;
* unfiltered table-style finalization;
* unfiltered table-cell-style finalization.

`legacyFrameStylesMaterialized` was read to avoid re-finalizing already adopted
frame names and reset by `load()`. It was written after `save()` adopted the
current frame names. `refresh()` finalized the current DOM with its historical
frame-disabled path and then called `load()`.

The direct `StyleWriter::writeAllStyles()` defaults were not changed. Its
callers may still request broad static compatibility output.

## Decision matrix before narrowing

| Situation | Coarse state | Save routing before D5G-C | Required current evidence |
|---|---|---|---|
| no structured legacy value | false | semantic table/cell filters; no legacy frame/image bridge | semantic/current references only |
| legacy Paragraph, RichText, ListElement | true | table/cell registries were unfiltered; frame/image hooks were enabled | no unrelated table/cell state |
| legacy ImageElement | true | current referenced image/frame plus all static table/cell entries | current image/frame references; referenced table/cell only |
| legacy CircularImageElement | true | current fill/image/frame plus all static table/cell entries | current fill/image/frame references; referenced table/cell only |
| legacy DrawTextBox | true | current frame plus all static table/cell entries | current frame reference; referenced table/cell only |
| legacy RichTable | true | current table/cell references plus all static table/cell entries | current table/cell references only |
| missing placeholder | true | the same broad table/cell route as successful legacy insertion | no inserted current reference |
| setElement() only | false | document-local semantic definitions and current-reference filters | semantic context and current DOM |
| mixed setElement() + legacy value | true | legacy broad table/cell route could override the semantic boundary | semantic definitions plus current structural references |

The focused pre-change characterization proved the important observable cases:
a legacy paragraph and a missing placeholder caused unrelated registered table
and table-cell definitions to appear in `styles.xml`. The direct public
registration/adoption tests also proved that current DOM references, rather
than producer class names, are the compatibility signal for direct legacy
reference elements.

## Minimal implementation

`OdtTemplate::save()` now always supplies the current-document table and
table-cell reference sets to `StyleWriter::writeAllStyles()`. The semantic
table-cell exclusion set is also always supplied. Consequently:

* a registered table or table-cell style is eligible during template
  finalization only when the current document structurally references that
  name and family;
* an unrelated process-global entry is not imported by a legacy paragraph,
  image, frame, table, or missing placeholder;
* a current legacy RichTable/table-cell reference remains adoptable;
* the process-global registries remain observable and unchanged.

The legacy image bridge and frame bridge continue to use the existing
`legacyStructuredValuesMaterialized` and current-document reference logic.
This preserves direct reference-element compatibility, including callers that
populate static registries directly without exposing the corresponding legacy
getter requirement. `legacyFrameStylesMaterialized` continues to prevent
duplicate frame adoption.

`refresh()` uses the same current table/table-cell reference filtering while
retaining its existing historical frame-disabled behavior and `load()` reset.
No public signature, protected visibility, or `StyleWriter` default behavior
changed.

## Compatibility results

The following remain intentionally unchanged:

* `assign()` / `render()` continue to process `content.xml` and `styles.xml`
  independently;
* styles-part placeholders remain supported;
* protected replacement and image-injection hooks remain dispatched;
* ImageElement derived legacy placement synchronization and its characterized
  package-resource omission remain unchanged;
* CircularImageElement fill-image name/style compatibility state remains
  adopted;
* DrawTextBox frame compatibility remains available;
* legacy RichTable rendering is not redirected to the SR-07 semantic path;
* load/refresh reset behavior is retained;
* direct `StyleMapper` registration and direct default `StyleWriter` calls
  remain broad compatibility APIs.

The intentional ownership correction is that a legacy table-cell definition
which is element-owned and already materialized in the current automatic
styles no longer receives a second common definition solely because the
process-global registry contains it.

## Repeated lifecycle and isolation evidence

The D5G-B repeated render characterization now observes stable `styles.xml`
for the representative producers after unrelated table/cell compatibility
entries are filtered. Native content remains stable. Existing image, fill,
frame, table, and semantic isolation suites remain green.

Static registrations remain visible through `StyleMapper`, but current-document
reference filtering prevents unrelated entries from affecting serialized
output. No registry is cleared and no current-document global pointer was
introduced.

## D5G-D handoff / remaining residue

The following remain outside D5G-C:

* broad static registry lifecycle and cleanup policy;
* any redesign of `StyleWriter` compatibility defaults;
* ImageElement physical-resource behavior in the legacy path;
* general repeated `assign()` / `render()` semantics;
* deeper provenance questions for legacy registrations;
* any lifecycle abstraction or `CompatibilityContext`.

The coarse boolean remains as a compatibility-lifecycle marker and still
controls the image/frame legacy bridge. It is no longer authoritative for
unrelated table/table-cell finalization. Further reduction requires a separate
D5G-D compatibility decision.

## Validation evidence

The new routing characterization and relevant D5G/SR-06/SR-07 suites pass.
The only changed prior characterization expectation is the intentional
SR-07/D5G ownership correction from one automatic plus one common legacy cell
definition to the single current-document automatic definition.

No sample output, `tmp/` artifact, lock file, public API, or compatibility
registry was changed by this slice.

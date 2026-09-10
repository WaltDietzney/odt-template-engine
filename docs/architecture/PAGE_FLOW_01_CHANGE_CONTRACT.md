# PAGE-FLOW-01 — Change Contract

## Status

**Reviewed implementation contract. Implementation has started only for the accepted paragraph-flow slice.**

This contract translates the completed PAGE-FLOW-01 research into a bounded 1.0 implementation scope.

It is intentionally narrower than the full set of Writer page-layout features, while explicitly preserving page-style authoring as a required future engine capability.

The governing rule is:

> **The engine describes or preserves native ODF flow semantics; LibreOffice/Writer computes actual pagination.**

## 1. Purpose

PAGE-FLOW-01 must make the researched native flow semantics reliably preservable and, where the engine owns generated paragraph content, expressible through the existing style architecture.

The milestone must not become a PHP pagination engine or a complete Writer page-style authoring framework.

This scope boundary does **not** mean that programmatic page-style authoring is unnecessary. PAGE-FLOW-01 establishes the semantic model that a later dedicated capability must build on.

## 2. Semantic baseline

The implementation must preserve the following researched distinctions.

### 2.1 Page identity versus geometry

```text
style:master-page
    = page/master style identity, succession, page-owned content

style:page-layout
    = referenced page/header/footer geometry
```

These concepts must not be merged into one generic page-layout abstraction.

### 2.2 Break versus page-style request

```text
fo:break-before / fo:break-after
    = paragraph-flow semantics

style:master-page-name
    = page-style/master-page reference
```

A new page and a requested page style are separate operations.

### 2.3 Section structure versus pagination

`text:section` owns structure. Child paragraphs, tables, lists, and frames retain their own flow/layout semantics. Writer computes physical page boundaries.

### 2.4 Page-owned content

Header/footer content under `style:master-page` is normal structured ODF content and remains part of template processing and document lifecycle.

## 3. Required implementation scope

PAGE-FLOW-01 implementation is limited to the following.

### 3.1 Complete required paragraph-flow mapping

Add explicit supported paragraph-style mappings for the researched missing properties:

```text
keep-together  -> fo:keep-together
widows         -> fo:widows
orphans        -> fo:orphans
```

Existing mappings remain:

```text
keep-with-next -> fo:keep-with-next
break-before   -> fo:break-before
break-after    -> fo:break-after
```

The implementation must use the current document-local semantic style path rather than introduce a second flow-style registry.

### 3.2 Preserve native values rather than reinterpret pagination

The mapper/materializer must serialize semantic values; it must not calculate page fit.

At minimum, tests must cover Writer-compatible values characterized by research, including:

```text
fo:keep-with-next="always"
fo:keep-together="always"
fo:widows="N"
fo:orphans="N"
fo:break-before="page"
fo:break-after="page"
```

If existing public style-option behavior accepts native/raw values, PAGE-FLOW-01 must not unnecessarily narrow that compatibility.

### 3.3 Characterize preservation of authored page/master semantics

Permanent automated coverage must ensure relevant engine operations do not lose or rewrite authored relationships such as:

```text
paragraph style -> style:master-page-name
master page     -> style:next-style-name
master page     -> style:page-layout-name
```

The test objective is preservation, not Writer pagination emulation.

### 3.4 Keep page-owned content in normal processing

Permanent characterization must retain evidence that:

- scalar replacement reaches master-page-owned header/footer content;
- nested scalar replacement inside native header content works;
- native fields such as `text:page-number` survive;
- ordinary structured `setElement()` insertion reaches page-owned content;
- the established `setImage()` path works in page-owned content;
- save/reopen preserves the resulting ODT structures/resources.

The existing PAGE-FLOW-01D test should be adjusted only where necessary to describe the established public image path accurately.

## 4. Page-style authoring boundary for this milestone

PAGE-FLOW-01 does **not** implement a broad new public page-style API such as:

```text
$template->pageStyle(...)
$template->masterPage(...)
$template->setFirstPageHeader(...)
$template->definePageStyle(...)
$template->keepSectionOnPage(...)
```

This is a milestone boundary, **not a conclusion that page-style authoring is unnecessary**.

The engine is expected to gain a dedicated future page-style capability. At minimum, that future design must be able to reason explicitly about:

- referencing an existing page/master style;
- modifying an existing page/master style where semantically valid;
- defining page/master styles where justified;
- assigning a page/master style to document flow;
- editing native succession such as `First Page -> Standard`;
- preserving the distinction between master-page identity/content and page-layout geometry.

The exact public API is deliberately not approved by PAGE-FLOW-01. It must be designed from the native ODF model established here rather than by extending `PageLayoutManager` into a generic page-style service.

LibreOffice-authored page styles remain the preferred native-first authoring mechanism where the template owns layout until that dedicated capability is designed.

## 5. Page-style reference/transition capability

### Decision for PAGE-FLOW-01

Programmatic page-style assignment is a **required future engine capability**, but it is not implemented opportunistically inside PAGE-FLOW-01.

The research established that `style:master-page-name` is semantically distinct from paragraph properties such as `fo:break-before`. Writer places the page-style reference on the paragraph `style:style` element rather than inside `style:paragraph-properties`.

Therefore:

- authored `style:master-page-name` relationships must be preserved now;
- generated page-style assignment must not be claimed through a raw paragraph-property escape hatch if that would serialize the attribute at the wrong ODF structural level;
- PAGE-FLOW-01 does not invent a convenience API merely for symmetry;
- the later page-style authoring capability must provide a semantically correct way to assign/reference page styles in document flow.

This removes the earlier conditional assumption that existing paragraph-style raw properties might already constitute sufficient generated page-style support. Preservation is part of PAGE-FLOW-01; programmatic generation/assignment is explicitly deferred but required.

## 6. `PageLayoutManager` compatibility boundary

`PageLayoutManager` remains a bounded existing-layout geometry mutator.

It may continue to:

- resolve an existing master page;
- follow its `style:page-layout-name`;
- mutate supported page-layout properties.

It must not silently grow responsibility for:

- defining page-style identity;
- page-style succession;
- page-style assignment from content;
- header/footer content ownership;
- pagination decisions.

A future page-style authoring capability may coordinate with page-layout geometry, but it must preserve these ownership distinctions rather than absorbing them into the current manager.

If refactoring is required, preserve meaningful public/protected facade behavior while moving implementation responsibility behind it.

## 7. Section behavior

No production pagination behavior is added to Section APIs.

Existing SECTION-03 operations must continue to:

- clone exact native Section subtrees;
- preserve paragraph/table/list/style references unless identity rules require rewriting;
- instantiate repeated/nested Sections structurally;
- remove prototypes as already defined;
- leave physical page breaking to Writer.

No `keepSectionOnPage()` behavior is introduced.

## 8. Page-owned content behavior

No separate header/footer processing subsystem is introduced.

The existing cross-document-part processing model remains authoritative where characterized:

```text
content.xml
styles.xml
```

Header/footer content continues to participate through the same template-processing and structured-materialization mechanisms where those mechanisms are valid for the target content.

Page-owned target/addressing APIs are explicitly deferred unless a separate requirement justifies them. Such future addressing must remain compatible with the page-style ownership model established by PAGE-FLOW-01.

## 9. `ImageElement` header discrepancy

PAGE-FLOW-01D exposed the following bounded compatibility finding:

```text
ImageElement in normal body content      ✓ Writer-visible
ImageElement in page-owned header        ✗ not Writer-visible
setImage() in page-owned header           ✓ Writer-visible
```

Changing only the generated graphic parent style from `Standard` to `Graphics` did not fix the Writer rendering.

### Contract decision

Do not repair or redesign the generic graphic materialization pipeline inside PAGE-FLOW-01 unless the implementation slices unexpectedly depend on it.

Record and retain the finding for a focused graphic/structured-materialization investigation.

PAGE-FLOW-01 tests must not incorrectly claim generic `ImageElement` Writer rendering in headers when current manual evidence disproves it.

## 10. Production files expected to change

The smallest likely production scope is:

```text
src/Utils/StyleMapper.php
```

Additional production files may change only if required by evidence from focused tests, for example to preserve semantic values through `Paragraph` -> `StyleRequirement` -> materialization.

Likely supporting test files include focused unit/integration tests around:

- paragraph style mapping;
- semantic paragraph requirements;
- page/master relationship preservation;
- page-owned content processing.

Do not change unrelated table/frame/image architecture in the same implementation slice.

## 11. Test contract

### 11.1 Focused automated tests

Required coverage includes:

- `keep-with-next` mapping/preservation;
- `keep-together` mapping/materialization;
- widows mapping/materialization;
- orphans mapping/materialization;
- break-before mapping/preservation;
- break-after mapping/preservation;
- inherited/native paragraph style references preserved through structured Section operations;
- First Page -> Standard master-page relationship preservation;
- authored `style:master-page-name` preservation;
- page-owned scalar and structured processing;
- native page-number field preservation;
- established `setImage()` page-owned resource behavior.

Tests must not present raw paragraph-property serialization of `style:master-page-name` as generated page-style assignment support.

### 11.2 Existing characterization suites

At minimum rerun relevant PAGE-FLOW/SECTION/style tests plus `PublicSampleSmokeTest` where the implementation touches public authoring behavior.

### 11.3 Full preflight

Before merge:

```text
focused PHPUnit tests
relevant integration tests
PublicSampleSmokeTest
composer test
PHP lint for src/ and tests/
git diff --check
composer validate when relevant
```

Documentation consistency must be checked if public behavior or terminology changes.

## 12. Manual LibreOffice regression contract

Automated XML assertions are insufficient for rendering-relevant changes.

Before PAGE-FLOW-01 merge, manually verify at least one processed multi-page Writer document containing:

- First Page -> Standard transition;
- distinct first/following page-owned content;
- native page-number field;
- paragraph keep semantics near a page boundary;
- a repeated/nested Section crossing a Writer-computed page boundary;
- page-owned image insertion through the established supported image path.

Writer must remain the source of the final pagination result.

## 13. Explicitly deferred work

The following are outside PAGE-FLOW-01 implementation scope:

- dedicated programmatic page-style authoring and mutation -> required future capability recorded in `FUTURE_DEVELOPMENT.md`;
- programmatic page-style assignment/transition authoring -> same future capability;
- table row/page splitting policy and professional table pagination -> `TABLE-LAYOUT-01`;
- frame positioning/geometry redesign -> `FRAME-LAYOUT-01`;
- generic `ImageElement` header rendering discrepancy -> focused graphic/structured-materialization follow-up unless promoted by new evidence;
- exhaustive first/left/right header/footer APIs;
- page-number restart/continuation convenience APIs;
- PHP page-height/page-count calculation;
- renderer-neutral pagination abstraction;
- CV-specific pagination heuristics.

Deferral of page-style authoring is a sequencing decision, not rejection of the capability.

## 14. Proposed implementation slices

Consistent with the current preference for larger coherent passes, PAGE-FLOW-01 should normally require no more than two implementation slices unless tests uncover real compatibility risk.

### Slice 1 — Paragraph flow completion and characterization

- add explicit missing paragraph-flow mappings;
- add focused semantic/materialization tests;
- characterize existing break/keep behavior;
- characterize preservation of authored `style:master-page-name` without pretending that raw paragraph properties provide generated page-style assignment;
- make no new public page-style API in this milestone.

### Slice 2 — Integration preservation and preflight

- consolidate page/master relationship preservation coverage;
- align PAGE-FLOW-01D image characterization with the established `setImage()` path;
- run Section/page-owned integration tests;
- perform manual Writer regression;
- update architecture docs/ROADMAP/FUTURE_DEVELOPMENT as appropriate;
- full preflight.

A separate future page-style authoring milestone should design the required generation/mutation/assignment capability from the PAGE-FLOW-01 semantic model rather than being introduced as an incidental third slice.

## 15. Completion criteria

PAGE-FLOW-01 implementation is complete when:

- all required paragraph flow semantics in this milestone are expressible through the existing style architecture;
- authored page/master relationships survive supported document operations;
- page-owned content remains processable without a parallel subsystem;
- Sections preserve flow semantics without taking pagination ownership;
- no PHP pagination logic has been introduced;
- no incorrect claim of generated page-style assignment support has been introduced;
- the required future page-style authoring capability is explicitly retained in project planning;
- all focused/full automated tests pass;
- manual LibreOffice regression is clean;
- the bounded `ImageElement` header discrepancy remains documented if still unresolved;
- documentation reflects actual behavior and deferrals.

Only after these criteria are met should PAGE-FLOW-01 be marked complete and merged to `develop`.

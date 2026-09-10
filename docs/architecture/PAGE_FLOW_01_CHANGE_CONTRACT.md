# PAGE-FLOW-01 — Change Contract

## Status

**COMPLETE / FINAL GO — both accepted implementation slices, integration preservation, manual LibreOffice regression, and the full PAGE-FLOW-01 preflight are complete.**

This contract translated the completed PAGE-FLOW-01 research into a bounded 1.0 implementation scope. The completed milestone stayed within that scope.

The final production change is intentionally small: the missing paragraph-flow mappings were added through the existing document-local style architecture. Authored page/master relationships, Section flow behavior, and page-owned content are covered by permanent characterization and integration tests. No PHP pagination engine or broad page-style authoring API was introduced.

The governing rule remains:

> **The engine describes or preserves native ODF flow semantics; LibreOffice/Writer computes actual pagination.**

## 1. Purpose

PAGE-FLOW-01 makes the researched native flow semantics reliably preservable and, where the engine owns generated paragraph content, expressible through the existing style architecture.

The milestone does not become a PHP pagination engine or a complete Writer page-style authoring framework.

This scope boundary does **not** mean that programmatic page-style authoring is unnecessary. PAGE-FLOW-01 establishes the semantic model that a later dedicated capability must build on.

## 2. Semantic baseline

The implementation preserves the following researched distinctions.

### 2.1 Page identity versus geometry

```text
style:master-page
    = page/master style identity, succession, page-owned content

style:page-layout
    = referenced page/header/footer geometry
```

These concepts are not merged into one generic page-layout abstraction.

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

## 3. Completed implementation scope

### 3.1 Required paragraph-flow mapping

The researched paragraph-style mappings are supported:

```text
keep-with-next -> fo:keep-with-next
keep-together  -> fo:keep-together
widows         -> fo:widows
orphans        -> fo:orphans
break-before   -> fo:break-before
break-after    -> fo:break-after
```

The implementation uses the existing document-local semantic style path and introduces no second flow-style registry.

### 3.2 Native values are preserved rather than reinterpreted

The mapper/materializer serializes semantic values; it does not calculate page fit. Permanent tests cover Writer-compatible flow values and retain raw/native compatibility where already supported.

### 3.3 Authored page/master semantics are preserved

Permanent automated coverage verifies preservation of relationships including:

```text
paragraph style -> style:master-page-name
master page     -> style:next-style-name
master page     -> style:page-layout-name
```

The tests verify preservation, not Writer pagination emulation.

### 3.4 Page-owned content remains in normal processing

Permanent characterization covers:

- scalar replacement in master-page-owned header/footer content;
- nested scalar replacement inside native header content;
- preservation of native fields such as `text:page-number`;
- ordinary structured `setElement()` insertion into page-owned content;
- the established `setImage()` path in page-owned content;
- save/reopen preservation of resulting ODT structures and resources.

## 4. Page-style authoring boundary

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

Programmatic page-style assignment is a **required future engine capability**, but it was not implemented opportunistically inside PAGE-FLOW-01.

The research established that `style:master-page-name` is semantically distinct from paragraph properties such as `fo:break-before`. Writer places the page-style reference on the paragraph `style:style` element rather than inside `style:paragraph-properties`.

Therefore:

- authored `style:master-page-name` relationships are preserved now;
- generated page-style assignment is not claimed through a raw paragraph-property escape hatch at the wrong ODF structural level;
- PAGE-FLOW-01 did not invent a convenience API merely for symmetry;
- the later page-style authoring capability must provide a semantically correct way to assign/reference page styles in document flow.

Preservation is part of the completed PAGE-FLOW-01 baseline; programmatic generation/assignment remains explicitly deferred but required.

## 6. `PageLayoutManager` compatibility boundary

`PageLayoutManager` remains a bounded existing-layout geometry mutator.

It may continue to:

- resolve an existing master page;
- follow its `style:page-layout-name`;
- mutate supported page-layout properties.

It does not own:

- defining page-style identity;
- page-style succession;
- page-style assignment from content;
- header/footer content ownership;
- pagination decisions.

A future page-style authoring capability may coordinate with page-layout geometry, but it must preserve these ownership distinctions rather than absorbing them into the current manager.

## 7. Section behavior

No production pagination behavior was added to Section APIs.

Existing SECTION-03 operations continue to:

- clone exact native Section subtrees;
- preserve paragraph/table/list/style references unless identity rules require rewriting;
- instantiate repeated/nested Sections structurally;
- remove prototypes as already defined;
- leave physical page breaking to Writer.

No `keepSectionOnPage()` behavior was introduced.

## 8. Page-owned content behavior

No separate header/footer processing subsystem was introduced.

The existing cross-document-part processing model remains authoritative where characterized:

```text
content.xml
styles.xml
```

Header/footer content continues to participate through the same template-processing and structured-materialization mechanisms where those mechanisms are valid for the target content.

Page-owned target/addressing APIs remain deferred unless a separate requirement justifies them.

## 9. `ImageElement` header discrepancy

PAGE-FLOW-01D exposed the following bounded compatibility finding:

```text
ImageElement in normal body content      ✓ Writer-visible
ImageElement in page-owned header        ✗ not Writer-visible
setImage() in page-owned header           ✓ Writer-visible
```

Changing only the generated graphic parent style from `Standard` to `Graphics` did not fix the Writer rendering.

No generic graphic repair or redesign was made inside PAGE-FLOW-01. The finding is retained as `GRAPHIC-PART-COMPAT-01` in `FUTURE_DEVELOPMENT.md`. PAGE-FLOW-01 page-owned image coverage uses the established `setImage()` path and does not claim generic `ImageElement` header compatibility.

## 10. Production scope

The production implementation remained limited to:

```text
src/Utils/StyleMapper.php
```

The remaining branch changes are architecture/evidence documentation and focused characterization/integration tests. No unrelated table/frame/image architecture was changed.

## 11. Test contract — completed

Permanent automated coverage includes:

- `keep-with-next` mapping/preservation;
- `keep-together` mapping/materialization;
- widows mapping/materialization;
- orphans mapping/materialization;
- break-before mapping/preservation;
- break-after mapping/preservation;
- inherited/native paragraph style references through structured Section operations;
- First Page -> Standard master-page relationship preservation;
- authored `style:master-page-name` preservation;
- page-owned scalar and structured processing;
- native page-number field preservation;
- established `setImage()` page-owned resource behavior.

The final PAGE-FLOW-01 preflight completed successfully with:

- focused PAGE-FLOW tests: 8 tests / 141 assertions;
- relevant style/materialization regression: 28 tests / 266 assertions;
- `PublicSampleSmokeTest`: 1 test / 185 assertions;
- full suite: 604 tests / 3884 assertions;
- PHP lint for `src/` and `tests/`: clean;
- `composer validate`: clean;
- `git diff --check`: clean.

The full PHPUnit run reported seven deprecation events caused by pre-existing PHPUnit doc-comment metadata in legacy tests. No PAGE-FLOW-01 test is the source of those deprecations; they are non-blocking for this milestone and were not opportunistically repaired here.

## 12. Manual LibreOffice regression — completed

Rendering-sensitive behavior was manually verified in LibreOffice Writer during PAGE-FLOW-01C and PAGE-FLOW-01D research/regression work.

The combined evidence covers:

- First Page -> Standard transition;
- distinct first/following page-owned content;
- native page-number field;
- paragraph flow semantics under Writer pagination;
- repeated/nested Section content participating in Writer-computed multi-page flow;
- page-owned image insertion through the established `setImage()` path.

The manual comparison also exposed and bounded the `ImageElement` header discrepancy documented above. Writer remains the source of the final pagination result.

## 13. Explicitly deferred work

The following remain outside the completed PAGE-FLOW-01 implementation scope:

- dedicated programmatic page-style authoring and mutation -> `PAGE-STYLE-AUTHORING-01`;
- programmatic page-style assignment/transition authoring -> same future capability;
- table row/page splitting policy and professional table pagination -> `TABLE-LAYOUT-01`;
- frame positioning/geometry redesign -> `FRAME-LAYOUT-01`;
- generic `ImageElement` header rendering discrepancy -> `GRAPHIC-PART-COMPAT-01`;
- exhaustive first/left/right header/footer APIs;
- page-number restart/continuation convenience APIs;
- PHP page-height/page-count calculation;
- renderer-neutral pagination abstraction;
- CV-specific pagination heuristics.

Deferral of page-style authoring is a sequencing decision, not rejection of the capability.

## 14. Completed implementation slices

### Slice 1 — Paragraph flow completion and characterization — COMPLETE

- added explicit missing paragraph-flow mappings;
- added focused semantic/materialization tests;
- characterized existing break/keep behavior;
- characterized preservation of authored `style:master-page-name` without pretending raw paragraph properties provide generated page-style assignment;
- introduced no new public page-style API.

### Slice 2 — Integration preservation and preflight — COMPLETE

- consolidated page/master relationship preservation coverage;
- aligned PAGE-FLOW-01D image characterization with the established `setImage()` path;
- completed Section/page-owned integration coverage;
- completed manual Writer regression;
- retained the required future capabilities and compatibility findings in project planning;
- completed the full PAGE-FLOW-01 preflight.

## 15. Completion decision

All accepted completion criteria are met:

- required paragraph flow semantics are expressible through the existing style architecture;
- authored page/master relationships survive supported document operations;
- page-owned content remains processable without a parallel subsystem;
- Sections preserve flow semantics without taking pagination ownership;
- no PHP pagination logic was introduced;
- no incorrect claim of generated page-style assignment support was introduced;
- required future page-style authoring remains explicitly retained in project planning;
- focused and full automated tests pass;
- manual LibreOffice regression is clean;
- the bounded `ImageElement` header discrepancy remains documented;
- documentation reflects actual behavior and deferrals.

**PAGE-FLOW-01 is COMPLETE / FINAL GO and is ready for final branch diff review and merge to `develop`.**

# STYLE-CONTEXT-01-D — Regression Closeout / Final GO

Status: **FINAL GO**  
Base: d245b5721511a1407adee61f10672105f99b1248  
Branch: architecture/style-context-01-final-closeout

## 1. Scope

This document closes STYLE-CONTEXT-01 after the A final audit and B
paragraph/text fallback characterization. It records regression evidence and
does not introduce production behavior, refactoring, API changes, or removal
of legacy/static compatibility.

No production code changed in STYLE-CONTEXT-01-D.

## 2. Architecture sequence

The completed sequence is:

    STYLE-CONTEXT-01-A  final ownership audit
            ↓
    STYLE-CONTEXT-01-B  paragraph/text fallback characterization
            ↓
    STYLE-CONTEXT-01-C  NOT REQUIRED
            ↓
    STYLE-CONTEXT-01-D  regression closeout / FINAL GO

This follows SR-06, SR-07, D5F, and D5G. Those milestones migrated modern
semantic ownership and bounded compatibility adoption; this closeout does not
reinterpret their retained compatibility surfaces.

## 3. Final semantic authority

The final architecture is:

    modern structured element
        ↓
    StyleRequirement
        ↓
    document-local OdtDocumentContext / StyleContext
        ↓
    document-local semantic materialization
        ↓
    ODT document/package

StyleContext is the document-local semantic authority for modern style
requirements. Covered families are paragraph, text, graphic, table,
table-column, table-row, and table-cell.

Process-global style registries remain compatibility surfaces, not document
semantic authority.

## 4. Modern lifecycle

The normal setElement() path collects semantic requirements before native DOM
materialization. Font and typed fill-image dependencies are prepared before
insertion. Physical image resources remain package-owned. Semantic definitions
are registered in the current StyleContext and materialized in their owning
document part.

The modern path does not require a process-global registry to produce complete
paragraph, text, graphic, table, column, row, or cell semantics.

## 5. Compatibility lifecycle

The legacy assign()/render() lifecycle remains distinct and supported.
Compatibility state includes StyleMapper static APIs, LegacyStyleRegistry,
broad direct StyleWriter defaults, public legacy OdtElement getters, HasStyles,
and protected OdtTemplate lifecycle hooks.

Current-document filtering in OdtTemplate prevents unrelated global entries
from entering normal document output. Direct StyleWriter callers retain their
historical broad behavior.

## 6. Paragraph/text characterization result

STYLE-CONTEXT-01-B proved:

* modern Paragraph definitions are document-local before materialization;
* modern styled text definitions are document-local before materialization;
* explicit paragraph fallback reads LegacyStyleRegistry through StyleMapper;
* explicit text fallback reads the separate StyleMapper text registry;
* local semantic definitions win same-name global definitions;
* authored document definitions win over local/global fallback candidates;
* unrelated documents do not serialize unreferenced global paragraph/text
  styles;
* LegacyStyleRegistry first-write-wins remains explicit legacy behavior.

The fallback is a well-defined compatibility boundary. It does not make
unrelated process-global registration modern document semantics.
STYLE-CONTEXT-01-C is not required.

## 7. Other style-family isolation

D5G regression evidence remains green for graphic/frame, image, fill-image,
table, and table-cell. Current-document references and semantic-owned
exclusions prevent unrelated entries from being adopted by normal OdtTemplate
finalization. Physical resources remain package-local.

## 8. LegacyStyleRegistry final role

LegacyStyleRegistry stores paragraph compatibility definitions with historical
first-write-wins behavior. It is exposed through StyleMapper and consumed by
direct compatibility and explicit paragraph reference fallback.

It is not modern semantic authority. Its process-global lifetime is retained
compatibility residue and is not a STYLE-CONTEXT-01 failure.

## 9. StyleMapper static API final role

Paragraph/text, frame, image, fill-image, table, and table-cell registration
and getter APIs remain public compatibility surfaces. They remain process
global and observable. Normal OdtTemplate consumption uses current-document
evidence. Modern StyleRequirements do not use these registries as semantic
authority.

No registry was removed, globally reset, or made document-local through
mutation.

## 10. StyleWriter final role

Direct StyleWriter defaults remain broad by design. A direct caller may
register static styles and call writeAllStyles() without an OdtTemplate
context.

OdtTemplate invokes the writer with narrower paragraph/text, frame, table, and
table-cell routing based on current-document evidence and semantic ownership.

This is an intentional boundary:

    direct StyleWriter call
        → broad public compatibility

    normal OdtTemplate finalization
        → current-document-filtered compatibility

## 11. HasStyles / legacy getter role

HasStyles remains an extension and compatibility surface. Its
getStyleDefinitions() data may overlap semantic requirements, but external
implementations and existing protected/public dispatch remain valid.

Legacy required paragraph/text, frame, image, fill-image, style-definition,
and image-asset getters remain public/protected compatibility facades.

## 12. Public/protected compatibility

Public compatibility remains covered for StyleMapper registration/getter APIs,
StyleWriter defaults, OdtElement legacy getters, assign(), render(), save(),
load(), and refresh().

Protected compatibility remains covered for fixBrokenVariables(),
setValuesInDom(), replacePlaceholderWithDom(), adjustBulletIndentation(),
injectImageStyles(), structured insertion callbacks, and related OdtTemplate
extension hooks.

No visibility or dispatch behavior changed.

## 13. Lifecycle persistence

| Operation | Document-local state | Global compatibility state |
|---|---|---|
| new template | new context/package | survives |
| setElement() | requirements added to current context | mirrors may be populated |
| render() | legacy DOM lifecycle | projections remain observable |
| save() | current-document filtering/materialization | registries remain global |
| repeated save() | definitions de-duplicated | state remains observable |
| refresh() | historical persist/reload behavior | registries not cleared |
| load() | document-local state reset | registries remain |
| multiple documents | contexts/resources isolated | shared registries filtered |

Existing D5F/D5G tests cover repeated render/save, load, refresh, mixed
lifecycle, and multi-document behavior.

## 14. Document isolation

| Family | Isolation status |
|---|---|
| paragraph | PASS — B A→B characterization |
| text | PASS — B A→B characterization |
| graphic/frame | PASS — D5G current-reference filtering |
| image | PASS — D5G current-reference filtering |
| fill-image | PASS — D5G current-reference filtering |
| table | PASS — D5G current-reference filtering |
| table-cell | PASS — D5G filtering and exclusions |

Static registries may contain entries from document A. That is retained
compatibility state; it is not leakage when document B output is unaffected.

## 15. Retained compatibility and residue

Intentionally retained:

* StyleMapper static APIs and registries;
* LegacyStyleRegistry;
* broad direct StyleWriter defaults;
* public legacy OdtElement getters;
* HasStyles and protected OdtTemplate facades;
* process-global compatibility observability;
* load() resetting document-local but not global state;
* historical refresh() semantics;
* legacy ImageElement resource omission;
* CircularImage missing-placeholder resource side effect.

These are explicit compatibility decisions, not hidden semantic authority.

## 16. Explicit non-goals

Outside STYLE-CONTEXT-01 are STYLE-API-02, public static API deprecation,
getter/HasStyles redesign, generic style cleanup, the ImageElement resource
bug, CircularImage missing-placeholder behavior, FRAME-LAYOUT-01/02,
IMAGE-LAYOUT-01, table-layout redesign, template authoring/format
preservation, assign/render redesign, refresh redesign, and new generic
registry/lifecycle abstractions.

## 17. Exit criteria matrix

| Criterion | Result | Evidence |
|---|---|---|
| Modern styles use document-local StyleContext | PASS | SR-06/SR-07/D5F suites |
| No modern requirement needs global semantic authority | PASS | A audit + B tests |
| Modern paragraph/text path is global-independent | PASS | B pure modern tests |
| Paragraph/text fallback characterized | PASS | B, 11 tests / 74 assertions |
| Local/global collisions preserve local semantics | PASS | B collision tests |
| Authored styles retain priority | PASS | StyleContext tests + B |
| Unrelated global state does not leak | PASS | B and D5G isolation |
| Graphic/image/fill/table/cell isolation green | PASS | D5G suites |
| LegacyStyleRegistry role documented | RETAINED COMPATIBILITY | A/B/D |
| StyleMapper APIs remain facades | RETAINED COMPATIBILITY | D5G/API tests |
| StyleWriter broad defaults remain | RETAINED COMPATIBILITY | direct writer tests |
| OdtTemplate path is filtered | PASS | D5G adoption tests |
| Public legacy getters remain | RETAINED COMPATIBILITY | API/structured tests |
| Protected compatibility remains | PASS | compatibility tests |
| Repeated lifecycle covered | PASS | D5F/D5G suites |
| load()/refresh() compatible | PASS | lifecycle characterization |
| No hidden behavior correction | PASS | no production changes |
| STYLE-API-02/Future Work separated | RETAINED RESIDUE | roadmap/Future handoff |
| C narrowing unnecessary | PASS | B decision |
| Full preflight green | PASS | validation below |

No criterion is FAIL.

## 18. Regression validation

The focused regression set includes StyleContext, StyleRequirement,
StyleMapper, StyleWriter, D5F, D5G, protected compatibility, structured
elements, graphics, tables, isolation, repeated lifecycle, and
PublicSampleSmokeTest.

Full suite:

* 596 tests;
* 3754 assertions;
* 1 existing warning;
* 7 PHPUnit deprecations;
* no failures.

No production or rendering code changed, so no new LibreOffice run is
required. Existing SR-06/SR-07 visual evidence remains applicable.

## 19. STYLE-API-02 handoff

STYLE-API-02 may independently address public StyleMapper consistency,
deprecation/facade strategy for static registries, legacy getter naming,
HasStyles consistency, direct StyleWriter defaults, and style naming/method
consistency.

None is required to establish document-local semantic authority.

## 20. Final verdict

StyleContext is the document-local semantic authority for modern style
requirements. Process-global style registries remain compatibility surfaces,
not document semantic authority.

**STYLE-CONTEXT-01-D COMPLETE**  
**STYLE-CONTEXT-01 FINAL GO**

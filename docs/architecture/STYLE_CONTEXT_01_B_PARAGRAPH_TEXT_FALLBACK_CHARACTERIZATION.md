# STYLE-CONTEXT-01-B — Paragraph/Text Fallback Characterization

Status: **CHARACTERIZATION COMPLETE — NO PRODUCTION CHANGE**  
Base: 3d7d90e050702fb5529dbc74ba88e4061cec6ccd  
Branch: architecture/style-context-01-final-closeout

## 1. Scope

This slice characterizes the remaining paragraph/text fallback between the
document-local StyleContext and process-global legacy state. No production
code, API, registry, writer, or lifecycle behavior was changed.

The characterization suite is
tests/Integration/StyleContextParagraphTextFallbackCharacterizationTest.php.
It contains 11 tests and 74 assertions.

## 2. Exact paragraph fallback path

Paragraph::getOwnStyleRequirements() produces a common/styles.xml paragraph
definition when options are present, or a paragraph reference when only a
style name is present. StyleRequirementCollector::collectSemantic() yields
that requirement before DOM materialization, and OdtTemplate::setElement()
registers it directly in the current StyleContext.

For an unresolved paragraph reference, StyleContext::resolveReference() calls
StyleMapper::getParagraphStyles(). That facade reads the process-global
LegacyStyleRegistry::paragraphStyles() map. If the name exists, resolution is
legacy. materializationRequirements() maps the raw options with
StyleMapper::mapParagraphStyle() into a common/styles.xml definition with
parent Standard.

The fallback does not copy the raw entry into StyleContext::paragraphStyles().
It produces a materialization requirement only when a current reference
requires it.

Resolution order is:

1. authored current-document style in styles.xml/content.xml;
2. current document-local semantic definition;
3. global paragraph compatibility registry;
4. unresolved.

## 3. Exact text fallback path

Text is not stored in LegacyStyleRegistry. Modern styled paragraph text
produces a text-family StyleRequirement before native rendering and
OdtTemplate::setElement() registers it directly in the document-local
StyleContext.

For an unresolved text reference, StyleContext::resolveReference() calls
StyleMapper::getTextStyles(), which reads StyleMapper's separate static
registered-text map. legacyRequirement() maps it with
StyleMapper::mapTextStyleOptions() into a common/styles.xml text definition
with parent Standard. StyleMapper::setTextStyle() is the explicit-name
compatibility entry point.

Paragraph and text therefore have parallel StyleContext fallback behavior but
different global registries and registration APIs.

## 4. LegacyStyleRegistry role

LegacyStyleRegistry stores paragraph styles only and retains historical
first-write-wins behavior. StyleMapper::registerParagraphStyle() delegates to
it, and StyleMapper::getParagraphStyles() exposes the map.

Consumers are paragraph reference fallback, direct StyleWriter paragraph
compatibility, and public/legacy callers. It is not the source of modern
paragraph definitions that already arrive as semantic StyleRequirements.

Classification: PUBLIC COMPATIBILITY FACADE plus INTERNAL COMPATIBILITY
TRANSPORT. Its lifetime and first-write-wins policy remain observable.

## 5. Pure modern paragraph result

A Paragraph with explicit options inserted through setElement() produced a
paragraph definition in the current StyleContext before materialization. The
definition was written to styles.xml with the expected mapped property.
No prior global paragraph registration was needed.

Result: **PASS — document-local semantic ownership is complete.**

## 6. Pure modern text result

A styled text span inserted through setElement() produced a text-family
semantic definition before native rendering. The definition was owned by the
current StyleContext and written with the expected text property. No static
text registry entry was required.

Result: **PASS — document-local semantic ownership is complete.**

## 7. Explicit legacy paragraph fallback

The test registered a paragraph style only through
StyleMapper::registerParagraphStyle() and then registered a reference in a
fresh StyleContext.

Observed:

* local StyleContext::paragraphStyles() was initially empty;
* resolution was legacy;
* one common/styles.xml paragraph requirement was produced;
* mapped fo:margin-left was preserved;
* the OdtTemplate structured path serialized the referenced style.

The global definition is consulted only because a current reference requires
it. An unrelated entry is not imported.

Result: **COMPATIBILITY — deterministic retained fallback.**

## 8. Explicit legacy text fallback

The test registered an explicitly named text style through
StyleMapper::setTextStyle() and registered a text reference in a fresh
StyleContext.

Observed:

* local StyleContext::textStyles() was initially empty;
* resolution was legacy;
* the definition was mapped to style:text-properties;
* the paragraph registry did not contain the text style.

Result: **COMPATIBILITY — separate text registry, not LegacyStyleRegistry.**

## 9. Paragraph document isolation

Document A registered and referenced a global paragraph style. Document B was
created in the same PHP process but did not reference it.

Observed:

* A serialized the style;
* B did not serialize the style;
* B's document-local semantic definitions did not contain it;
* the global registry still contained the entry.

Result: **ISOLATED.** Global observability is not document-output leakage.

## 10. Text document isolation

The same boundary was exercised for the separate StyleMapper text registry.
An explicit text fallback was available to a reference-bearing context, but an
unrelated document-local context did not acquire the style merely because the
process-global registry contained it.

Result: **ISOLATED for the characterized StyleContext path.** Direct broad
StyleWriter behavior remains a separate public compatibility contract.

## 11. Paragraph name collision

Global definition A was registered first. A modern Paragraph with the same
name and local definition B was inserted through setElement().

Observed:

* StyleContext held the semantic local definition B;
* serialized output contained B's property;
* global A did not replace B;
* global first-write-wins did not override the semantic definition.

Result: **LOCAL.**

## 12. Text name collision

Global text definition A was registered through the text registry. A local
semantic text definition B with the same name was registered in a fresh
StyleContext, followed by a reference.

Observed: resolution was document-local and the materialization requirement
retained B's property.

Result: **LOCAL.**

## 13. Two-document modern isolation

Two independent OdtTemplate instances used the same paragraph style name with
different modern definitions and no legacy registration as authority.

Observed:

* document A serialized only its definition;
* document B serialized only its definition;
* A's local definition did not become B's definition;
* no process-global first-write-wins behavior was involved.

The same local-over-global rule was characterized for text at StyleContext
level.

Result: **ISOLATED.**

## 14. Authored style precedence

Existing StyleContextTest::testExistingDocumentCandidateWinsOverLowerPriorityCandidates()
proves authored paragraph document definitions take precedence over global
fallback and lower-priority local candidates.

This slice additionally characterized authored paragraph and text references
against global registries:

* paragraph authored candidate resolved as document;
* text authored candidate resolved as document;
* neither was added to materializationRequirements() as a fabricated legacy
  definition.

Result: **AUTHORED.**

## 15. First-write-wins consequences

Existing StyleMapperCompatibilityTest proves that registering paragraph style
X as A and then B leaves A in getParagraphStyles()[X]. This remains observable
through the public facade, direct StyleWriter, and paragraph fallback when a
reference requires X.

It does not affect a modern local StyleRequirement with the same identity:
local semantic definitions resolve before global fallback.

## 16. Repeated lifecycle

The new characterization covered repeated save for a legacy paragraph
reference:

* first and second saves retained one definition identity;
* no duplicate style definition was created;
* load() reset document-local StyleContext state;
* the process-global paragraph registry remained available after load().

Existing D5F/D5G suites cover repeated render/save and broader lifecycle.

## 17. load()/refresh() boundary

load() resets the current document package/context while retaining static
registries. The new repeated-lifecycle test confirms this boundary for a
paragraph fallback.

refresh() was not changed or redesigned. Its existing persist/reload
semantics and D5G characterization remain authoritative.

## 18. Direct StyleWriter compatibility boundary

Existing D5G and StyleMapper tests prove that
StyleWriter::writeAllStyles($dom) retains broad default compatibility.

The new test confirms the separation:

* direct StyleWriter materialized a registered global paragraph style;
* normal OdtTemplate save with no current reference did not serialize it.

Result: **DIRECT COMPATIBILITY RETAINED; normal document semantics isolated.**

## 19. Characterization gaps

No gap remains for the decision between a modern local definition and the
paragraph/text global fallback in the tested cases.

Retained lower-priority gaps are:

* exhaustive external subclass overrides of every legacy paragraph/text getter;
* specialized font helper lifetime beyond the existing text/font suite;
* the secondary static table-cell field identified by STYLE-CONTEXT-01-A;
* direct StyleWriter behavior, intentionally broad rather than document-local.

These are not paragraph/text fallback failures.

## 20. Decision for STYLE-CONTEXT-01-C

The evidence satisfies the no-narrowing conditions:

* modern paragraph and text semantics are complete in document-local
  StyleContext;
* global fallback activates only for an explicit unresolved reference;
* unrelated registrations do not materialize in another OdtTemplate document;
* local definitions win same-name global collisions;
* authored definitions win over global fallback;
* broad direct StyleWriter behavior remains separate compatibility.

Therefore:

**STYLE-CONTEXT-01-C NOT REQUIRED**

No production narrowing is justified by this evidence. The fallback can be
retained as an explicit, documented compatibility facade.

## 21. Updated exit criteria

The paragraph/text portion is ready for regression closeout when:

1. semantic definitions are registered before materialization;
2. unresolved references may use the explicit legacy fallback;
3. authored and local definitions have precedence;
4. unrelated global state does not enter normal OdtTemplate output;
5. first-write-wins remains only public legacy behavior;
6. direct StyleWriter defaults remain separate from OdtTemplate finalization;
7. public/protected getters and static APIs remain available.

## Decision matrix

| Case | Paragraph | Text | Result |
|---|---|---|---|
| pure modern | local definition before materialization | local definition before materialization | PASS |
| explicit legacy fallback | LegacyStyleRegistry via StyleMapper | separate StyleMapper text registry | COMPATIBILITY |
| unrelated second document | not serialized | not serialized in characterized context path | ISOLATED |
| global/local same-name collision | local wins | local wins | LOCAL |
| modern A/B same name, different definitions | independent local output | independent at StyleContext level | ISOLATED |
| authored template precedence | authored wins | authored wins | AUTHORED |
| repeated lifecycle | one definition; load resets local state | existing suites cover text path | STABLE |
| load boundary | local reset, global retained | same StyleContext rule | STABLE |
| direct StyleWriter | broad global compatibility | broad global compatibility | COMPATIBILITY |

## Final status

**STYLE-CONTEXT-01-B COMPLETE — DIRECT CLOSEOUT POSSIBLE**

No production code was changed.

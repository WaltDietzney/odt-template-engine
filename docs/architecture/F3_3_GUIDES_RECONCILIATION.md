# F3.3 — Guides Reconciliation

**Status:** COMPLETE — bounded FINALIZATION-01 F3.3 documentation slice

## Objective

Reconcile the user-facing guide layer with the completed 1.0 API and the F3
information architecture without introducing new runtime architecture.

The guides must teach one current product model rather than the chronology of
the architecture work that produced it.

## Evidence basis

The reconciliation compared the user-facing guide families with:

- the completed Practical API Reference;
- the canonical L/C/B/S sample taxonomy and executable samples;
- the F3.1 information-architecture contract;
- the F3.2 Recommended first-document lifecycle;
- the accepted Writer-native, TemplateContract, Mapping, Concrete Preflight,
  Automation, structured-content, style, metadata, and page-layout semantics.

No runtime/API contradiction requiring an implementation blocker was found.

## Reconciled learning model

The guide layer now consistently uses the three complementary ownership models:

1. **Simple Template Processing** — Writer owns surrounding structure; PHP
   supplies visible values and lightweight template logic.
2. **Structured ODT Construction** — PHP owns the generated ODT subtree.
3. **Writer-native Document Model** — Writer owns named native structure; PHP
   addresses it through bounded type-specific operations.

The decision remains per piece of document structure. The models may be mixed
within one document.

Mapping & Automation is an optional integration workflow over inspected
template meaning. It is not a fourth authoring model.

## Completed guide slices

### Structured content and styles

Existing correct RichText, Paragraph, List, Table, Image, and style guidance was
preserved. Stale migration-era sample labels were removed and canonical L/C/B/S
sample links were reconciled.

Current limitation sections were not removed merely because they describe
unfinished areas. Where they still state bounded 1.0 behavior, they remain part
of the product documentation.

### Writer-native, inspection, and authoring

Writer-native guidance now reflects the final type-specific API:

- Writer tables support bounded `TableTarget::populate()`;
- `FrameTarget` remains identity/inspection-only;
- mapped frame image replacement is a separate validated native-object action;
- L09–L12 are canonical learning samples, not historical replacements;
- `RECOGNIZED` is an inspection classification, not a statement that
  declarative execution is unavailable;
- direct declarative execution and mapped Automation are distinct paths;
- `instantiateMany()` is not an unrestricted application-data mapper.

### Frames & Text Boxes

A dedicated learning guide was added around the ownership question:

> Who owns this frame — Writer or PHP?

It separates PHP-owned `DrawTextBox` / `ImageElement` frame construction from
Writer-owned named-frame identity and from the mapped `replace-image` action.

### Mapping, Preflight & Automation

A dedicated learning guide now explains the complete optional workflow:

```text
Writer template
→ inspectTemplate()
→ TemplateContract
→ MappingDefinition + application data
→ Concrete Preflight
→ READY
→ automate()
→ save()
```

It records the non-mutating boundaries, three mapping families, concrete
validation role, common invocation-wide atomicity/rollback, execution order,
single-success Working-Document lifecycle, explicit save, and distinction from
`executeDeclarative()` and classic `render()`.

The earlier map-and-render convenience idea is retained as application-level
motivation, not introduced as a new 1.0 API.

### Final legacy sweep

The final sweep removed or reconciled:

- old “Level 1/2/3” learning labels in favor of the three accepted model names;
- public learning references to numbered migration-era Samples 01/03/05/21/25;
- contradictory “historical L08/L10/L11” language for canonical Learn samples;
- stale F2.4/F2.5/F2.6 future-tense text in the completed API Reference index;
- migration-only HTML-import language;
- internal product/final-review documents used as user-facing Named Sections
  next steps;
- misleading S01b/C04 headings that implied each document used only one
  ownership mechanism.

Compatibility behavior that remains part of 1.0 was preserved and identified
as such rather than silently rewritten as Recommended API.

## Navigation additions

The normal learning navigation now includes:

- **Structured ODT Construction → Frames & Text Boxes**
- **Writer-native Document Model → Mapping, Preflight & Automation**

The detailed API Reference remains the exact contract behind those guides.

## Scope boundaries

This slice does not:

- change PHP runtime behavior;
- introduce a new API or convenience facade;
- redesign compatibility behavior;
- turn Mapping & Automation into a mandatory lifecycle;
- rewrite valid limitation sections merely for presentation;
- perform the root README reconciliation reserved for F3.4.

## F3.3 completion gate

F3.3 is complete when:

- the guide families tell the same 1.0 ownership story;
- canonical samples are treated as canonical rather than historical migration
  artifacts;
- Writer-native operation descriptions agree with the final API Reference;
- Frames/Text Boxes have a normal learning guide;
- Mapping/Preflight/Automation has an integrated learning guide;
- stale milestone/future-tense API-reference entry text is removed;
- compatibility behavior remains explicitly classified;
- documentation validation passes.

Subject to normal documentation/CI validation, the bounded F3.3 reconciliation
is complete.

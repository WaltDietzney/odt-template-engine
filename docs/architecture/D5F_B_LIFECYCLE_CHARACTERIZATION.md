# D5F-B — Lifecycle / Materialization Characterization

Status: **CHARACTERIZATION EVIDENCE — NO LIFECYCLE CHANGE AUTHORIZED**

Base: `architecture/d5f-lifecycle-audit` at `c72e033`

## 1. Purpose

D5F-B characterizes the normal `OdtTemplate::setElement()` lifecycle around
`OdtElement::toDomNode()`. It compares semantic requirements, legacy
compatibility data, physical resource requirements, and fill-image
dependencies immediately before and after native DOM materialization.

This document records observed behavior. It is not a Change Contract and does
not authorize removal of compatibility paths or introduction of a lifecycle
abstraction.

The characterization tests are in
`tests/Integration/D5FLifecycleCharacterizationTest.php`.

## 2. Lifecycle under observation

The current normal insertion path remains conceptually:

```text
collectSemantic()
  -> semantic style/dependency registration and materialization
collect()                  legacy compatibility projection
StructuredResourceCollector
  -> package resource preparation
StructuredElementMaterializer::insert()
  -> element->toDomNode()
collect()                  legacy post-materialization projection
```

D5F-B does not change this ordering. It only compares the observable
projections around the native materialization call.

## 3. Producer matrix

| Producer / concern | Semantic pre/post | Legacy pre/post | Resource pre/post | Post phase genuinely required? |
| --- | --- | --- | --- | --- |
| Paragraph | Identical | Identical | Identical | No evidence |
| RichText with styled Paragraph | Identical through owned children | Identical | Identical | No evidence |
| ListElement | Identical through owned children | Identical | Identical | No evidence |
| ImageElement | Identical empty output; ImageElement has no semantic graphic producer | **Changed**: derived `style:wrap`, `style:horizontal-pos`, `style:horizontal-rel`, and sometimes vertical values are written into `imageOptions` | Identical | **Not proven**; current mutation is deterministic render/compatibility state |
| CircularImageElement | Identical graphic requirement | **Changed**: legacy graphic and fill-image arrays become available after `toDomNode()` through `circularStyle*` and `$fillImageName` state | Identical | No; typed semantic graphic/fill-image/resource projections are already complete |
| DrawTextBox | Identical graphic requirement | Identical for the characterized options | Identical | No evidence |
| RichTable with styled cell, explicit columns, row style | Identical table/column/row/cell requirements | Identical collector output; direct DOM compatibility work still occurs | Identical | No evidence |
| RichTable with ratio columns | Identical semantic relative-column requirements | Identical collector output | Identical | No evidence |

The table rows used in characterization include a styled `RichTableCell`,
explicit column widths, and `min-row-height`; the ratio case uses
`setColumnWidthRatios([2, 1, 1])`.

## 4. ImageElement state transition

`ImageElement` is the only producer with a visible pre/post mutation in the
normal image path.

Before `toDomNode()`:

- constructor/setStyle maps the public options into `imageOptions`;
- the generated style name is already present and stable;
- legacy `getOwnImageStyleRequirements()` is available;
- no semantic graphic requirement is produced;
- the physical image asset is already discoverable.

During `toDomNode()` the current implementation derives frame attributes from
the existing raw/mapped options. The following values are then copied back to
`imageOptions` for legacy style synchronization:

- `style:wrap`;
- `style:horizontal-pos`;
- `style:horizontal-rel`;
- `style:vertical-pos` and `style:vertical-rel` when a vertical position is
  present.

Characterized mappings include:

```text
align=left      -> wrap=right,  horizontal-pos=left,  horizontal-rel=paragraph
align=right     -> wrap=left,   horizontal-pos=right, horizontal-rel=paragraph
align=center    -> wrap=none,   horizontal-pos=center, horizontal-rel=paragraph
align=absolute  -> wrap=none,   horizontal-pos=from-left, horizontal-rel=page-content
```

Explicit wrap, horizontal placement, vertical placement, and anchor options
were also characterized. The style name remains identical before and after
materialization, while the legacy definition array changes because it exposes
the post-render synchronized options. Repeated `toDomNode()` calls stabilize
at that post-state.

The public `ImageElement` mapper does not preserve arbitrary `svg:x`/`svg:y`
input in `imageOptions`, so those options do not produce frame coordinates in
the current public path. An explicit `style-name` input is likewise not an
independent public identity: `setStyle()` generates the mapped style name.
These are observed compatibility facts, not corrections.

All alignment values above are deterministically derivable from the existing
input. The evidence therefore does not show an intrinsically
post-materialization semantic requirement.

## 5. Semantic collector findings

`StyleRequirementCollector::collectSemantic()` produced identical snapshots
before and after `toDomNode()` for:

- Paragraph and nested styled RichText;
- ListElement;
- CircularImageElement graphic requirements;
- DrawTextBox graphic requirements;
- RichTable table/column/row/cell requirements, including explicit and ratio
  columns.

For ImageElement, the semantic collector is empty both before and after
materialization. This is the current absence of an ImageElement semantic
graphic producer, not evidence that the legacy image family is semantically
unimportant.

No semantic requirement changed in the characterization suite. A change here
would be a D5F blocker requiring separate architecture review.

## 6. Legacy collector findings

`StyleRequirementCollector::collect()` is stable for Paragraph, RichText,
ListElement, DrawTextBox, and both table cases.

ImageElement changes only because its legacy image definition reads the
mutated `imageOptions` after rendering. CircularImageElement changes because
its legacy graphic and fill-image compatibility arrays are intentionally empty
until `toDomNode()` assigns the generated compatibility state. These are
legacy compatibility observations; they are not proof that a generic semantic
post-discovery phase is required.

In particular, CircularImageElement already exposes before rendering:

- a semantic graphic requirement;
- a semantic fill-image dependency;
- a physical image asset.

Its post-state is needed by the historical compatibility getters, not by the
semantic document-local pipeline.

## 7. Resources and fill-image dependencies

`StructuredResourceCollector::collect()` returned identical physical asset
requirements before and after materialization for direct and nested images.
The nested RichText/Paragraph image case remains complete before DOM creation.

`FillImageRequirementCollector::collect()` returned the same typed
`FillImageRequirement` for CircularImageElement before and after rendering:

```text
part: styles.xml
name: cv_photo_WaltDietzney
href: Pictures/WaltDietzney.png
```

This confirms that semantic fill-image dependency discovery is already
pre-materialization complete. The later legacy `$fillImageName` assignment is
separate compatibility state.

## 8. Active setElement lifecycle

The focused lifecycle test inserts two different ImageElement instances into
independent templates, saves both, and saves the first template twice. It
observes:

- image content and the physical `Pictures/WaltDietzney.png` resource remain
  present;
- each saved document contains one image node for its inserted element;
- repeated save does not add another image node;
- document B references its own image style and does not reference document
  A's style.

This test preserves current normal-path behavior without changing
`setElement()`, `save()`, or `refresh()` implementation.

## 9. Answers to the D5F audit questions

### A. Is there a proven semantic producer requiring post-materialization discovery?

No. The characterization found no semantic producer whose requirement output
changes after native DOM materialization. ImageElement has no semantic graphic
producer and its post-state is deterministic render/legacy state. Circular
image semantic graphic, fill-image, and resource projections are complete
before rendering.

### B. Which legacy data changes only because of `toDomNode()`?

- ImageElement writes derived wrap and horizontal/vertical placement values
  into `$imageOptions`.
- CircularImageElement assigns `$fillImageName`, `$circularStyleName`, and
  `$circularStyleOptions`, which populate its legacy compatibility getters.

These mutations are observable and must remain characterized until a later
compatibility decision explicitly narrows them.

### C. Can D5F continue planning an authoritative pre-materialization path?

Yes. The evidence supports a single authoritative pre-materialization path
for semantic style requirements, fill-image declarations, and physical
resources. A generic semantic post-materialization discovery pass is not
justified by current producer behavior.

The remaining ImageElement question is how to preserve deterministic derived
render state and legacy synchronization without treating it as semantic
post-discovery. That can be addressed by a narrowly scoped future contract,
not by a speculative lifecycle framework.

### D. Which compatibility work belongs explicitly to D5G?

D5G remains responsible for decisions about:

- the legacy `StyleRequirementCollector::collect()` projection;
- StyleMapper/StyleWriter registration and finalization bridges;
- protected compatibility hooks and public legacy getters;
- legacy `assign()` / `render()` behavior;
- save/finalization compatibility state;
- legacy graphic carriers and old frame/image/fill-image array APIs;
- the post-render ImageElement synchronization and CircularImageElement
  mutation contract.

D5F-B does not remove or refactor any of these paths.

## 10. Final characterization status

The focused D5F-B evidence supports proceeding to a D5F Change Contract
review. It does **not** authorize implementation, lifecycle abstraction, or
compatibility cleanup. Production code remains unchanged by this slice.

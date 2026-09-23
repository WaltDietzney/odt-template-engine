# TEMPLATE-AUTHORING-01F — S02 Professional Invoice Change Contract

Status: APPROVED DESIGN CONTRACT / implementation not started

## Purpose

S02 is the professional invoice showcase for TEMPLATE-AUTHORING-01F.

It must demonstrate that a credible data-driven business document can be
authored primarily in LibreOffice Writer and rendered with the existing
ODT Template Engine capabilities without introducing invoice-specific engine
features.

S02 complements, but does not replace, B01 Invoice Template Builder:

```text
B01 — Invoice Template Builder
    PHP constructs a substantial editable invoice/template candidate
        ↓
        visual and semantic authoring reference
        ↓
S02 — Professional Invoice
    Writer owns stable design and document structure
    Engine supplies data and bounded dynamic behavior
```

The architectural benchmark is not merely whether PHP can produce an invoice.
The benchmark is whether a professional Writer-authored template remains the
primary design artifact while the engine supplies the dynamic document
semantics it already supports.

## Source evidence

This contract is based on the current `develop` architecture and on the
accepted B01 findings in
`TEMPLATE_AUTHORING_01F_INVOICE_PROTOTYPE_FINDINGS.md`.

Relevant existing evidence includes:

- B01's accepted visual/composition model;
- L03/classic repeating-content behavior;
- L06 image replacement/insertion distinctions;
- L10 Writer User Field binding;
- C04 declarative Writer Section controls;
- C05 inspection/mapping/automation architecture;
- the S01b rule that stable professional layout belongs in Writer;
- legacy Sample 04 metadata, Sample 10 integrated business-letter behavior,
  and Sample 14 advanced tab geometry, all of which currently point wholly or
  partly toward S02 in the sample registry.

This contract does not silently promote behavior from those legacy samples
into a new guarantee. S02 may use only behavior already supported and
characterized on `develop`.

## Governing ownership rule

S02 follows the template-authoring principle:

> Stable visual design and stable document structure belong to LibreOffice
> Writer. PHP owns application data and the dynamic behavior required to bind,
> repeat, conditionally retain/remove, or replace supported template content.

The final S02 template must therefore not recreate B01's stable visual design
through `RichText`, `Paragraph`, `RichTable`, or application-defined style
options merely because B01 proved that this is possible.

B01 remains the Builder example for programmatic construction. S02 is the
Showcase example for template-driven composition.

## Required showcase character

The final document should preserve the accepted B01 design direction rather
than mechanically copy its generated XML:

- clear brand/logo region at the upper left;
- sender/contact information at the upper right;
- recipient/BILL TO region;
- prominent INVOICE title;
- visually distinct invoice metadata block;
- restrained orange/gray/black hierarchy;
- clear item heading and aligned item records;
- payment information and financial summary in the lower business-content
  region;
- account-manager/signature closing;
- thank-you/terms closing region;
- professional whitespace and editable native Writer structure.

LibreOffice may refine exact spacing, typography, table geometry, and other
visual details during template authoring. B01 is the design reference, not a
requirement for byte-identical structure.

## Region ownership

### Brand and sender region

Writer owns:

- page position and geometry;
- logo position;
- brand typography;
- contact labels and visual treatment;
- stable sender text where it is genuinely template-owned.

Where the logo is intended to be application data rather than template
branding, S02 may use the already supported template image-replacement path.
The choice must be explicit in the sample and must not introduce a new named
element API.

### Recipient / BILL TO region

Writer owns the region, styles, line geometry, and labels.

Recipient values are dynamic. The exact binding mechanism must follow the
semantic role of each value:

- document-global scalar values may use ordinary scalar template binding;
- Writer User Fields may be used where a value is genuinely document-global
  and native field semantics improve the authoring model.

S02 must not use User Fields merely to maximize feature count.

### Invoice identity and date

Invoice number and invoice date are strong candidates for existing Writer
string User Fields because they are document-global/ROOT-scoped values.

The implementation slice must verify the actual authored field declarations
and references in the final template. If User Fields are used, their current
bounded semantics remain authoritative:

- string values only;
- document-global/ROOT scope;
- declaration is authoritative;
- references may require Writer reevaluation for cached display text.

No item-scoped value may be modeled as a User Field.

### Salutation

S02 must demonstrate existing declarative Writer Section conditions for a
conditional salutation.

The authored template must use only the supported Phase-D condition grammar
and controls already established on `develop`, including `#if` and/or
`#ifnot` as appropriate.

S02 must not invent a native Section `#else` construct that has not been
established by the current architecture. If mutually exclusive variants are
needed, they must be expressed with supported conditions.

The Section names and conditions must remain understandable in LibreOffice as
template-authoring constructs.

### Introductory text

Writer owns paragraph style and layout.

Dynamic text may use ordinary scalar placeholders. Static prose should remain
ordinary Writer text rather than being generated from PHP.

### Invoice item heading

The item heading is stable Writer-owned content and remains outside the
repeated unit.

The accepted semantic columns are:

```text
ITEM DESCRIPTION        PRICE        QTY        TOTAL
```

The final visual wording may be refined during authoring, but the heading must
not be repeated for every item.

### Invoice items

S02 uses the classic template repeating-content mechanism for collection `items`:

```text
{{#foreach:items}}
...
{{#endforeach}}
```

This choice is deliberate. S02 does not use native Writer `#foreach` Sections
for invoice item repetition. B01 already established the classic
paragraph/sibling-node repetition path as the appropriate existing mechanism
for this item layout, while the salutation uses native Writer Sections for
conditional structure.

This distinction is architecture evidence: Simple Template Processing and
Structured Template Processing coexist and are selected according to the
semantics of the authored content.

Required item fields:

```text
name
description
price
quantity
line_total
```

The initial S02 draft described the intended semantic scope as:

```text
items[].total   line/item total
ROOT.total      document-level grand total
```

However, a characterization of the current classic rendering path establishes
that this same-name arrangement is not executable through the existing public
classic lifecycle. `render()` applies globally assigned scalar values to the
document before the classic foreach block is cloned. Consequently, a ROOT
assignment for `total` reaches the item prototype before row-local replacement,
and item-local `total` values cannot shadow it.

This is an evidence-driven amendment to the approved S02 contract. The
compatibility-safe S02 authoring rule is therefore:

```text
items[].line_total   line/item total
ROOT.total           document-level grand total
```

The current S02 template/sample must adopt that distinct item name in the
following implementation slice. This characterization slice does not change
the existing template or sample. Future scoped placeholder work is tracked
separately; changing render order is not assumed to be a sufficient or safe
solution without broader compatibility characterization.

The final S02 item layout uses Writer-authored paragraphs and native tab stops,
not repeated spaces.

B01 established the accepted tab geometry as a useful starting point:

- 10.2 cm right aligned — price;
- 13.1 cm centered — quantity;
- 17.0 cm right aligned — line total.

These exact measurements are design evidence, not immutable engine semantics.
They may be adjusted in LibreOffice if the final template requires it.

### Native table-row repetition is not part of S02

S02 must not add a new native table-row repetition feature.

The current classic repetition path is paragraph/sibling-node based and is
already characterized. The project does not yet claim stable canonical native
table-row repetition semantics.

A real Writer table may still be used for genuinely two-dimensional stable
layout regions where no row repetition is required. It must not be used to
simulate page positioning merely because tables make geometry convenient.

Native table-row repeat remains a required post-1.0/next-development-round
capability identified by B01, with exact semantics/API still undecided.

### Payment information

Payment information is business content, not a page footer merely because it
appears low on the page.

Writer owns its stable labels, typography, and geometry. Dynamic payment
values use ordinary scalar or document-global binding according to their
semantics.

### Financial summary

S02 binds externally supplied values for:

```text
subtotal
tax
total
```

The engine performs no invoice arithmetic.

The showcase must not calculate subtotal, tax, VAT, line totals, or grand
total and must not imply that the engine validates accounting correctness.

Any displayed tax label/rate is representative sample content. The bound
`tax` value is application input.

### Signature / account-manager closing

Writer owns stable layout, labels, and typography. Application-specific names
or role values may use scalar binding.

This region remains business content even when visually aligned with the
financial summary.

### Thank-you / terms closing

Writer owns the closing treatment and static terms text.

If a terms value is intentionally dynamic, it may use scalar binding, but S02
does not require a rich-text terms API or generated footer block.

The implementation must preserve the distinction established by B01:

> Content near the bottom of the page is not automatically document-footer
> content.

A true Writer footer should be used only if the content semantically belongs
to the page/master-page footer.

## Binding model

S02 should use the simplest existing mechanism that matches each semantic
role.

Preferred decision order:

1. static stable content stays ordinary Writer content;
2. document-global native field semantics may use Writer User Fields;
3. ordinary dynamic scalar values use scalar template binding;
4. repeated line-item data uses the existing `items` collection/repetition;
5. conditional salutation uses declarative Writer Sections;
6. image replacement is used only where the image is genuinely application
   data;
7. document metadata uses the existing metadata API only where it contributes
   meaningfully to the business-document showcase.

S02 is a composition benchmark, not a requirement to exercise every available
engine feature.

## Mapping and automation

C05 proves the optional mapping/preflight/automation layer. S02 may use that
layer if the final application-shaped sample data benefits from explicit
mapping, but mapping is not required merely to make the showcase appear more
advanced.

If S02 uses mapping:

- the mapping must solve a real distinction between application data and
  template dependencies;
- concrete preflight must complete before mutation;
- the sample must use the established Phase-E architecture rather than a
  showcase-specific mapper.

If direct template-shaped data is clearer for this showcase, direct existing
APIs remain valid and first-class.

The implementation decision must be documented rather than silently choosing
one path.

## Document metadata

Legacy Sample 04 and Sample 10 provide historical metadata/business-document
evidence and currently point toward S02.

S02 should include meaningful document metadata if it can do so without
obscuring the authoring lesson. Suitable metadata may include title, subject,
creator, language, or business-document description through the existing
metadata API.

Metadata is not a substitute for visible invoice values and must not be used
as a hidden data store for invoice business semantics.

## Explicit non-goals

S02 must not introduce or imply:

- invoice-specific production APIs;
- invoice calculation/accounting semantics;
- native table-row repetition;
- a new programmatic Writer Section/`SectionElement` API;
- a new style API;
- a new page-layout or pagination API;
- a new semantic named-element API;
- a new image identity model;
- a new field family or non-string User Field type;
- a new conditional grammar;
- a new mapping architecture;
- programmatic reconstruction of the stable invoice design;
- redesign of B01;
- cleanup or deletion of unrelated legacy samples;
- retirement of migration records merely because S02 overlaps their lessons.

Any defect in an existing mechanism discovered while implementing S02 must be
characterized first. A required engine fix becomes a separate bounded slice
rather than being hidden inside visual showcase work.

## Planned canonical artifacts

Subject to the normal implementation slice and registry review, the intended
canonical S02 artifacts are:

```text
samples/sample_S02_professional_invoice.php
samples/templates/template_S02_professional_invoice.odt
samples/output/output_S02_professional_invoice.odt
```

The generated output remains a local/regression artifact and must not be
committed unless an explicit task changes the repository policy.

The final registry entry should use:

```text
id: S02
title: Professional Invoice
role: showcase
status: canonical
distribution: composer
execution_mode: odt

ownership: to be classified from the final implementation
```

The exact purpose text and migration-target reconciliation belong to the
implementation/registry slice after the final S02 behavior is known.

## Legacy migration evidence

The current registry identifies these S02-directed historical lessons:

- legacy Sample 04 — metadata — targets S02 and S03;
- legacy Sample 10 — Smart Business Letter — targets S02;
- legacy Sample 14 — Advanced Tabs — targets L04, L06, and S02.

S02 should absorb only the relevant integrated business-document lessons:

- meaningful document metadata where appropriate;
- professional composition of template logic, data binding, images, and
  document structure;
- Writer-authored/native tab geometry for aligned invoice content.

S02 completion does not automatically prove that every historical behavior in
those samples is obsolete. Migration targets must be reconciled against the
actual final showcase before any target is removed.

## Implementation slices

S02 should proceed in small evidence-based slices.

### S02-A — Writer template authoring

Create the LibreOffice-authored professional invoice template using B01 as the
visual reference and this contract as the ownership boundary.

Required review:

- native Writer structure inspection;
- placeholder/control placement;
- semantic paragraph styles;
- real tab stops;
- Section names/condition grammar;
- User Field declarations/references if used;
- image/frame identity if replacement is used;
- no accidental PHP ownership of stable layout.

This slice is rendering-sensitive and requires manual LibreOffice review.

### S02-B — Data binding and rendering sample

Add the canonical PHP sample using only established APIs.

The sample data must include:

- document-global invoice identity/date values;
- recipient data;
- at least two line items with distinct descriptions and quantities;
- externally supplied subtotal, tax, and grand total;
- values sufficient to exercise each authored salutation branch in focused
  validation, even if the public sample renders one representative branch;
- payment/signature values where dynamic;
- image input only if the template intentionally makes branding replaceable.

No arithmetic is performed by the engine.

### S02-C — Characterization and integration

Add or extend focused tests for the S02 contract where existing generic tests
do not already provide sufficient evidence.

At minimum verify:

- S02 is registered canonically;
- its declared template exists;
- generation produces a valid ODT;
- expected dynamic values are present after rendering;
- item repetition has the intended cardinality and distinct item `line_total` /
  ROOT `total` scope;
- the chosen conditional salutation branch survives and the other branch does
  not;
- User Field values are updated consistently if User Fields are part of S02;
- authored style/tab/Section structure required by the showcase is preserved
  at the appropriate semantic level.

Tests must not overfit LibreOffice's incidental XML normalization.

### S02-D — Visual regression and closeout

Perform the rendering-sensitive gate:

```text
generated ODT
    ↓
LibreOffice open/headless conversion
    ↓
PDF
    ↓
PNG pages where useful
    ↓
manual visual review
```

Review at least:

- normal representative invoice;
- multiple item rows;
- a long item name/description;
- salutation branch behavior;
- totals alignment;
- logo/branding if dynamic;
- no unexpected overflow or layout collapse.

If the invoice crosses a page boundary during stress validation, Writer owns
pagination. S02 must not add PHP pagination logic to force the result.

After visual acceptance, reconcile the sample registry/migration evidence and
record S02 completion evidence.

## Automated validation

For relevant implementation slices run, as applicable:

- PHP lint for changed PHP files;
- focused S02/integration tests;
- `PublicSampleSmokeTest`;
- full `composer test`;
- `composer validate` when Composer/package metadata is touched;
- ODT ZIP integrity checks;
- XML parsing/validation for relevant `content.xml` and `styles.xml`;
- `git diff --check`;
- documentation build/check when documentation changes require it.

Automated validation does not replace the manual LibreOffice visual gate.

## Compatibility contract

S02 is a consumer of established public behavior.

It must preserve:

- existing public APIs;
- existing render/save lifecycle semantics;
- repeated render/save behavior;
- `content.xml` / `styles.xml` processing behavior;
- protected compatibility facades and polymorphic override points;
- legacy sample behavior outside explicitly approved migration changes.

No compatibility change is justified merely to make S02 easier to author.

## Artifact protection

Implementation work must not casually modify, restore, delete, or regenerate:

- unrelated files under `samples/output/`;
- LibreOffice `.~lock.*#` files;
- `research/` artifacts, including the invoice reference image;
- unrelated local sample outputs;
- B01 artifacts except where a later explicit maintenance task requires it.

The B01 reference remains evidence and teaching material after S02 exists.

## Acceptance criteria

S02 reaches FINAL GO only when all of the following are true:

1. a professional invoice template exists as a real editable
   LibreOffice-authored ODT;
2. stable visual design and structure are template-owned rather than rebuilt
   in PHP;
3. dynamic invoice data renders through existing supported mechanisms;
4. document-global User Fields are used only where their semantics fit, if
   used at all;
5. conditional salutation is implemented with supported declarative Writer
   Section semantics;
6. `items` repeats correctly with item-scoped values, including item
   `total`, while ROOT `total` remains the document total;
7. item alignment uses Writer-authored native tab stops rather than spaces;
8. subtotal, tax, and total are externally supplied and no invoice arithmetic
   is attributed to the engine;
9. no new engine capability is smuggled into the showcase;
10. registry and migration evidence accurately describe the final sample;
11. focused/integration/public-sample validation is green;
12. the generated ODT passes manual LibreOffice visual review;
13. B01 remains a distinct Builder sample rather than being replaced by S02;
14. findings that belong to future architecture are documented instead of
    being silently implemented during S02.

## Stop conditions

Stop the implementation slice and return to architecture discussion if any of
the following becomes necessary:

- native table-row repetition is required to achieve the agreed design;
- existing foreach semantics cannot preserve the authored item layout;
- the desired conditional salutation cannot be expressed with established
  declarative Section semantics;
- User Field behavior conflicts with the required document-global values;
- the template requires a new named-element mutation capability;
- a new public API appears necessary;
- a rendering defect requires changing existing engine semantics;
- LibreOffice normalization destroys a structure on which S02 depends.

The correct response to such a stop condition is characterization and a
separate decision, not an opportunistic workaround inside S02.

## Final architectural statement

S02 succeeds when the invoice looks and behaves like a professional business
document while remaining recognizably authored in LibreOffice.

The engine should make the template dynamic without becoming the visual
designer:

```text
LibreOffice / Writer
    owns design
    owns stable structure
    owns native styles and tab geometry
        +
ODT Template Engine
    binds document data
    executes supported conditions
    repeats supported item content
    replaces supported resources
        =
Professional editable invoice
```

# SA-01 Section Authoring Contract

Status: approved implementation contract, 2026-11-17. Baseline: `4101548` on
`feature/section-authoring-01`.

## Public surface

SA-01 adds exactly these authoring entry points:

```php
new SectionElement(string $name)
    ->addElement(OdtElement $element): self
    ->addSection(SectionElement $section): self

OdtTemplate::appendBody(OdtElement $content): void
OdtTemplate::appendSection(SectionElement $section): SectionTarget
```

`SectionTarget::appendSection()` is deliberately not part of SA-01. Existing
`replaceContent()` and all protected compatibility paths retain their current
contracts.

## Native representation

`SectionElement` emits one native `text:section` with `text:name`. Its owned
children are emitted in insertion order. Nested `SectionElement` instances
remain nested native Sections. SA-01 exposes no Section style, column,
protection, display, condition, notes, or background-image options.

An empty Section emits the schema-minimal native `text:section` with its
`text:name` and no child block; it does not receive a synthetic paragraph. The
Writer reference fixture is not evidence for an empty Section—it contains
paragraphs in every inspected Section. The focused construction test verifies
the chosen minimal representation and the absence of a synthetic child. In two isolated headless Writer probes, the empty named Section was retained and Writer added exactly one `text:p` child and a Section style. The second probe used only valid newly constructed content and also preserved nested Sections, paragraph order, text and hyperlink destination (Writer normalized the URL). This establishes structural roundtrip behavior, not byte stability or visual acceptance.

## Insertion and validation

`appendBody()` appends legal block content directly under `office:body/office:text`.
`appendSection()` appends the supplied native Section at the same carrier and
returns an identity-backed `SectionTarget`. Both mutate the working package
immediately; neither requires a placeholder or calls `render()`.

Legal direct block nodes are `text:p`, `text:h`, `text:list`, `table:table`,
`draw:frame`, and `text:section`. A direct frame is hosted in a generated
`text:p`, matching existing Section replacement behavior. Inline-only nodes,
malformed bookmarks, empty Section names, and same-type identity collisions
are rejected before insertion. Different native object types may retain the
existing compatibility rule allowing the same name.

Construction uses the existing semantic style/resource preparation and the
existing package/context snapshot rollback boundary. A failure restores the
working DOM, style state, package resources, and lifecycle state. Repeated
save/render operations do not duplicate constructed nodes or resources.

`inspect()` and `section()` address the working document. `sourceDom()` and
`inspectTemplate()` remain source-oriented. `load()` resets constructed content
from the original template.

## Visual acceptance evidence

2026-11-17: C06 generated from the blank Writer shell, with six named Sections, nested content, hyperlink, list, table, image, text box, empty Section and target replacement. `tools/visual-regression/render.sh samples/output/output_C06_section_authoring.odt` produced one PDF page and one PNG at 150 DPI under `tmp/visual-regression/`. The complete PNG was inspected: expected content and order, no visible clipping or overlap. Visual GO for C06 / SA-01; first visual reference, not a pixel comparison. The PNG cannot prove native identity or editability; structural tests and Writer inspection complement it. Local output and rendering artifacts are not committed.

## Explicit boundary

This slice does not add Section styles or layout authoring, SectionTarget
nested construction, pagination/break abstractions, or unrelated refactoring.

# SECTION-02D — Block-Context Frame Materialization

## A. Visual defect discovered

Sample 24 produced a ZIP-valid ODT with a valid image and manifest entry, but
LibreOffice did not render the inserted image and introduced an unexpected
page. The generated structure was a naked frame directly below a section:

```text
text:section
└── draw:frame
    └── draw:image
```

## B. LibreOffice evidence

The LibreOffice-authored CV templates contain visible Writer-flow images in a
paragraph host:

```text
text:p
└── draw:frame
    └── draw:image
```

Their frames use `text:anchor-type="char"`. This is evidence for a required
flow context, but it does not by itself prove that the engine's default anchor
must change globally.

## C. Root cause

The previous section contract treated every legal top-level materialized node
as a direct section child. A `draw:frame` is a valid native element, but a
valid element is not automatically a valid Writer-flow placement. The defect
was the missing paragraph host, not an established need to change all image
anchor defaults.

## D. Bounded materialization rule

`SectionMutationService` now applies a small internal block-context rule:

- top-level `draw:frame` output is hosted in a new namespace-aware `text:p`;
- existing `text:p`, `text:h`, `text:list`, and `table:table` nodes are not
  wrapped;
- frames nested in an existing paragraph are not wrapped again;
- no public materialization-context API is introduced.

This is a frame-like block-host rule, not an ImageElement-specific sample
hack. It can therefore cover ImageElement and frame-like DrawTextBox output
without changing their element APIs.

## E. ImageElement result

Section image replacement now produces:

```text
text:section
└── text:p
    └── draw:frame
        └── draw:image
```

The section remains named and addressable. Package copying and manifest
synchronization remain owned by the SECTION-02C package path.

## F. Anchor decision

The existing ImageElement default `text:anchor-type="paragraph"` is retained.
Changing it globally would alter established image APIs and was not necessary
to correct the proven structural defect. The working LibreOffice `char`
anchor remains a useful local authoring pattern, but anchor/geometry policy is
deferred unless local visual validation proves that the paragraph host alone
is insufficient.

## G. DrawTextBox findings

DrawTextBox normally returns a paragraph-hosted frame for its default anchor,
so it already follows the required block shape. Its `as-char` form returns a
bare frame and is covered by the same section host rule. Geometric placement,
wrapping, z-index and positioned-frame behavior remain separate unresolved
concerns and are not redesigned here.

## H. Namespace handling

The wrapper is created with the ODF text namespace. The existing section copy
path preserves namespace-aware `text:p`, `draw:frame`, `draw:image`, and xlink
attributes. Tests assert namespace URIs rather than relying only on prefixes.

## I. Transactional compatibility

The paragraph host is added during detached materialization before package
resource commit and DOM replacement. SECTION-02C rollback behavior is
unchanged: failed resource preparation or DOM commit leaves the section and
new package files in their previous state.

## J. Tests and samples

Focused tests cover the section host, nested frame/image structure, namespace
correctness, resource copying, manifest output, save/reopen and rollback.
Sample 23 remains the non-frame structured-content regression control. Sample
24 is regenerated and its package/XML structure is verified with the corrected
host shape.

## K. Visual validation

The generated Sample 24 output is structurally corrected, but the agent
environment retains the known LibreOffice `javaldx`/`dconf` limitation. Local
visual validation is required:

```bash
php samples/sample_23_sectionContentReplacement.php
php samples/sample_24_sectionImageReplacement.php
./tools/visual-regression/render-odt.sh \
  samples/output/output_24_sectionImageReplacement.odt
```

Acceptance requires a visible image between the surrounding section text,
without an unexpected blank page or surrounding CV corruption.

## L. Remaining frame/text-box debt

This slice does not define general frame geometry, anchor policy, text-box
positioning, or page/cell-specific host contexts. Those require separate
evidence and must not be conflated with structural block placement.

## M. SECTION-03 implications

Future section clone/instantiate work can reuse the explicit distinction
between native materialization and placement context. It must preserve the
same host rule while separately defining frame identity, resource naming and
geometry semantics.

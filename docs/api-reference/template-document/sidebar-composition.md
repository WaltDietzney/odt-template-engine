# Multi-page Layouts and Sidebars (H02)

**Advanced · Writer page layout and structured frame composition**

H02 composes existing APIs to create first-page and continuation-page
layouts with a sidebar. It did **not** introduce a `SidebarBuilder` class.

## Ownership and approach

- Create native page layouts and page styles with `PageLayoutOdtTemplate`.
- Supply a native `DrawTextBox` as header content when a full-height
  floating sidebar is needed.
- Assign the first page style to the actual starting paragraph style and
  connect the continuation page using `setNextPageStyle()`.
- Place the flowing main content in a Section with an appropriate indent.
  Writer, not PHP, determines the actual page breaks.

The header serves as the sidebar frame's **anchor carrier**. Its height is
not the height of the floating sidebar. Keep the sidebar's frame geometry
separate from the header's own dimensions.

## Relevant public APIs

| Task | Public entry point | Reference |
| --- | --- | --- |
| Page dimensions and margins | `definePageLayout()` | [Programmatic Page Styles](page-styles.md) |
| First and continuation masters | `definePageStyle()`, `setNextPageStyle()` | [Programmatic Page Styles](page-styles.md) |
| Select first master | `assignPageStyle()` | [Programmatic Page Styles](page-styles.md) |
| Floating sidebar frame | `DrawTextBox::setFrameLayout()` | [Frames & Text Boxes](../structured-content/frames-text-boxes.md) |
| Main content Section and columns | `SectionElement`, `DocumentStyles::defineSection()` | [Native Section Authoring](../structured-content/section-authoring.md) |

For a complete runnable composition example, use the existing
[C08 sample](../../../samples/sample_C08_cv_composition.php)
and the [S01b template](../../../samples/templates/template_S01b_cv_structured.odt).
The sample and template are the executable reference, not an additional
new public builder API.

## Important limits

A Writer header frame may use paragraph- or character-relative anchoring
and wrapping that must be verified in the target document. An empty initial
paragraph with the wrong page style can trigger an unwanted blank page.
Avoid applying the first-page master to every body paragraph. Verify
first/continuation styles, repeated render/save, page count, and visual
placement in LibreOffice.

The [H02 sidebar composition evidence](../../architecture/H02_SIDEBAR_COMPOSITION_PATTERN.md)
records exact values for one CV reference; they are **not universal defaults**.
The [H02 closeout](../../architecture/H02_CLOSEOUT.md) records the visual
acceptance and remaining scope boundaries. Native user-field construction
and H03 shape geometry were not H02 features.

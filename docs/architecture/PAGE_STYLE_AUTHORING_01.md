# PAGE-STYLE-AUTHORING-01 — Programmatic Writer Page Styles

## Ausgangssituation

PAGE-FLOW-01 preserved authored Writer master pages, page-layout references,
page-owned header/footer content, and paragraph-to-master-page references. The
former public surface could only mutate geometry on an existing master page.
It could not define a new `style:page-layout`, define a new
`style:master-page`, or assign a generated master page to a paragraph style.

PAGE-STYLE-AUTHORING-01 adds that bounded authoring capability without adding
a pagination engine. LibreOffice Writer remains responsible for physical page
selection and pagination.

## Architecture decision

The implementation keeps three native ownership layers separate:

```text
style:master-page
    identity, page-owned header/footer content, next-style-name

style:page-layout
    page width/height, margins, orientation

style:style[style:family="paragraph"]
    style:master-page-name assignment and paragraph flow properties
```

`PageLayoutManager` remains responsible for page-layout geometry and now also
provides the low-level creation operation. `PageStyleManager` owns master-page
creation, succession, assignment, and page-owned structured content.
`PageLayoutOdtTemplate` is the public facade.

## Public API

```php
$template->definePageLayout('FirstLayout', [
    'page-width' => '21cm',
    'page-height' => '29.7cm',
    'margin-top' => '1cm',
    'margin-right' => '1cm',
    'margin-bottom' => '1cm',
    'margin-left' => '1cm',
    'orientation' => 'portrait',
]);

$template->definePageStyle('FirstPage', 'FirstLayout', [
    'next-style-name' => 'ContinuationPage',
    'header' => (new Paragraph())->addText('First page'),
    'footer' => (new Paragraph())->addText('First footer'),
]);

$template->definePageStyle('ContinuationPage', 'ContinuationLayout', [
    'header' => (new Paragraph())->addText('Continuation'),
    'footer' => (new Paragraph())->addText('Continuation footer'),
]);

$template->assignPageStyle('FirstParagraph', 'FirstPage');
$template->setNextPageStyle('FirstPage', 'ContinuationPage');
```

Methods return the template facade for fluent authoring, except existing
`setValues()`/`render()` lifecycle methods which retain their existing return
contracts.

### `definePageLayout()`

Creates a new `style:page-layout` in `styles.xml`. It supports the same seven
geometry options as `setPageLayout()`: four margins, page width, page height,
and portrait/landscape orientation. The method rejects empty names, duplicate
layout names, empty geometry values, and invalid orientation values.

### `definePageStyle()`

Creates a new `style:master-page` referencing an existing page layout. Optional
options are `display-name`, `next-style-name`, `header`, and `footer`.
Header/footer values are `OdtElement` instances and are materialized in the
master-page-owned styles document region. Existing structured style and image
resource ownership services are reused.

### `assignPageStyle()`

Sets `style:master-page-name` on the paragraph style actually resolved by
document content. Existing automatic paragraph styles in `content.xml` are
mutated in place. If only a common style in `styles.xml` exists, a content-local
copy with the same identity and preserved properties is created. If no style
exists, a new Standard-derived content-local style is created. Existing
`text:p`/`text:h` references are not rewritten.
This is a native Writer assignment to a flow style, not a page-number or
pagination instruction.

### `setNextPageStyle()`

Sets or removes `style:next-style-name` on an existing master page. The target
successor must exist when this explicit mutation is requested. During initial
definition, a successor may be declared before it is defined so that a
First-Page/Continuation chain can be authored naturally.

## ODF mapping

```text
definePageLayout()          → office:automatic-styles/style:page-layout
geometry                    → style:page-layout-properties
definePageStyle()           → office:master-styles/style:master-page
layout reference            → style:page-layout-name
successor                   → style:next-style-name
assignPageStyle()           → style:master-page-name on paragraph style
header/footer               → style:header/style:footer under master-page
```

No Section, column, or pagination structures are created by this milestone.

### Content-local paragraph-style resolution

LibreOffice resolves paragraph styles used by document content through
`content.xml/office:automatic-styles`. Authored documents commonly keep the
actual page-owning paragraph style there, even when related named styles are
defined in `styles.xml`. `assignPageStyle()` therefore uses this resolution
order:

1. mutate the existing content-local automatic paragraph style;
2. otherwise copy the existing common paragraph style into content automatic
   styles with the same identity and preserved properties;
3. otherwise create a new Standard-derived content-local paragraph style.

The `text:p`/`text:h` style references remain unchanged. This preserves style
identity from the document-content point of view while making the native
`style:master-page-name` assignment effective in LibreOffice rendering.

## Identity and conflict handling

Names are caller-supplied and document-local. A duplicate page-layout or
master-page identity fails explicitly. A paragraph-style assignment rejects
ambiguous same-name styles and rejects a non-paragraph style. Existing
structures are not overwritten.

Definition operations use an `OdtDocumentContext` snapshot and restore the
working DOM if validation or materialization fails. Image resources prepared
for page-owned content are removed on failure where package ownership is
available.

## Compatibility

The existing methods `setPageMargins()` and `setPageLayout()` retain their
behavior. Existing authored master pages, page layouts, headers, footers, and
paragraph flow styles remain authoritative unless the new API explicitly
targets them. No existing template is rewritten implicitly.

## Known limitations

- Header/footer authoring is bounded to structured `OdtElement` content.
- No public page-style target/inspection object is introduced yet.
- No Section authoring or Section columns are included.
- No column breaks or PHP pagination are included.
- LibreOffice PDF/visual verification depends on the local LibreOffice
  installation and must be reported separately from XML and save/reopen tests.

## CV Generator Pro integration note

This capability was motivated by the Visual Template Designer requirement to
generate editable first-page and continuation-page layouts without requiring a
pre-authored First Page/Continuation pair. It is generic engine functionality.
The engine has no dependency on CV Generator Pro, applicant data contracts,
application schemas, or CV-specific layout concepts. CV Generator Pro should
consume only the public `PageLayoutOdtTemplate` API.

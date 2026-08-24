# Page Layout

`PageLayoutOdtTemplate` extends `OdtTemplate` with helpers for changing selected page geometry in `styles.xml`.

Use it when the LibreOffice template owns the page design but the application needs to adjust margins, dimensions, or orientation programmatically.

## Page margins

```php
use OdtTemplateEngine\PageLayoutOdtTemplate;

$template = new PageLayoutOdtTemplate(
    __DIR__ . '/templates/report.odt'
);

$template->setPageMargins(
    '1.5cm', // top
    '1.5cm', // right
    '1.5cm', // bottom
    '1.5cm'  // left
);
```

By default, the method resolves the master page named `Standard` and updates the page layout referenced by that master page.

A different master page can be supplied explicitly:

```php
$template->setPageMargins(
    '1cm',
    '1cm',
    '1cm',
    '1cm',
    'MyMasterPage'
);
```

## General page layout options

`setPageLayout()` currently supports:

```php
$template->setPageLayout([
    'margin-top' => '1cm',
    'margin-right' => '1.2cm',
    'margin-bottom' => '1cm',
    'margin-left' => '1.2cm',
    'page-width' => '29.7cm',
    'page-height' => '21cm',
    'orientation' => 'landscape',
]);
```

Supported keys are:

- `margin-top`
- `margin-right`
- `margin-bottom`
- `margin-left`
- `page-width`
- `page-height`
- `orientation` (`portrait` or `landscape`)

Empty values and invalid orientation values cause a runtime exception rather than silently producing an invalid layout request.

## How page layout is resolved

ODF page geometry is not stored directly on the document body. The relevant relationship is approximately:

```text
styles.xml
│
├── style:master-page
│       │
│       └── style:page-layout-name
│                    │
│                    ▼
└── style:page-layout
        │
        └── style:page-layout-properties
                ├── fo:margin-top
                ├── fo:margin-right
                ├── fo:margin-bottom
                ├── fo:margin-left
                ├── fo:page-width
                ├── fo:page-height
                └── style:print-orientation
```

`PageLayoutOdtTemplate` follows this reference rather than assuming a fixed generated style name.

If the requested master page does not exist, does not reference a page layout, or the referenced layout has no properties node, the API throws an exception with the corresponding structural problem.

## Template-first page design

Programmatic page layout should complement the template rather than replace it.

Prefer LibreOffice for stable design decisions such as:

- headers and footers;
- page styles and master-page structure;
- multi-column document design;
- fixed frames and visual branding.

Use `PageLayoutOdtTemplate` when application data or a layout variant genuinely needs to change page geometry.

## Why this is a separate class

Page layout changes operate on `styles.xml` and have different responsibilities from normal placeholder replacement. Keeping them in `PageLayoutOdtTemplate` makes that advanced behavior explicit while preserving `OdtTemplate` as the normal entry point.

The implementation also overrides list-indentation adjustment so page margins are not accidentally affected by the historical list-style cleanup path.

## Example: complex CV

Sample 21 uses `PageLayoutOdtTemplate` because the LibreOffice template defines the two-column CV structure while PHP adjusts the page margins and fills the two large generated regions.

```php
$template = new PageLayoutOdtTemplate(
    __DIR__ . '/templates/template_21_cvProfile.odt'
);

$template->setPageMargins('0cm', '0.8cm', '0cm', '0cm');
```

This is a good example of the intended division of responsibility: LibreOffice owns the durable page composition; PHP owns dynamic document content and selected geometry.

## Related resources

- Sample 21 — complex CV / page-layout usage
- `PageLayoutOdtTemplateTest` — integration coverage for page-layout changes

See [ODT Internals](odt-internals.md) for the role of `styles.xml` and master pages in the package.

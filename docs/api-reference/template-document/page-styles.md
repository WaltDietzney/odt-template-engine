# Programmatic Page Styles

`PageLayoutOdtTemplate` provides bounded native Writer page-style authoring.
Use it when an application must create First Page and continuation layouts
without requiring those master pages to exist in the input template.

```php
$template = new PageLayoutOdtTemplate('template.odt');

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
]);
$template->definePageStyle('ContinuationPage', 'ContinuationLayout');
$template->assignPageStyle('FirstParagraph', 'FirstPage');
```

## Methods

```php
definePageLayout(string $name, array $options): static
definePageStyle(string $name, string $pageLayoutName, array $options = []): static
assignPageStyle(string $paragraphStyleName, string $masterPageName): static
setNextPageStyle(string $masterPageName, ?string $nextMasterPageName): static
```

`definePageLayout()` accepts `margin-top`, `margin-right`, `margin-bottom`,
`margin-left`, `page-width`, `page-height`, and `orientation`.

`definePageStyle()` accepts `display-name`, `next-style-name`, `header`, and
`footer`. Header and footer values must be `OdtElement` instances such as
`Paragraph` or `RichText`; they become native content owned by the generated
master page.

`assignPageStyle()` writes `style:master-page-name` on the paragraph style
actually resolved by document content. Existing automatic paragraph styles in
`content.xml` are mutated in place. If only a common style in `styles.xml`
exists, a content-local copy with the same identity and preserved properties is
created. If no style exists, a new Standard-derived content-local style is
created. Existing `text:p`/`text:h` references are not rewritten.

Writer remains responsible for actual pagination and page selection.

For multi-page CV composition with floating header sidebars, see [Multi-page Layouts and Sidebars](sidebar-composition.md).

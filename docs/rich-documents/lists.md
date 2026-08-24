# Lists

Use `ListElement` when PHP needs to generate a real bulleted or numbered ODT list.

Do not simulate lists by prefixing paragraph text with characters such as `•` or `1.` when the content is structurally a list. Native ODF list elements remain easier to edit and preserve list semantics in LibreOffice.

## Bullet list

```php
use OdtTemplateEngine\Elements\ListElement;
use OdtTemplateEngine\Elements\Paragraph;

$list = new ListElement('bullet');
$list
    ->addItem((new Paragraph())->addText('First item'))
    ->addItem((new Paragraph())->addText('Second item'))
    ->addItem((new Paragraph())->addText('Third item'));

$template->setElement('features', $list);
```

The generated structure uses native ODF `text:list` and `text:list-item` elements.

## Numbered list

```php
$list = new ListElement('numbered');
$list
    ->addItem((new Paragraph())->addText('Prepare data'))
    ->addItem((new Paragraph())->addText('Render document'))
    ->addItem((new Paragraph())->addText('Open in LibreOffice'));
```

If no explicit list style name is supplied, the element uses the engine's default bullet or numbering style name.

## Styled list text

List items are paragraphs, so normal text styling remains available:

```php
$list = new ListElement('bullet');

$list->addItem(
    (new Paragraph())
        ->addText('Important', ['bold' => true, 'color' => '#12324a'])
        ->addText(' — generated as native list content.')
);
```

Paragraph styles can also be applied to the item paragraphs when spacing or other paragraph-level behavior is needed.

## Nested lists

A list can contain another `ListElement`:

```php
$main = new ListElement('numbered');
$main->addItem((new Paragraph())->addText('Parent item'));

$nested = new ListElement('bullet');
$nested
    ->addItem((new Paragraph())->addText('Nested item A'))
    ->addItem((new Paragraph())->addText('Nested item B'));

$main->addSubList($nested);
```

`addSubList()` raises the nested list level relative to the parent. List levels are constrained to the ODT-oriented range supported by the element.

## Convenience methods on RichText

For simple string lists, `RichText` offers convenience methods:

```php
$richText->addBulletList([
    'PHP',
    'LibreOffice',
    'OpenDocument',
], [
    'font-size' => '10pt',
]);
```

and:

```php
$richText->addNumberedList([
    'Create template',
    'Assign data',
    'Generate ODT',
]);
```

Use explicit `ListElement` objects when you need nested lists, individually styled items, or more control over list composition.

## Lists inside generated sections

Lists can be mixed with other ODT elements in a `RichText` block:

```php
$section = new RichText();
$section->addParagraph('Requirements', null, ['bold' => true]);
$section->addElement($list);

$template->setElement('requirements', $section);
```

They can also be used as part of richer table-cell content when the cell contains a suitable structured block.

## Current limitations

List rendering uses native ODF structures, but fine-grained list-layout control is still an active development area. In particular, indentation behavior and advanced nested-list style customization are tracked for future improvement.

For documents where exact visual list geometry is critical, generate representative output and inspect it in the LibreOffice version used by your target workflow.

## Related samples

- Sample 18 — native numbered, bulleted, and nested list structures
- Sample 21 — native bullet lists used in a larger editable CV document

See [RichText & Paragraphs](richtext-and-paragraphs.md) for styling the paragraphs used as list items.

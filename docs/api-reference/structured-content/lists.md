# Lists

`ListElement` is the PHP-owned structured list model. Use it when PHP owns the list structure. For simple lists of strings, `RichText::addBulletList()` and `addNumberedList()` are convenient shortcuts.

## `ListElement`

**Recommended · Structured Content**

```php
public function __construct(
    string $type = 'bullet',
    ?string $styleName = null
)
```

The default style name is `Bullet_20_Symbol` unless `$type === 'numbered'`, in which case it is `Numbering_20_Symbol`. Other type strings currently fall back to the bullet default; no broader type vocabulary is part of the 1.0 contract.

A supplied `$styleName` is emitted as the list's `text:style-name`. `ListElement` does not define that named list style itself.

## `addItem()`

```php
public function addItem(Paragraph|ListElement $item): self
```

Adds either a paragraph item or a nested list.

```php
use OdtTemplateEngine\Elements\ListElement;
use OdtTemplateEngine\Elements\Paragraph;

$list = new ListElement('bullet');
$list
    ->addItem((new Paragraph())->addText('First item'))
    ->addItem((new Paragraph())->addText('Second item'));

$content->addElement($list);
```

Each supplied item is materialized inside its own `text:list-item`.

## Nested lists

```php
public function addSubList(ListElement $list): self
```

Adds another `ListElement` as a nested list and sets its stored level to the parent's level plus one.

```php
$parent = new ListElement('bullet');
$child = (new ListElement('bullet'))
    ->addItem((new Paragraph())->addText('Nested item'));

$parent
    ->addItem((new Paragraph())->addText('Parent item'))
    ->addSubList($child);
```

### Current level limitation

```php
public function setLevel(int $level): self
```

`setLevel()` clamps the stored value to `1..10`, but the current renderer does **not** use that value when producing ODT. It therefore has no independent visual effect in 1.0.

Actual nesting comes from nesting one `text:list` inside another via `addSubList()` / `addItem($list)`.

Do not use `setLevel()` as a promise of visual indentation or list-level formatting.

## RichText list conveniences

**Recommended for simple string lists**

```php
public function addBulletList(array $items, array $style = []): self
public function addNumberedList(array $items, array $style = []): self
```

Each item is converted to a `Paragraph` and its value is cast to string. The supplied `$style` is passed to `Paragraph::addText()`; it is therefore an inline **text-style** array, not a list-style or paragraph-layout array.

```php
$body->addBulletList(
    ['Fast', 'Editable', 'Native ODT'],
    ['bold' => true]
);
```

These helpers create a default `ListElement`. Use `ListElement` directly when you need nested lists or an explicit Writer list-style name.

## Paragraph list compatibility

`Paragraph::setBulleted()`, `setNumbered()`, and `isList()` are retained public behavior. They wrap a single paragraph in a one-item `text:list` using the historical `Bullet_20_Symbol` or `Numbering_20_Symbol` style.

For new multi-item PHP-owned lists, prefer `ListElement` or the `RichText` list conveniences.

## Ownership and limitations

`ListElement` owns PHP-generated list structure. It does not currently author list-style definitions or provide a separate semantic API for list level formatting.

A named `$styleName` therefore assumes that the referenced list style is available in the document.

## See also

- [RichText](richtext.md)
- [Paragraphs & Inline Content](paragraph.md)

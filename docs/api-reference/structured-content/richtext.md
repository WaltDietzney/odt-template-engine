# RichText

`RichText` is the Recommended container for a PHP-owned structured document region. It composes paragraphs and other `OdtElement` objects into one value that can be passed to `setElement()`.

```php
use OdtTemplateEngine\Elements\RichText;

$body = new RichText();
$body->addParagraph('First paragraph');
$body->addParagraph('Second paragraph');

$template->setElement('body', $body);
```

All authoring methods below are fluent and return the same `RichText` instance.

## Paragraph composition

### `addParagraph()`

**Recommended**

```php
public function addParagraph(
    string|Paragraph $text = '',
    ?string $styleName = null,
    array $styleOptions = []
): self
```

With a string, `RichText` creates a new `Paragraph`. `$styleName` references a paragraph style; `$styleOptions` is split into paragraph and text properties.

With an existing `Paragraph`, that paragraph is appended directly. In that form the supplied `$styleName` and `$styleOptions` are not applied.

```php
$body
    ->addParagraph('Heading', 'Heading 2')
    ->addParagraph('Important text', null, [
        'align' => 'center',
        'bold' => true,
    ]);
```

For this convenience array, `align` is normalized to `text-align` and `weight` to `font-weight`.

### `addText()`

**Recommended**

```php
public function addText(string $text, array $style = []): self
```

Adds text to the last paragraph, creating a paragraph when necessary. The mixed style array is split into paragraph and text properties using the same convenience rules as string-based `addParagraph()`.

```php
$body
    ->addText('Name: ', ['bold' => true])
    ->addText('Ada Lovelace');
```

### `addParagraphBreak()`

**Recommended**

```php
public function addParagraphBreak(int $count = 1): self
```

Appends `$count` empty paragraphs. A zero or negative count adds none.

### `addLineBreak()` and `addTab()`

**Recommended**

```php
public function addLineBreak(): self
public function addTab(): self
```

Operate on the last paragraph, creating one when necessary.

## Structured children

### `addElement()`

**Recommended**

```php
public function addElement(OdtElement $element): self
```

Appends an arbitrary structured child to this container.

### `addTable()`

**Recommended convenience method**

```php
public function addTable(RichTable $table): self
```

Appends a PHP-owned `RichTable`.

### `addImage()`

**Recommended convenience method**

```php
public function addImage(ImageElement $image): self
```

Wraps the image in a new `Paragraph` and appends that paragraph.

## List conveniences

```php
public function addBulletList(array $items, array $style = []): self
public function addNumberedList(array $items, array $style = []): self
```

Create a `ListElement` and one `Paragraph` per string item. The supplied style array is passed to `Paragraph::addText()`, so it is a **text-style array**, not a list-layout or paragraph-style array.

The full list contract is documented separately in the Structured Content list reference.

## `addMultiParagraph()`

**Advanced convenience method**

```php
public function addMultiParagraph(
    array $lines,
    ?array $style = null,
    bool $firstBold = false
): self
```

Adds one paragraph per string in `$lines`. `$style` is passed to `Paragraph::addText()`; when `$firstBold` is true, `bold => true` is merged into the first line's text style.

## Internal compatibility helpers

`applyParagraphStyleOptions()`, `applyTextStyle()`, and `popLastElementIfList()` are public in PHP but serve compatibility/composition internals. They are not Recommended application-authoring entry points.

Resource and style discovery methods exposed through the element hierarchy are likewise not normal application API.

## Ownership and lifecycle

A `RichText` object is PHP-owned structure. Construct it in application code and insert it with `setElement()`. Do not use it to model a Writer-authored region that already has a named native structure.

## See also

- [Structured Content](index.md)
- [Paragraphs & Inline Content](paragraph.md)

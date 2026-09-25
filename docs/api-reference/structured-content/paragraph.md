# Paragraphs & Inline Content

`Paragraph` represents one PHP-owned ODT paragraph. It combines inline text, text styles, line breaks, tabs, hyperlinks, paragraph styling, and embedded structured elements.

```php
use OdtTemplateEngine\Elements\Paragraph;

$paragraph = (new Paragraph())
    ->addText('Invoice ', ['bold' => true])
    ->addText('#2026-001')
    ->addLineBreak()
    ->addText('Paid', ['color' => '#008000']);
```

Unless noted otherwise, authoring methods are fluent and return the same `Paragraph`.

## Construction and paragraph styles

```php
public function __construct(
    ?string $paragraphStyle = null,
    array $paragraphStyleOptions = []
)
```

`$paragraphStyle` references a named paragraph style. When style options are supplied without a name, the engine generates a paragraph style name for the definition.

### `setParagraphStyle()`

**Recommended**

```php
public function setParagraphStyle(string $styleName): self
```

References the named paragraph style. Supplying a style name without style options is a style **reference**, not a request to redefine that Writer style.

### `setParagraphStyleOptions()`

**Recommended**

```php
public function setParagraphStyleOptions(array $options): self
```

Replaces the paragraph's current option array. It does not merge with the previous array.

If no paragraph style name exists, a generated name is assigned.

### Paragraph option contract

The following friendly keys are mapped by the current paragraph style mapper:

| Key | Value | Semantics |
| --- | --- | --- |
| `margin-left`, `margin-right`, `margin-top`, `margin-bottom` | ODF length | Paragraph margins. |
| `text-align` | ODF alignment value | Paragraph alignment. |
| `text-indent` | ODF length | First-line/text indent. |
| `line-height` | ODF line-height value | Line height. |
| `background-color` | color | Paragraph background. |
| `keep-with-next`, `keep-together` | ODF value | Pagination constraints. |
| `widows`, `orphans` | integer/ODF value | Widow/orphan control. |
| `break-before`, `break-after` | ODF break value | Page/column break behavior. |
| `writing-mode` | ODF writing-mode value | Writing direction/mode. |
| `padding`, `padding-*` | ODF length | Paragraph padding. |
| `border`, `border-*` | ODF border value | Paragraph border. |
| `number-lines`, `line-number` | ODF value | Line-number properties. |
| `tab-stops` | list of tab definitions | Each item uses `position` in cm and optional `alignment` (default `left`). |

`StyleOptionSplitter` also recognizes `align` as a convenience alias for `text-align` when a mixed convenience array passes through the splitter, for example `RichText::addParagraph(..., $styleOptions)`. Calling `setParagraphStyleOptions()` stores the supplied options directly; do not assume that every convenience alias is normalized at that call boundary.

Native-prefixed keys remain an Advanced compatibility escape hatch and should not replace the friendly API in normal application code.

## Text

### `addText()`

**Recommended**

```php
public function addText(string $text, array $style = []): self
```

Appends text and optionally defines an inline text style.

### Text option contract

| Key | Value | Semantics |
| --- | --- | --- |
| `bold` | truthy | Bold text. |
| `font-weight` | string | Explicit font weight. |
| `italic` | truthy | Italic text. |
| `font-style` | string | Explicit font style. |
| `underline` | truthy | Single solid underline. |
| `text-decoration` | string/truthy | Enables underline; `line-through` additionally enables strike-through. |
| `text-line-through` | truthy | Strike-through. |
| `color` | color | Text color. |
| `background-color` | color | Text background color. |
| `font-size` | string | Font size; named xx-small through xx-large map to 6pt through 17pt, with medium = 11pt. Other values pass through. |
| `font-family` | string | Font family/name. |
| `style:text-position` | string | Text position such as `sub` or `super`. |
| `font-variant` | `small-caps` | Enables small caps for that value. |
| `monospace` | `true` | Uses Courier New. |

Native `fo:` and `style:` keys are passed through by the mapper as an Advanced compatibility escape hatch.

The alias `weight` is normalized to `font-weight` only at APIs that use `StyleOptionSplitter`; `Paragraph::addText()` itself does not run that normalization.

## Line breaks and tabs

```php
public function addLineBreak(int $count = 1): self
public function addTab(): self
```

`addLineBreak()` appends `$count` ODT line-break elements. A zero or negative count adds none. `addTab()` appends one ODT tab.

### Tab definitions and tabbed text

**Advanced**

```php
public function addTabStopDefinition(
    float $position,
    string $alignment = 'left'
): self

public function addTabStop(
    float $position,
    string $alignment = 'left',
    ?string $text = null,
    array $style = []
): self
```

`addTabStopDefinition()` adds a paragraph tab-stop definition; the numeric position is serialized in centimeters.

`addTabStop()` appends a tab to the paragraph's inline content and may append text after it. Despite its name, it does **not** add the paragraph tab-stop definition itself. Define the tab position separately when the layout depends on it.

The convenience methods `addTabularLines()`, `addKeyValueLine()`, and `addTabsWithTexts()` remain supported Advanced helpers for tab-based layouts. They use the same paragraph tab-stop and inline text mechanisms.

## Hyperlinks

### `addHyperlink()`

**Recommended for unstyled hyperlinks · Known 1.0 limitation for styled hyperlinks**

```php
public function addHyperlink(
    string $text,
    string $href,
    array $style = []
): self
```

Creates a native ODT hyperlink with `xlink:href`, `xlink:type="simple"`, and `xlink:show="new"`.

Unstyled hyperlinks are covered by current focused tests.

**Known limitation:** the current `addHyperlink()` implementation records the supplied style on the rendered span but does not register that style in the paragraph's style-requirement map. Therefore the 1.0 reference does not promise correct materialization of a styled hyperlink through this method.

`addPHyperLink()` is a historical compatibility variant that performs that style registration. It remains discoverable for compatibility but is not the preferred name for new API design.

## Embedded elements

```php
public function addElement(OdtElement $element): self
```

Embeds a structured child such as an image into the paragraph.

## List compatibility methods

`setBulleted()`, `setNumbered()`, and `isList()` are retained public list-related behavior. Prefer `ListElement` or the `RichText` list conveniences when constructing new multi-item lists; their detailed contract belongs to the list reference.

## Compatibility and infrastructure helpers

`applyTextStyle()` is a compatibility helper used when mixed style arrays are propagated into existing content. Public materialization/style-discovery methods inherited from `OdtElement` are not normal application authoring API.

## See also

- [Structured Content](index.md)
- [RichText](richtext.md)

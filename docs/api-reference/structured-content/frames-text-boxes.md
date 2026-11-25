# Frames & Text Boxes

`DrawTextBox` creates a PHP-owned ODT `draw:frame` containing a `draw:text-box`. It is the structured-content API for newly constructed text boxes.

For a named frame already authored in Writer, use the Writer-native frame APIs instead.

## Construction and content

```php
public function __construct(string $name, array $options = [])
public function addElement(OdtElement $element): self
```

`$name` becomes the ODT `draw:name`. `addElement()` appends PHP-owned structured children to the text box.

```php
use OdtTemplateEngine\Elements\DrawTextBox;
use OdtTemplateEngine\Elements\Paragraph;

$box = (new DrawTextBox('Notice', [
    'width' => '7cm',
    'height' => '2.5cm',
]))
    ->addElement(
        (new Paragraph())->addText('Important notice', ['bold' => true])
    )
    ->setFrameAnchor('paragraph')
    ->setFrameHorizontalAlignment('right', 'paragraph');
```

## Semantic frame layout

**Recommended**

The semantic frame-layout API is shared by `DrawTextBox` and `ImageElement`.

### Complete layout form

```php
public function setFrameLayout(array $layout): self
```

The complete friendly contract is:

```php
[
    'anchor' => 'paragraph',
    'width' => '7cm',
    'height' => '2.5cm',
    'horizontal' => [
        'alignment' => 'right',
        'relative-to' => 'paragraph',
    ],
    'vertical' => [
        'offset' => '1cm',
        'relative-to' => 'paragraph',
    ],
    'wrap' => 'parallel',
]
```

Top-level keys other than `anchor`, `width`, `height`, `horizontal`, `vertical`, and `wrap` throw `InvalidArgumentException`.

### Anchors

Allowed anchors:

- `paragraph`
- `char`
- `as-char`
- `page`

### Size

`width` and `height` are positive absolute ODF lengths using `cm`, `mm`, `in`, `pt`, or `pc`.

### Horizontal placement

The `horizontal` group must contain **exactly one** of:

```php
['alignment' => 'left|center|right', 'relative-to' => '...']
['offset' => '1cm', 'relative-to' => '...']
```

Offsets may be signed absolute ODF lengths.

Allowed horizontal relations depend on the anchor:

| Anchor | Allowed `relative-to` |
| --- | --- |
| `paragraph` | `paragraph`, `paragraph-content`, `page`, `page-content` |
| `char` | `char`, `paragraph`, `paragraph-content`, `page`, `page-content` |
| `page` | `page`, `page-content` |
| `as-char` | no horizontal placement |

When the anchor is omitted, relation validation uses paragraph semantics.

### Vertical placement

The `vertical` group likewise requires exactly one of alignment or offset.

Alignment values are `top`, `middle`, or `bottom`. Offsets may be signed absolute ODF lengths.

| Anchor | Allowed `relative-to` |
| --- | --- |
| `paragraph` | `paragraph`, `paragraph-content`, `page`, `page-content` |
| `char` | `char`, `paragraph`, `paragraph-content`, `page`, `page-content`, `baseline` |
| `page` | `page`, `page-content` |
| `as-char` | `baseline` |

Vertical offset is not supported for `as-char`.

### Wrapping

Allowed values are:

`none`, `left`, `right`, `parallel`, `dynamic`, `run-through`.

### Focused fluent methods

```php
public function setFrameAnchor(string $anchor): self
public function setFrameHorizontalAlignment(string $alignment, ?string $relativeTo = null): self
public function setFrameVerticalAlignment(string $alignment, ?string $relativeTo = null): self
public function setFrameHorizontalOffset(string $offset, ?string $relativeTo = null): self
public function setFrameVerticalOffset(string $offset, ?string $relativeTo = null): self
public function setFrameWrap(string $wrap): self
```

These methods rebuild and validate the same semantic layout state.

Default relation for horizontal/vertical fluent placement is `page` for a page-anchored frame and `paragraph` otherwise; `as-char` vertical alignment defaults to `baseline`.

Calling `setFrameLayout([])` clears semantic layout state and returns to the legacy frame-option path.

## Structured insertion mode

For `DrawTextBox`:

- `as-char` is inserted into inline text flow;
- other anchors are block structured content.

This is different from `ImageElement`, whose non-`as-char` insertion preserves its surrounding text container.

## Text-box appearance

The following fluent methods remain supported for PHP-owned text-box appearance:

```php
public function setBackground(string $color): self
public function setFill(string $fill): self
public function setFillColor(string $color): self
public function setAllowOverlap(bool $allow = true): self
public function flowWithText(bool $enable = true): self
```

The underlying frame mapper also supports friendly border and padding keys when supplied through constructor options:

- `border`, `border-top`, `border-right`, `border-bottom`, `border-left`
- `padding`, `padding-top`, `padding-right`, `padding-bottom`, `padding-left`
- `background-color`
- `fill`, `fill-color`
- `wrap-influence`
- `allow-overlap`
- `corner-radius-x` / `rx`, `corner-radius-y` / `ry`

The mapper is permissive for unknown keys and passes them through. Such unknown/raw keys are not part of the Recommended friendly contract.

Width, height, anchor, and legacy position options in the constructor are object/layout carriers rather than ordinary graphic-style properties.

## Legacy positioning helpers

**Compatibility / Advanced**

```php
public function setVerticalPos(string $pos, string $rel = 'baseline'): self
public function setHorizontalPos(string $pos, string $rel = 'char'): self
public function setHorizontalPosition(string $pos, string $rel = 'page'): self
public function setVerticalPosition(string $pos, string $rel = 'page'): self
```

These write directly to the historical frame-option state and do not use the semantic `DrawingLayout` validation model.

For new code, prefer the `setFrame*()` semantic methods.

## Ownership

`DrawTextBox` means PHP owns the frame and text-box structure. The surrounding Writer template supplies the insertion point only.

A Writer-authored named frame is a different ownership model and belongs to the Writer-native API.

## Public implementation helpers

Frame style-requirement getters, DOM/style materialization methods, and related hooks are public for the engine pipeline or extension compatibility. They are not normal application-authoring API.

## See also

- [Structured Content](index.md)
- [Images](images.md)
- [Paragraphs & Inline Content](paragraph.md)

## Explicit Writer text flow and spacing

```php
$frame->setFrameLayout(['anchor' => 'as-char', 'width' => '4cm', 'height' => '0.1cm']);
$frame->setTextBoxSizing(['min-height' => '0.1cm', 'auto-grow-height' => true]);
$frame->setFrameSpacing(['padding-left' => '0.152cm', 'padding-right' => '0.152cm',
    'padding-top' => '0.152cm', 'padding-bottom' => '0.152cm',
    'margin-left' => '0.08cm', 'margin-right' => '0.08cm']);
```

`setTextBoxSizing(array $sizing): self` replaces an explicit sizing overlay.
Supported keys: `min-width`, `min-height` (positive absolute lengths in cm/mm/in/
pt/pc) and `auto-grow-width`, `auto-grow-height` (booleans). Minima are emitted
on the native `draw:text-box`; drawing Auto-Grow flags are graphic properties.
They are different ODF carriers. A native minimum selects variable Writer size
on that axis; combining it with an explicit false Auto-Grow flag is rejected.
Use `setFrameLayout()` dimensions without a native minimum for a fixed axis.
For contacts, fixed width plus native minimum height allows text to wrap and grow.

`setFrameSpacing(array $spacing): self` replaces an explicit spacing overlay.
It accepts `padding`, `margin` and their `-top/-right/-bottom/-left` forms, with
nonnegative absolute lengths in the same units. Shorthands expand into explicit
sides; a specific side wins regardless of input order. New values override matching
constructor-side options. Clearing either overlay with `[]` restores its legacy
constructor behavior; existing raw options and protected style hooks remain available.
Invalid keys, lengths and flags fail before changing the corresponding overlay.

**Maximum bounds are not supported by this new API.** The native `fo:max-width`/
`fo:max-height` control failed the actual Writer bound test: a nominal 4cm maximum
was discarded and text expanded across the page. These properties are not a
reliable clipping guard. The negative evidence is preserved in P2-I02 research.
Choose a fixed width and growing height for a wrapping fallback; consumer render
feedback must still reject content that exceeds its available region/page.
The engine does not silently shrink text or guarantee unbounded frame capacity.

Rectangular growing tags, explicit per-side spacing, 90 atomic tags over two pages,
body/header/footer composition and repeat-save/Writer/PDF are covered by the P2-I02
proof. Rounded Auto-Grow tags and general drawing composition remain separate.


### Explicit drawing layers and margins (P2-I03)

`DrawingLayout::fromArray()` additionally accepts:

| Key | Values and scope |
| --- | --- |
| `run-through` | `foreground` or `background`; requires `wrap => run-through` |
| `z-index` | Nonnegative integer, emitted as `draw:z-index`; group child order overrides child values |
| `margin` | Per-side map (`top/right/bottom/left`) of nonnegative absolute ODF lengths |

These options survive the immutable `with*()` updates. Changing wrap away from
`run-through` while retaining an explicit layer is rejected as inconsistent.
Margins control outside spacing; `ShapeTextLayout` padding controls inside spacing.
Object z-index and foreground/background have different roles: one orders drawing
objects, the other controls whether text appears in front of a run-through object.
Writer may normalize numeric indexes; verify visible paint order.

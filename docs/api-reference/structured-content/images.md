# Images

`ImageElement` is the Recommended structured-content API when PHP owns a newly inserted image and its frame.

For a Writer-authored named frame whose image should be replaced while the Writer structure remains authoritative, use the Writer-native frame/image replacement APIs instead.

## Construction

```php
public function __construct(string $imagePath, array $options = [])
```

When enabled, the image file must exist and be readable or construction throws `Exception`.

The image is packaged into the ODT `Pictures/` directory when the element is inserted.

```php
use OdtTemplateEngine\Elements\ImageElement;

$image = new ImageElement('assets/logo.png', [
    'width' => '4cm',
    'anchor' => 'as-char',
]);

$content->addImage($image);
```

## Constructor / legacy image options

The constructor passes its options through the historical image-style mapper.

| Key | Values / format | Meaning |
| --- | --- | --- |
| `enabled` | boolean | Disable materialization when false. Default: true. |
| `width` | length string | Frame width. |
| `height` | length string | Frame height. |
| `anchor` | `paragraph`, `page`, `char`, `as-char` | Frame anchor. |
| `wrap` | `none`, `left`, `right`, `run-through` | Historical wrapping option. |
| `align` | `left`, `right`, `center`, `absolute` | Historical alignment shortcut. |
| `horizontal-pos`, `horizontal-rel` | ODF-compatible strings | Historical positioning. |
| `vertical-pos`, `vertical-rel` | ODF-compatible strings | Historical positioning. |

If neither width nor height is supplied, the historical default is `5cm × 3cm`. If exactly one is supplied, the other dimension is calculated from the source image ratio. This legacy autoscaling assumes a centimeter-formatted supplied dimension; use explicit width and height when another unit is required.

Invalid historical `wrap`, `align`, or `anchor` values are silently omitted by the mapper rather than rejected. New layout-sensitive code should prefer the semantic frame-layout API below.

## Semantic frame layout

**Recommended for positioning and wrapping**

`ImageElement` shares the semantic frame-layout API with `DrawTextBox`:

```php
public function setFrameLayout(array $layout): self
public function setFrameAnchor(string $anchor): self
public function setFrameHorizontalAlignment(string $alignment, ?string $relativeTo = null): self
public function setFrameVerticalAlignment(string $alignment, ?string $relativeTo = null): self
public function setFrameHorizontalOffset(string $offset, ?string $relativeTo = null): self
public function setFrameVerticalOffset(string $offset, ?string $relativeTo = null): self
public function setFrameWrap(string $wrap): self
```

See [Frames & Text Boxes](frames-text-boxes.md) for the complete shared layout contract and validation matrix.

```php
$image
    ->setFrameAnchor('paragraph')
    ->setFrameHorizontalAlignment('center', 'paragraph')
    ->setFrameWrap('none');
```

Once semantic frame-layout state exists, it controls the corresponding layout carriers instead of the historical alignment/positioning branch.

## `setStyle()`

**Compatibility / low-level image option mapper**

```php
public function setStyle(array $options): self
```

Replaces the stored historical image options with their mapped form. Prefer constructor options for simple sizing and the semantic frame-layout API for new positioning code.

## Structured insertion behavior

An `as-char` image uses inline text-flow insertion. Other anchors preserve the surrounding text container during structured insertion.

This distinction matters when an image replaces a placeholder inside a paragraph.

## Disabled images

With `enabled => false`, the constructor skips file validation and materialization returns an empty paragraph. Treat this as a compatibility convenience rather than as conditional template logic.

## `CircularImageElement`

**Advanced**

```php
public function __construct(string $imagePath, array $options = [])
```

`CircularImageElement` renders a LibreOffice-style ellipse/custom shape filled with the image bitmap. Its default dimensions are `3.4cm × 3.4cm` when not supplied.

The circular image has its own custom-shape materialization path. The inherited semantic frame-layout setters currently update `ImageElement`'s frame-layout state, but `CircularImageElement::toDomNode()` does not project that state onto its custom shape. **No visual effect from those inherited frame-layout setters is promised for circular images in 1.0.**

Use constructor dimensions/anchor behavior that is known to be supported by the circular materializer, and treat more advanced custom-shape layout as a documented limitation.

## Public implementation helpers

Image asset discovery, image/style requirement getters, DOM materialization, and similar public methods exist for the engine's resource/style pipeline. They are not normal application-authoring entry points.

## See also

- [Structured Content](index.md)
- [Frames & Text Boxes](frames-text-boxes.md)
- [Template & Document Images](../template-document/images.md)

## Static SVG resources (P2-I04)

`ImageElement`, `setImage()`, named image replacement and mapped frame replacement
share the same local image metadata validation. SVG metadata does not depend on
PHP's `getimagesize()` SVG support, which differs between PHP 8.2 and PHP 8.5.
The manifest uses `image/svg+xml`; SVG bytes remain vector resources in the ODT.
Writer may add PNG alternatives when it saves the document.

A single explicit width or height derives the other dimension proportionally in
the same unit (`cm`, `mm`, `in`, `pt`, `pc`, `px`). Two explicit dimensions remain
explicit. `ImageElement` and `setImage()` retain their legacy `5cm × 3cm` fallback
when neither dimension is supplied. The viewport ratio takes precedence when an
SVG declares two absolute viewport dimensions; otherwise a valid viewBox supplies
its ratio. A ratio-less SVG can be used with two explicit dimensions, but cannot
supply a missing dimension. Native field text and image selection remain separate.

The supported SVG subset is deliberately bounded: UTF-8 XML, default SVG namespace,
static `svg/g/path/rect/circle/ellipse/line/polyline/polygon/title/desc`, and the
allowlisted geometry/paint attributes. Limits are 1 MiB, 4096 elements, depth 32
and 128 KiB per attribute. Scripts, event handlers, CSS/style nodes or attributes,
DTDs/entities, processing instructions, href/use/image references, foreign content,
paint URLs and prefixed SVG namespaces are rejected. This is validation, not a
universal SVG sanitizer or namespace conversion service. No external icon library
is loaded at runtime; the consumer owns icon semantics, variants and license notices.
SVG is revalidated when prepared for packaging, including files changed after element
construction. Existing SVG resources in an authored ODT are not globally sanitized.

Identical bytes at one package path are reused. Structured insertion continues to
reject different bytes competing for the same basename atomically. Legacy image
insertion/replacement now preserves an occupied basename by allocating a deterministic
hash-suffixed path for different content. This prevents changes to other objects
sharing the old image. It does not rename every resource or promise global deduplication
across different filenames. No crop/cover/contain API is introduced.

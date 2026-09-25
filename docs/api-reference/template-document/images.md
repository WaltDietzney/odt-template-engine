# Template Images

The core facade provides a simple placeholder-image convenience and retains a historical named-frame replacement API.

Choose image APIs by ownership:

- use `setImage()` for a classic visible image placeholder;
- use `ImageElement` when PHP constructs the structured image/frame;
- use the Writer-native/mapped frame model when Writer owns an existing frame.

## `setImage()`

**Recommended · bounded classic convenience**

Immediately replaces a classic image placeholder such as `{{photo}}` with a generated ODT image frame.

```php
public function setImage(
    string $key,
    string $imagePath,
    array $options = []
): void
```

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `$key` | `string` | Placeholder name without braces. |
| `$imagePath` | `string` | Source image path. |
| `$options` | `array<string, string>` | Bounded image options below. |

### Options

| Key | Type | Default | Current behavior |
| --- | --- | --- | --- |
| `width` | `string` | none | Explicit width. With no height, derives height from intrinsic raster ratio. |
| `height` | `string` | none | Explicit height. With no width, derives width from intrinsic raster ratio. |
| `anchor` | `string` | `paragraph` | Written directly as `text:anchor-type`; this facade does not validate the value. |
| `wrap` | `string` | `none` | Only `left`, `right`, or `parallel` cause the historical wrap child to be emitted. |

If neither dimension is supplied, the historical fallback is `5cm × 3cm`.

One-dimensional autosizing uses the current historical centimetre conversion and is not unit-aware. Use a numeric `cm` value when relying on proportional derivation.

Unknown option keys are ignored.

### Lifecycle and side effects

`setImage()` is immediate; it does not require `render()`. The source file is copied to `Pictures/<basename>` in the working package and replacement is attempted in both content and styles.

The placeholder matching is intentionally narrow: it targets a paragraph whose direct text contains the placeholder, and replaces the **entire paragraph** with the generated image paragraph. Do not place other content in a paragraph that is intended for `setImage()`.

If no placeholder matches, no not-found exception is thrown; the resource may already have been copied to the working package.

A missing source path throws a generic `Exception`. The facade does not define an additional stable validation contract for unreadable or invalid image data beyond underlying filesystem/image handling.

```php
$template->setImage('photo', '/path/to/photo.png', [
    'width' => '4cm',
    'anchor' => 'paragraph',
    'wrap' => 'none',
]);

$template->save('output.odt');
```

## `replaceImageByName()`

**Compatibility**

Replaces the image reference of existing Writer frames selected by `draw:name`.

```php
public function replaceImageByName(
    string $name,
    string $imagePath,
    array $options = []
): void
```

### Options

| Key | Type | Default | Current behavior |
| --- | --- | --- | --- |
| `width` | `string` | `5cm` | Replaces frame width. |
| `height` | `string` | `3cm` | Replaces frame height. |

The defaults are applied before the historical proportional branches. Therefore supplying only `width` still uses height `3cm`, and supplying only `height` still uses width `5cm`. Do not expect one-dimensional proportional sizing from this compatibility API.

Unknown option keys are ignored.

### Compatibility behavior

- Missing image path: generic `Exception`.
- No matching named frame: no dedicated not-found exception.
- Duplicate same-name frames in one document part: all matches are updated.
- A matching frame without a direct `draw:image`: dimensions may change, but no image child is created.
- Existing frame style, anchor, z-index, and unrelated state are retained.

This behavior is deliberately different from the typed/mapped Writer-native frame replacement semantics. The APIs are not aliases.

## See also

- Structured Content -> Images for `ImageElement`
- Writer-native Objects -> Frames
- Mapping & Automation -> native `replace-image`

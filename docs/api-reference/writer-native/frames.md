# Frames

A Writer-native frame is a named `draw:frame` that already exists in the Writer-authored document.

The 1.0 imperative `FrameTarget` API deliberately provides **identity and inspection only**. It does not expose a general frame editor or an imperative `replaceImage()` method.

## Resolve a frame

**Recommended for identity and inspection**

```php
public function frame(string $name): FrameTarget
```

```php
$frame = $template->frame('PortraitFrame');
$descriptor = $frame->descriptor();

echo $descriptor->payloadType();
```

Resolution is strict across the inspected frame set:

- no match throws `TargetNotFoundException`;
- multiple same-named frames throw `AmbiguousAddressableTargetException`.

Frame discovery includes `content.xml` and supported Writer master-page header/footer content in `styles.xml`.

Targets are identity-backed handles rather than captured DOM nodes. Calling `descriptor()` resolves the current document state again.

## `FrameTarget`

```php
name(): string
type(): string
descriptor(): FrameDescriptor
```

`type()` returns `frame`.

There are no other public imperative mutation methods on `FrameTarget` in the 1.0 API.

## Descriptor

`FrameDescriptor` is the immutable inspection view:

```php
name(): string
documentPart(): string
payloadType(): string
width(): ?string
height(): ?string
containingSection(): ?string
diagnostics(): array
toArray(): array
```

The descriptor reports what inspection discovered. In particular, `payloadType()` describes the frame payload; it is not a capability promise that every discovered payload can be mutated.

## Image replacement is not a `FrameTarget` method

The engine has a bounded declarative native-frame image replacement in the Mapping & Automation pipeline. That operation is driven by an inspected template contract plus READY preflight and is documented with Phase-E native-object actions.

It must not be confused with an imperative call such as:

```php
// This API does not exist:
$template->frame('PortraitFrame')->replaceImage(...);
```

This distinction is intentional in the 1.0 reference. The public imperative frame surface is not expanded merely because the automation layer can execute one bounded frame action.

For PHP-owned newly constructed frames or images, use the Structured Content APIs such as `DrawTextBox` and `ImageElement`.

## Ownership

Writer owns the native frame structure.

`FrameTarget` gives PHP a stable typed identity and current descriptor without transferring structural ownership to PHP.

## See also

- [Inspection](inspection.md)
- [Writer-native Objects](index.md)
- [Structured Images](../structured-content/images.md)
- [Structured Frames & Text Boxes](../structured-content/frames-text-boxes.md)

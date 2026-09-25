# Frames & Text Boxes

Frames are one of the places where the ownership rule matters most. A frame can either be **created by PHP** as structured content or **authored in Writer** and kept under Writer ownership.

Ask first:

> **Who owns this frame — Writer or PHP?**

## PHP-owned frames and text boxes

Use Structured ODT Construction when PHP needs to create the frame itself. `DrawTextBox` creates a native ODT frame containing a text box, while `ImageElement` creates an image/frame as part of generated content.

```php
use OdtTemplateEngine\Elements\DrawTextBox;
use OdtTemplateEngine\Elements\Paragraph;

$box = (new DrawTextBox('Notice', [
    'background-color' => '#eeeeee',
    'padding' => '0.2cm',
]))
    ->setFrameLayout([
        'anchor' => 'paragraph',
        'width' => '7cm',
        'height' => '2.5cm',
        'horizontal' => ['alignment' => 'right', 'relative-to' => 'paragraph'],
        'vertical' => ['alignment' => 'top', 'relative-to' => 'paragraph'],
        'wrap' => 'left',
    ])
    ->addElement((new Paragraph())->addText('Important notice', ['bold' => true]));

$template->setElement('notice', $box);
```

Here Writer supplies an insertion point, but PHP owns the generated frame, its content, and its layout. For new positioning code, prefer `setFrameLayout()` or the focused `setFrame*()` methods over historical positioning helpers.

## Generated images use the same frame-layout model

`ImageElement` and `DrawTextBox` share the semantic frame-layout model. The main concepts are **anchor**, **size**, horizontal/vertical **placement**, and **wrap**.

An `as-char` frame participates in text flow like an inline object. Paragraph-, character-, or page-anchored frames are floating layout objects whose placement and wrapping interact with Writer's layout engine.

The exact accepted values and anchor/relation matrix belong to the [Frames & Text Boxes API Reference](../api-reference/structured-content/frames-text-boxes.md). For layout-sensitive documents, generate the ODT and inspect it in LibreOffice.

## Writer-owned named frames

When a frame already exists in the LibreOffice template and its position, dimensions, anchor, and surrounding layout should remain template-owned, use the Writer-native model.

```php
$frame = $template->frame('CompanyLogo');
$descriptor = $frame->descriptor();
```

The 1.0 imperative `FrameTarget` API provides strict identity and inspection. It deliberately does **not** provide:

```php
// Does not exist:
$template->frame('CompanyLogo')->replaceImage(...);
```

Do not transfer PHP-owned `DrawTextBox` or `ImageElement` semantics onto a Writer-owned frame merely because both materialize as ODT frames.

## Replacing an image in a Writer-owned frame

Two supported paths are distinct.

The historical template-level `replaceImageByName()` API remains a Compatibility path for direct replacement by frame name.

For the current semantic automation workflow, a named Writer frame can instead be an explicit `replace-image` native-object action. That action is validated against the TemplateContract, concrete image payload, and current Working Document during Concrete Preflight, then executed by the common atomic `automate()` invocation.

This bounded automation action does not add an imperative mutation method to `FrameTarget`.

## Choosing the ownership model

| Situation | Model |
| --- | --- |
| Writer already contains the designed frame | Writer-native Document Model |
| PHP creates a new text box | Structured ODT Construction / `DrawTextBox` |
| PHP creates a new image/frame | Structured ODT Construction / `ImageElement` |
| Application data replaces an image in a mapped named frame | Mapping & Automation native-object action |

A single document can use all of these where appropriate.

## Learn from C03

[C03 — Frame Layout](../../samples/sample_C03_frame_layout.php) is the canonical capability sample for PHP-owned frame layout. It demonstrates a floating paragraph-anchored text box, an inline `as-char` image, a right-aligned floating image, and explicit frame offsets.

For Writer-owned frame identity, start with [L09 — Native Objects](../../samples/sample_L09_native_objects.php). For mapped named-frame image replacement, use [C05 — Mapping & Automation](../../samples/sample_C05_mapping_automation.php).

## See also

- [Images](images.md)
- [Addressable Native ODT Structures](addressable-document.md)
- [Frames & Text Boxes API Reference](../api-reference/structured-content/frames-text-boxes.md)
- [Writer-native Frames API Reference](../api-reference/writer-native/frames.md)

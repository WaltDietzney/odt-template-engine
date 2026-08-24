# Images

The engine supports both template-level image replacement and programmatically generated `ImageElement` objects.

Choose the approach based on who owns the document structure:

- use template image replacement when LibreOffice already contains the intended image position;
- use `ImageElement` when PHP needs to create the image as part of a generated `RichText` or `Paragraph` structure.

## ImageElement

Create an image element from a local readable image file:

```php
use OdtTemplateEngine\Elements\ImageElement;

$image = new ImageElement(
    __DIR__ . '/assets/photo.png',
    [
        'width' => '4cm',
        'anchor' => 'as-char',
    ]
);
```

If only width or height is provided, the element calculates the other dimension from the source image aspect ratio. If neither is provided, the current fallback size is `5cm × 3cm`.

## Add an image to RichText

```php
$section = new RichText();
$section->addImage($image);

$template->setElement('photo', $section);
```

`RichText::addImage()` places the image inside a new paragraph.

For explicit inline composition, add the image directly to a paragraph:

```php
$paragraph = new Paragraph();
$paragraph
    ->addText('Profile photo: ')
    ->addElement($image);
```

## Common image options

The image API accepts ODT-oriented options such as:

```php
[
    'width' => '4cm',
    'height' => '4cm',
    'anchor' => 'as-char',
    'wrap' => 'none',
    'align' => 'center',
]
```

The implementation also supports lower-level ODF-oriented positioning properties for advanced cases.

For portable generated documents, start with simple sizing and `as-char` or paragraph-oriented placement before introducing absolute coordinates or complex wrapping.

## Template-level image replacement

When the document design already contains an image placeholder or named frame, prefer the template-level image APIs on `OdtTemplate`.

This keeps layout decisions in LibreOffice while PHP supplies the actual asset. It is often more predictable for letterheads, logos, signatures, or fixed profile-image positions than recreating the full frame geometry in PHP.

See Samples 05 and 06 for the existing image replacement and image-settings workflows.

## Image assets in the ODT package

Generated images are embedded into the ODT package under `Pictures/`, and the package manifest is updated so LibreOffice recognizes the asset.

This is one reason an image element is more than a visual placeholder: the engine must coordinate XML, package assets, styles, and manifest declarations.

## Images imported from HTML

The HTML importer can also create image elements. Local images and validated data images are supported by the import pipeline. Remote HTTP/HTTPS images are disabled by default and require explicit opt-in.

The HTML-import guide covers that security and temporary-asset lifecycle separately.

## Current limitations

Advanced image positioning is one of the areas where ODF semantics and LibreOffice layout behavior can become difficult to predict.

Known development areas include:

- more reliable advanced anchor/wrap combinations;
- absolute positioning and coordinate behavior;
- clearer interaction between frame styles and generated image styles.

For layouts requiring exact image geometry, a LibreOffice-designed frame with image replacement is often preferable to constructing every positioning rule programmatically.

Always inspect representative output in the target office suite when using advanced wrapping or positioning.

## Related samples

- Sample 05 — replace an image in an existing template structure
- Sample 06 — image settings and sizing
- Sample 16 — image placement combined with tabs/positioning options
- Sample 21 — profile image embedded in a generated CV section

See [How the Engine Works](../concepts/how-it-works.md) for the general template-first design principle.

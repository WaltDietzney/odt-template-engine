# RichText & Paragraphs

Template variables are ideal when LibreOffice already contains the document structure. `RichText` and `Paragraph` are the foundation for sections whose structure must be assembled in PHP.

A `RichText` object is a container. It can hold paragraphs, images, tables, lists, and other ODT elements. A `Paragraph` represents a native ODT paragraph and can contain differently styled text runs, line breaks, tabs, hyperlinks, and embedded elements.

## Build a generated section

Suppose the template contains:

```text
{{intro}}
```

Build the replacement in PHP:

```php
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichText;

$paragraph = new Paragraph();
$paragraph
    ->addText('Important: ', ['bold' => true])
    ->addText('this text remains editable.', ['italic' => true]);

$intro = new RichText();
$intro->addParagraph($paragraph);

$template->setElement('intro', $intro);
```

The result is native ODT content, not HTML pasted into the document.

## RichText as a document-section container

A `RichText` block can combine several kinds of content:

```php
$section = new RichText();

$section->addParagraph('Profile');
$section->addParagraph(
    'Experienced developer with a focus on document automation.'
);
$section->addBulletList([
    'PHP development',
    'OpenDocument generation',
    'Document workflows',
]);

$template->setElement('profile', $section);
```

For larger sections, build explicit `Paragraph`, `ListElement`, `ImageElement`, or `RichTable` objects and add them to the same container.

## Paragraph text styles

`Paragraph::addText()` accepts an inline text-style array:

```php
$paragraph = new Paragraph();
$paragraph
    ->addText('Normal text. ')
    ->addText('Bold text. ', ['bold' => true])
    ->addText('Colored text.', [
        'color' => '#12324a',
        'font-size' => '11pt',
        'font-family' => 'Arial',
    ]);
```

Common text options include:

```php
[
    'bold' => true,
    'italic' => true,
    'underline' => true,
    'color' => '#333333',
    'background-color' => '#fff4cc',
    'font-size' => '11pt',
    'font-family' => 'Arial',
]
```

These describe the text itself. Paragraph geometry is a separate concern.

## Paragraph styles

A paragraph style controls properties such as spacing, alignment, indentation, borders, padding, and line height.

```php
$paragraph = new Paragraph(null, [
    'margin-top' => '0.3cm',
    'margin-bottom' => '0.1cm',
    'text-align' => 'left',
    'line-height' => '110%',
    'border-left' => '2pt solid #12324a',
    'padding-left' => '0.2cm',
]);

$paragraph->addText('A styled paragraph.');
```

The important distinction is:

```text
Paragraph style
├── spacing
├── alignment
├── indentation
├── borders
├── padding
└── line height

Text style
├── font family
├── font size
├── bold / italic
├── underline
└── color
```

Keeping these responsibilities separate makes complex documents easier to reason about and produces cleaner ODF structures.

## Named paragraph styles

For a reusable semantic style, create a paragraph with a meaningful style name and register its definition through the advanced style API:

```php
use OdtTemplateEngine\Utils\StyleMapper;

StyleMapper::registerParagraphStyle('DocumentSectionHeading', [
    'margin-top' => '0.4cm',
    'margin-bottom' => '0.1cm',
    'border-bottom' => '1.5pt solid #12324a',
    'padding-bottom' => '0.03cm',
]);

$heading = new Paragraph('DocumentSectionHeading');
$heading->addText('Experience', [
    'bold' => true,
    'font-size' => '12pt',
]);
```

This pattern is useful in larger documents because generated ODT styles retain understandable names such as `DocumentSectionHeading` instead of being only implementation-generated identifiers.

Direct `StyleMapper` registration is an advanced API. The current registry is static process state, so applications generating multiple independent documents in one PHP process should avoid treating global registrations as document-scoped configuration. A future document-scoped style context is tracked in the project roadmap.

## Line breaks and tabs

A paragraph can contain native ODT line breaks and tabs:

```php
$paragraph = new Paragraph();
$paragraph
    ->addText('First line')
    ->addLineBreak()
    ->addText('Second line')
    ->addTab()
    ->addText('After tab');
```

For aligned tabular text, define tab stops:

```php
$paragraph = new Paragraph();
$paragraph
    ->addTabStopDefinition(4.0, 'left')
    ->addTabStopDefinition(10.0, 'right')
    ->addText('Item')
    ->addTab()
    ->addText('129.90');
```

Tabs are useful for smaller aligned text structures. Use native ODT tables when the content is genuinely tabular or needs stable cell structure.

## Hyperlinks

`Paragraph` can add native hyperlinks:

```php
$paragraph->addHyperlink(
    'Project website',
    'https://example.com',
    ['color' => '#1a5fb4', 'underline' => true]
);
```

## Adding arbitrary ODT elements

`RichText::addElement()` accepts an `OdtElement`:

```php
$richText->addElement($list);
$richText->addElement($table);
```

Images can also be placed inside paragraphs when inline placement is needed:

```php
$paragraph->addElement($image);
```

This compositional model is what makes `RichText` useful for larger generated regions.

## Recommended architecture

For a complex section, prefer a small rendering method that receives application data and returns a `RichText` block:

```php
function buildProfile(array $profile): RichText
{
    $content = new RichText();

    $heading = new Paragraph();
    $heading->addText('Profile', ['bold' => true]);
    $content->addParagraph($heading);

    $content->addParagraph($profile['summary']);

    return $content;
}

$template->setElement('profile', buildProfile($profile));
```

This keeps application data separate from ODT rendering and prevents the LibreOffice template from accumulating large amounts of presentation logic.

## Related samples

- Sample 07 — paragraphs and tabular lines
- Sample 09 — RichText blocks and paragraph styling
- Sample 14 — advanced tabs, margins, borders, and paragraph styles
- Sample 21 — large real-world document assembled from reusable RichText sections

Continue with [Lists](lists.md), [Tables](tables.md), and [Images](images.md) for structured child elements.

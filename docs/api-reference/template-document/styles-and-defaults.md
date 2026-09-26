# Styles & Document Defaults

The normal 1.0 style model is document-oriented:

- use friendly element options for local generated content;
- use `$template->styles()->defineParagraph()` for a reusable generated paragraph style;
- use `setDocumentDefaults()` for document-wide Writer defaults based on the `Standard` paragraph style.

Low-level style serialization helpers are not normal application API.

## `styles()`

**Recommended · Styles & Document Layout**

Returns the document-local style authoring facade.

```php
public function styles(): DocumentStyles
```

The returned facade resolves the current document context for every operation, so a retained facade remains attached to the owning `OdtTemplate` across `load()` boundaries.

```php
$styles = $template->styles();

$styles->defineParagraph('ReportHeading', [
    'font-size' => '14pt',
    'bold' => true,
    'margin-bottom' => '0.2cm',
]);
```

## `DocumentStyles::defineParagraph()`

**Recommended · Styles & Document Layout**

Defines a reusable named paragraph style in the current document.

```php
public function defineParagraph(string $name, array $options): void
```

The mixed option array is split into text and paragraph responsibilities before the native ODF style is materialized.

The complete shared text/paragraph option vocabulary will be consolidated with the Structured Content style-option reference in F2.3. Do not infer additional style families from this method: the current public document-style facade defines paragraph styles only.

## `setDocumentDefaults()`

**Recommended · Styles & Document Layout**

Convenience facade for applying document-wide Writer defaults through the native `Standard` paragraph style.

```php
public function setDocumentDefaults(array $settings): void
```

Equivalent document-style operation:

```php
$template->styles()->setDocumentDefaults($settings);
```

### Settings contract

The outer array recognizes two groups:

| Key | Type | Default | Meaning |
| --- | --- | --- | --- |
| `text` | `array<string, mixed>` | `[]` | Text properties for `Standard`. |
| `paragraph` | `array<string, mixed>` | `[]` | Paragraph properties for `Standard`. |

If either supplied group is not an array, `InvalidArgumentException` is thrown.

Existing authored properties are preserved unless the corresponding mapped property is explicitly supplied.

### Verified text options

The current text mapper recognizes the following friendly keys:

| Key | Value form / behavior |
| --- | --- |
| `bold` | truthy -> bold |
| `italic` | truthy -> italic |
| `font-weight` | non-empty ODF/CSS-style weight value |
| `font-style` | non-empty style value |
| `underline` | truthy -> single solid underline |
| `text-decoration` | non-empty value enables underline; `line-through` also enables strike-through |
| `text-line-through` | truthy -> strike-through |
| `color` | color value |
| `background-color` | background color value |
| `font-size` | size; named xx-small..xx-large map to fixed pt sizes |
| `font-family` | font family/name |
| `font-variant` | `small-caps` is recognized |
| `monospace` | strict `true` selects Courier New |
| `style:text-position` | text-position value such as sub/super form |

Native `fo:*` and `style:*` text attributes pass through the text mapper as an advanced compatibility escape hatch.

### Verified paragraph options

The current paragraph mapper recognizes these friendly properties for document defaults:

| Key | Value form / behavior |
| --- | --- |
| `margin-left`, `margin-right`, `margin-top`, `margin-bottom` | ODF length |
| `text-align` | alignment value |
| `text-indent` | ODF length |
| `line-height` | line-height value |
| `background-color` | color value |
| `keep-with-next`, `keep-together` | ODF-compatible value |
| `widows`, `orphans` | scalar count/value |
| `break-before`, `break-after` | ODF-compatible break value |
| `writing-mode` | writing-mode value |
| `padding-left`, `padding-right`, `padding-top`, `padding-bottom`, `padding` | ODF length |
| `border-left`, `border-right`, `border-top`, `border-bottom`, `border` | ODF border value |
| `number-lines`, `line-number` | scalar ODF-compatible value |

Native-prefixed paragraph attributes are an Advanced compatibility escape hatch only when they map to supported `fo:*` or `style:*` attributes. `setDocumentDefaults()` rejects mapped attributes outside those namespaces.

`setDocumentDefaults()` passes these two groups directly to the current mappers; it does not run the convenience-key normalization used by `defineParagraph()`. Therefore aliases such as `weight` and `align` are not valid document-default keys.

The generic `margin` convenience key and structured `tab-stops` value are **not** valid successful document-default contracts in the current implementation: their mapped form does not satisfy the scalar supported-namespace merge boundary. Use the four explicit margin keys; configure tab stops through the paragraph APIs documented with Structured Content.

### Lifecycle and ownership

Document defaults mutate the current working `styles.xml` immediately. They do not require classic `render()`.

If `Standard` does not exist, the engine creates the paragraph style in `office:styles`. Paragraph styles inheriting from `Standard` continue to use Writer's native style inheritance.

```php
$template->setDocumentDefaults([
    'text' => [
        'font-family' => 'Liberation Sans',
        'font-size' => '11pt',
    ],
    'paragraph' => [
        'line-height' => '120%',
        'margin-bottom' => '0.15cm',
    ],
]);

$template->save('output.odt');
```

## Limitations

The current document-style facade does not provide symmetric `defineText()`, table-style, frame-style, or page-style authoring methods. Do not infer those APIs from `defineParagraph()`.

## See also

- Styling guides for the conceptual style model
- Structured Content reference for shared text/paragraph option contracts

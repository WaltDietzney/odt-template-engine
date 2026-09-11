# TABLE-LAYOUT-01B — Public Style Semantics Correction

Status: ARCHITECTURE CORRECTION / SUPERSEDES RAW-QNAME PUBLIC EXAMPLES

Parent milestone: `TABLE-LAYOUT-01`

Related:

- `TABLE_LAYOUT_01B_ARCHITECTURE_SYNTHESIS.md`
- `TABLE_LAYOUT_01B_CODE_INSPECTION_RESOLUTION.md`

## 1. Purpose

This note corrects an important design mistake in the initial TABLE-LAYOUT-01B synthesis.

Examples such as:

```php
$table->setStyle([
    'table:align' => 'center',
    'style:rel-width' => '60%',
]);
```

must **not** be treated as the intended public semantic API of the engine.

Namespace-qualified ODF names such as `table:align`, `style:width`, `style:rel-width`, `style:row-height`, or `style:vertical-align` are serialization vocabulary. They belong at the ODF/materialization boundary, not in the normal application-facing semantic surface.

The existing ability to pass native prefixed keys through low-level style paths is retained only as a compatibility/advanced escape hatch. It must not define the design language of TABLE-LAYOUT-01.

## 2. Correct architecture boundary

The public/application-facing layer should express intent in engine vocabulary.

The ODF layer should translate that intent into native properties.

```text
APPLICATION / PUBLIC API
    width
    relative width
    alignment
    exact row height
    minimum row height
    vertical alignment

            ↓ semantic mapping

ODF MATERIALIZATION
    style:width
    style:rel-width
    table:align
    style:row-height
    style:min-row-height
    style:vertical-align
```

This preserves the project principle that the engine increasingly models ODT as a structured document model instead of requiring callers to author XML vocabulary manually.

## 3. Consequence for TABLE-LAYOUT-01

The Change Contract must not promote raw namespace-qualified keys as the normal way to author table geometry.

The preferred semantic direction remains dedicated intent-bearing APIs for the new geometry capabilities, for example:

```php
$table->setWidth('10cm');
$table->setRelativeWidth('60%');

$table->addRow($cells, ['row-height' => '2cm']);
$table->addRow($cells, ['min-row-height' => '2cm']);

$cell->setStyle(['vertical-align' => 'middle']);
```

The unprefixed row/cell option names are engine-level semantic keys. Their native ODF names are implementation details of the mapper/materializer.

For table placement/alignment, TABLE-LAYOUT-01B must not use `table:align` as public syntax. If alignment is included in the 1.0 table-geometry surface, it requires an engine-level semantic form (for example a dedicated setter or an unprefixed semantic option) and must be decided explicitly rather than leaking the ODF QName.

## 4. Compatibility versus design

Two facts must remain separate:

```text
Existing raw/native pass-through
    = compatibility / advanced escape hatch

Public semantic API
    = engine vocabulary without ODF namespace prefixes
```

Characterization tests that use native prefixed keys remain valid because they freeze existing compatibility behavior. They do not approve those keys as preferred public API.

Likewise, documentation may show native properties when explaining ODF evidence or materialization results, but application-facing examples must not present prefixed ODF keys as the semantic authoring interface.

## 5. Correction to prior 01B documents

Where the earlier synthesis or code-inspection note uses examples such as:

```php
$table->setStyle([
    'table:align' => 'center',
    'style:rel-width' => '60%',
]);
```

those examples are now to be read only as descriptions of the **existing low-level compatibility path**, not as proposed public semantics.

In particular, the earlier statement that a caller may explicitly switch to element-owned style mode with raw `setStyle([...])` before using a width convenience setter is not sufficient as a public design rule. The Change Contract must define behavior in semantic engine terms and keep the raw/native path secondary.

## 6. Open architecture question reintroduced

One bounded question is therefore intentionally reopened before TABLE-LAYOUT-01C:

> What is the semantic engine-level representation of table alignment/placement when a caller needs it together with explicit table width?

This must be resolved from existing API conventions and milestone scope. The answer must not be `table:align` in normal application code.

No production implementation is authorized by this correction.

# SR-05 Change Contract — Document-local Font Dependencies

## 1. Goal

SR-05 introduces document-local, semantically correct handling of ODF font-face dependencies.

A style that uses a font does not itself own the physical font-face declaration. Instead, a document-wide dependency is derived from its semantic style requirement and materialized in the required ODF document part.

The intended data flow is:

```text
Element
  ↓
StyleRequirement
  ↓
Font dependency discovery
  ↓
document-local dependency ownership
  ↓
font-face materialization
  ↓
content.xml / styles.xml
```

SR-05 therefore incrementally replaces the implicit font handling in the legacy `StyleWriter` with an explicit document model.

## 2. Empirical and normative basis

This contract is based on:

- ODF 1.4 style and font-face semantics;
- FONT-01 — named style using a non-default font;
- FONT-02 — Writer document base font represented through `Standard`;
- FONT-03 — derived named paragraph style with a font override;
- the current engine state at `6f3eae5`;
- the completed SR-1 through SR-4 slices.

The following relationship is treated as established:

```text
style:font-name
        │
        ▼
style:font-face/@style:name
        │
        ▼
svg:font-family
```

`style:name` of a font-face declaration is its document-internal reference identity. `svg:font-family` identifies the font family. The architecture must not assume that both values are identical.

FONT-03 demonstrates this with a structure equivalent to:

```text
style:font-name = Liberation Sans1
                         │
                         ▼
style:font-face
    style:name = Liberation Sans1
    svg:font-family = Liberation Sans
```

## 3. Semantic ownership

### Element

An `OdtElement` continues to describe only the semantics of its own content and styles.

An element:

- does not register fonts globally;
- does not write `style:font-face` declarations;
- does not decide between `content.xml` and `styles.xml`;
- does not own a package-wide font registry.

### StyleRequirement

`StyleRequirement` continues to describe a style definition or style reference.

It remains responsible for:

- style family;
- scope;
- document part;
- parent style;
- native ODF property groups.

A font-face declaration is not modeled as a style property.

`StyleRequirement` remains a style model and does not become a general dependency container.

### Document context

Font-face dependencies belong to the logical ODT document.

Their lifetime therefore follows `OdtDocumentContext`.

A document replacement or `replaceCoreDocuments()` must not transfer font dependencies from the previous document into the new document.

There must be no process-global current-document or font state for the new semantic path.

## 4. Font-face identity and font family

SR-05 must at minimum be able to distinguish semantically between:

```text
FontFace identity
Font Family
```

For example:

```text
identity = Liberation Sans1
family   = Liberation Sans
```

The concrete internal representation is an implementation decision, but it must not lose this distinction.

An engine-generated font-face identity may be identical to the font family if it is unique within the relevant document part and used consistently.

However, the architecture must not establish the invariant:

```text
fontFaceName === fontFamily
```

Existing ODT documents whose font-face identities differ from their font families must be respected.

## 5. Dependency discovery

Font dependencies are derived from semantically prepared ODF style properties.

Discovery therefore happens after convenience API mapping and must not reinterpret the original user options.

This preserves the SR-4B principle:

> Once a convenience option has been translated into native ODF semantics, it must not be reinterpreted as convenience input again.

In particular, SR-05 must not reinterpret:

```php
'font-family' => 'Liberation Sans'
```

when native ODF semantics have already been produced.

The relevant discovery layer is, for example:

```xml
<style:text-properties
    style:font-name="..."
    fo:font-family="..."/>
```

## 6. Document-part-specific dependencies

A font-face dependency is associated with the document part in which its font reference is required.

For example:

```text
StyleRequirement
    documentPart = styles.xml
    style:font-name = FontA

        ↓

styles.xml
    office:font-face-decls
        FontA
```

and correspondingly:

```text
StyleRequirement
    documentPart = content.xml
    style:font-name = FontA

        ↓

content.xml
    office:font-face-decls
        FontA
```

If the same font-face identity is required from both document parts, it may occur in both `office:font-face-decls` containers.

SR-05 does not introduce a blanket rule that every used font must always be declared in both core document parts.

LibreOffice serialization behavior and normative ODF requirements remain explicitly distinguished.

## 7. Existing font-face declarations are authoritative

As with existing styles:

> Existing document data is authoritative.

If a matching font-face declaration already exists in the relevant document part, SR-05 must not unnecessarily replace or duplicate it.

For example:

```xml
<style:font-face
    style:name="Liberation Sans1"
    svg:font-family="'Liberation Sans'"/>
```

must not be rewritten merely because the engine's legacy model would prefer:

```xml
<style:font-face
    style:name="Liberation Sans"
    svg:font-family="'Liberation Sans'"/>
```

SR-05 is not a normalization pass for foreign ODT documents.

## 8. Conflict semantics

Within the same document part, the same font-face identity must not silently represent different font families.

For example:

```text
FontFace "MyFont"
    → Liberation Sans
```

combined with:

```text
FontFace "MyFont"
    → DejaVu Serif
```

is a semantic conflict.

SR-05 must not silently resolve this conflict through first-wins or last-wins behavior.

Equivalent repeated requirements must be idempotent.

The exact exception or resolution mechanism is an implementation detail; the no-silent-conflict rule is contractual.

## 9. Materialization

Font-face materialization is treated as a separate responsibility.

In particular:

```text
StyleRequirementMaterializer
    → materializes styles
```

must not become:

```text
StyleRequirementMaterializer
    → styles
    → font discovery
    → font resolution
    → font-face materialization
```

This contract does not prescribe the exact class or service decomposition.

A small document-related responsibility is preferred over extending an existing God path.

## 10. Relationship to legacy StyleWriter

The existing `StyleWriter` remains compatibility infrastructure during migration.

Its current implicit assumption:

```text
style:font-name
        =
style:font-face/@style:name
        =
svg:font-family
```

is not adopted as future semantics.

SR-05 should take physical font ownership for the already semantically migrated paragraph/text style path without simultaneously migrating every legacy style family.

Non-migrated compatibility paths may continue to use `StyleWriter` temporarily, provided that the new document-local path is not physically materialized a second time or overwritten.

SR-05 therefore follows the same bounded-ownership strategy established by SR-4A.

## 11. Backward compatibility

SR-05 must not unintentionally change the public formatting API.

Convenience syntax such as:

```php
$paragraph->addText('Example', [
    'font-family' => 'Liberation Sans',
    'font-size' => '11pt',
]);
```

remains supported.

Native ODF properties remain available through the existing advanced/compatibility path.

Existing templates containing font-face declarations must continue to work.

Repeated `render()` / `save()`, document replacement, and legacy compatibility paths must not leak font state between logical documents.

## 12. Explicitly out of scope

The following topics are not part of SR-05:

- a public `setDefaultFont()` API;
- general document-wide style defaults;
- changing the semantics of Writer's `Standard` style;
- changing ODF `style:default-style`;
- font embedding;
- font files inside the ODT package;
- licensing checks for embedded fonts;
- general style inheritance redesign;
- graphic styles;
- table/table-cell style migration;
- D5F materialization lifecycle work;
- general cleanup or rewrite of `StyleWriter`;
- normalization of existing LibreOffice documents.

These topics must not be decided implicitly by SR-05.

## 13. Characterization and tests before behavior changes

Before the actual migration, tests must characterize the current behavior of the affected font paths.

The eventual SR-05 test matrix should cover at least:

1. one semantic style using one font;
2. multiple styles using the same font;
3. multiple different fonts;
4. font-face identity different from font family;
5. an existing font-face declaration in the template;
6. a font dependency originating only from `styles.xml`;
7. a font dependency originating only from `content.xml`, once such a semantic producer exists;
8. the same font required from both document parts;
9. idempotent repeated requirements;
10. conflicting same font-face identity with different families;
11. document replacement without dependency leakage;
12. legacy compatibility without duplicate physical declarations.

FONT-01 through FONT-03 are external LibreOffice/ODF references, not descriptions of current engine behavior.

## 14. Visual quality gate

Because font materialization can affect rendering, XML validity alone is insufficient.

For affected samples, the implementation must pass the established visual regression workflow:

```text
normal sample generation
        ↓
ODT
        ↓
LibreOffice headless
        ↓
PDF
        ↓
PNG
        ↓
whole-document comparison
against known-good baseline
```

A visual GO may only be issued after inspection of the actual rendered result.

Font family, font size, wrapping, line height, pagination, and resulting layout shifts must be considered.

## 15. Definition of Done

SR-05 is complete when:

- font-face identity and font family are internally distinguishable;
- semantic paragraph/text styles trigger their font dependencies document-locally;
- dependencies are handled per document part;
- existing font-face declarations are respected;
- equivalent requirements are idempotent;
- conflicting font-face identities are not silently resolved;
- the new semantic path is not additionally physically materialized by legacy `StyleWriter`;
- no process-global font state is required for the new path;
- document replacement does not leak font state;
- compatibility behavior outside the agreed migration scope remains intact;
- focused tests, integration tests, and the full `composer test` suite pass;
- PHP lint, `composer validate`, and `git diff --check` pass;
- relevant LibreOffice visual regressions pass against known-good baselines.

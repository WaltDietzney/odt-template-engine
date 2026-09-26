# TABLE-LAYOUT-01B — Table Style Scope Evidence

Status: EVIDENCE / PRE-CONTRACT

Parent milestone: `TABLE-LAYOUT-01`

Purpose: record the focused comparison between current `RichTable` element-owned table-style materialization and LibreOffice Writer-authored table geometry before TABLE-LAYOUT-01C freezes a Change Contract.

This document is descriptive. It authorizes no production change and does not yet decide the final public table-layout API.

## 1. Question

TABLE-LAYOUT-01A established the native Writer representation for concrete table geometry. A manual Sample 11 regression then showed that an engine-generated table using:

```php
$table->setStyle([
    'style:width' => '15cm',
    'table:align' => 'left',
]);
```

receives the requested native properties but does not behave like the equivalent Writer-authored narrow table.

The focused question is therefore:

> Is the remaining difference the property mapping, or the scope/document-part in which the element-owned table style is materialized?

## 2. Writer oracle

The TABLE-LAYOUT-01A combined Writer oracle records concrete table styles in `content.xml` automatic styles.

Examples include:

```xml
<style:table-properties style:width="9.999cm" table:align="left"/>
```

for an approximately 10 cm left-aligned table, and:

```xml
<style:table-properties
    style:width="10.199cm"
    style:rel-width="60%"
    table:align="left"/>
```

for a 60% relative-width table.

The already-existing repository Writer oracle `TABLE-02` likewise places its concrete table style in `content.xml` automatic styles.

Writer evidence therefore establishes this observed concrete-table pattern:

```text
family:        table
scope:         automatic
document part: content.xml
property group: style:table-properties
```

The evidence does not imply that all table styles must be automatic. Reusable/authored common table styles remain a separate valid concept.

## 3. Current engine path

Current `RichTable::setStyle()` stores element-owned table properties and generates a style identity locally. `RichTable::getOwnStyleRequirements()` then emits the element-owned table definition as:

```text
kind:          definition
family:        table
scope:         common
document part: styles.xml
property group: style:table-properties
```

The properties themselves are passed through unchanged.

Consequently the manual Sample 11 test with:

```php
$table->setStyle([
    'style:width' => '15cm',
    'table:align' => 'left',
]);
```

materializes the correct native property names, but in a common table style in `styles.xml` rather than an automatic table style in `content.xml`.

The visible Writer result remains inconsistent with the requested 15 cm narrow-table geometry.

## 4. Historical architecture contract

This current scope is not consistent with the governing SR-07 ownership rule.

`SR-07_SEMANTIC_TABLE_STYLE_REQUIREMENTS_CHANGE_CONTRACT.md` explicitly states:

```text
element-owned / generated style
    -> automatic style in the owning document part

authored / explicitly reusable style
    -> common style where that API or authored definition carries common-style semantics

reference only
    -> no fabricated definition
```

The same contract distinguishes `setTableStyleName()` as reference-only and permits a table definition only when an actual authored, registered, or element-owned definition source exists.

STYLE-API-02F had already established that `RichTable` element-owned generated definitions must be owned directly by the element rather than process-global `StyleMapper` registry state. It intentionally deferred the exact public authoring method name, but not the ownership distinction between named reference and element-owned generated definition.

Therefore the current implementation has the correct **owner** (`RichTable`) but appears to retain the wrong **scope/document-part** for the element-owned table definition.

## 5. Comparison matrix

```text
                               Writer concrete table     Current RichTable::setStyle()
--------------------------------------------------------------------------------------
family                         table                     table
property group                 style:table-properties    style:table-properties
style:width                    correct                   correct
style:rel-width                correct when requested    correct when requested
table:align                    correct                   correct
scope                          automatic                 common
document part                  content.xml               styles.xml
ownership                      concrete table            concrete RichTable
```

The remaining mismatch is therefore not a QName/property-mapping problem. It is a scope/document-part mismatch.

## 6. Architectural interpretation

The evidence supports a sharper distinction:

```text
ELEMENT-OWNED GENERATED TABLE STYLE
    concrete RichTable instance owns geometry/presentation properties
    -> automatic/content.xml

AUTHORED / REUSABLE COMMON TABLE STYLE
    reusable document/template style definition
    -> common/styles.xml

TABLE STYLE REFERENCE
    RichTable references existing style identity
    -> no fabricated definition
```

This is not a new TABLE-LAYOUT ownership model. It is the model already approved by SR-07, now confronted with TABLE-LAYOUT Writer evidence.

The current `RichTable::setStyle()` common/styles.xml output should therefore be treated as a characterized implementation mismatch / compatibility residue, not as target architecture merely because existing samples still open successfully.

## 7. Compatibility boundary

No production correction is authorized by this evidence note.

Before changing `RichTable::getOwnStyleRequirements()`, TABLE-LAYOUT-01C must explicitly address compatibility for:

- existing `setStyle()` callers that currently generate common table definitions;
- existing `setTableStyleName()` reference semantics;
- explicitly authored/reusable common table definitions;
- repeated save/materialization behavior;
- existing SR-07 characterization tests that intentionally froze current behavior before TABLE-LAYOUT semantics were investigated;
- public samples, especially Sample 11.

A correction must not collapse common reusable table styles and element-owned generated table styles into one concept.

## 8. Pre-contract conclusion

The focused evidence gate is resolved:

```text
native table geometry properties      -> known
current engine property transport     -> correct
Writer concrete-table scope           -> automatic/content.xml
current element-owned engine scope    -> common/styles.xml
SR-07 governing ownership rule        -> automatic for element-owned/generated
```

TABLE-LAYOUT-01B should therefore carry this mismatch into architecture synthesis and Change-Contract preparation.

The next phase must decide the bounded migration/compatibility contract; it should not perform a broad style-system redesign.

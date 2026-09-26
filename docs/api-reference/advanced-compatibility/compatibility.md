# Compatibility & Deprecated API

The engine retains several historical public surfaces for 1.0 so existing applications are not forced through an unrelated migration during finalization.

New code should use the Recommended replacement unless the historical behavior itself is required.

## Classic assignment aliases

### `setValues()`

**Compatibility**

```php
public function setValues(array $values): void
```

It has the same staging behavior as:

```php
$template->assign($values);
```

Both merge values into the classic value stack for later `render()`; neither renders immediately.

**Preferred:** `assign()`.

### `setRepeating()`

**Deprecated · Compatibility**

```php
public function setRepeating(string $key, array $rows): void
```

It stages the same repeat stack entry as `assignRepeating()`.

**Preferred:**

```php
$template->assignRepeating($key, $rows);
$template->render();
```

The source explicitly marks `setRepeating()` deprecated.

### `setRepeatingData()`

**Deprecated · Historical Compatibility**

```php
public function setRepeatingData(array $data): void
```

This is **not** another alias for `assignRepeating()`.

It immediately repairs template variables and mutates the current `content.xml` and `styles.xml` through an older direct foreach implementation. It does not stage `repeatStack` data and does not require a later `render()`.

The current repository has no focused characterization test or current caller establishing this older algorithm as a recommended behavioral contract. It is retained for compatibility, but intentionally hidden from normal learning documentation.

**Preferred:** `assignRepeating() + render()` for classic templates, or the appropriate Writer-native/Mapping workflow when Writer owns the repeated structure.

## Legacy structured values through `assign()/render()`

Passing an `OdtElement` through the classic value stack remains compatibility behavior:

```php
$template->assign([
    'content' => $richText,
]);

$template->render();
```

This is not semantically equivalent to:

```php
$template->setElement('content', $richText);
```

`setElement()` is the Recommended structured-content lifecycle and owns semantic state/resource preparation before bounded materialization.

The classic structured-value path is retained because existing applications can depend on its historical content/styles processing. New structured-content code should use `setElement()`.

## `refresh()`

**Compatibility lifecycle**

```php
public function refresh()
```

The name is easy to misread.

Current characterized behavior performs its historical graphic/font finalization/persistence sequence and then calls the reset boundary. The resulting Working Document is restored from the **original template source**.

It therefore does **not** mean “reload while preserving my current rendered/mutated state.”

If you need an explicit reset, prefer reasoning in terms of `load()`; do not use `refresh()` as a persistence operation.

## `extractTemplateVariables()`

**Compatibility / tooling**

```php
public function extractTemplateVariables(): array
```

Returns the historical current-Working-DOM extraction shape:

```text
variables
loops
conditions
negated_conditions
filters
filter_options
```

It parses serialized current `content.xml` and `styles.xml` using the classic expression grammar.

It provides no semantic scopes, provenance, native-object model, capability readiness, or structured diagnostics.

For new tooling that needs to understand the authored template, prefer:

```php
$template->inspectTemplate();
```

Use `inspectTemplateStructure()` instead when the task is specifically physical classic-expression topology/normalization diagnostics.

## Imperative `replaceImageByName()`

**Compatibility image replacement**

```php
public function replaceImageByName(
    string $name,
    string $imagePath,
    array $options = []
): void
```

This historical named-frame replacement remains supported, but its semantics differ materially from mapped Writer-native `replace-image`.

Its compatibility defaults are:

| option | type | default |
| --- | --- | --- |
| `width` | string | `5cm` |
| `height` | string | `3cm` |

Unknown option keys are ignored.

Supplying only one dimension does **not** request proportional sizing: the other historical default still applies.

Other compatibility behavior includes:

- missing image path throws a generic `Exception`;
- no dedicated not-found exception for a missing named frame;
- duplicate same-name frames in a document part are all updated;
- a matching frame without direct `draw:image` can have dimensions changed without creating an image child;
- unrelated frame state is retained.

Do not infer these rules for mapped Phase-E Frame replacement. The mapped action preserves authored dimensions when none are supplied and can derive the second dimension from the replacement image ratio when exactly one dimension is supplied.

See [Template & Document Images](../template-document/images.md) and [Automation](../mapping-automation/automation.md).

## Metadata author aliases

The imperative metadata API accepts:

```text
author          -> creator
initial_author  -> initial_creator
```

These aliases are retained Compatibility vocabulary. New code should use the canonical `creator` and `initial_creator` keys.

## Paragraph and RichText compatibility helpers

The structured model retains some historical helpers because existing callers/importer code can depend on them.

Notable examples established by the API audit include:

- `Paragraph::addPHyperLink()` — historical hyperlink helper;
- historical Paragraph tab helpers where their literal behavior is required;
- `RichText::applyParagraphStyleOptions()` and `applyTextStyle()` mixed convenience helpers;
- `RichText::getImageAssets()` — historical, non-transitive image collector;
- `RichText::popLastElementIfList()` — importer compatibility helper.

These should not be used to infer a second styling/resource architecture. Prefer the normal Paragraph/RichText authoring methods and semantic ownership/resource traversal documented by the Recommended/Advanced contracts.

A known 1.0 limitation remains around styled `Paragraph::addHyperlink()` style registration. It is documented as current behavior rather than silently repaired during finalization.

## Infrastructure/compatibility facade methods

Some public or protected methods remain observable for subclass or historical integration compatibility even though they are not normal application calls.

Examples include protected `OdtTemplate` processing wrappers and template-preparation helpers such as `ensureParagraphStylesExist()`. Refactoring must respect established polymorphic dispatch where characterized, but these methods are not taught as normal 1.0 authoring API.

`ensureDefaultListStylesForContentXml()` and the many public implementation services/value carriers classified as Infrastructure are likewise not promoted into the user reference.

## Retired APIs are not compatibility APIs

The current source intentionally retired earlier architecture such as:

- `HasStyles`;
- `LegacyStyleRegistry`;
- mutable StyleMapper paragraph/text registries and their old getters;
- process-global paragraph/text style fallback;
- the old generic protected `registerStyles(array)` path.

Historical documentation or samples mentioning them do not make them part of the 1.0 compatibility surface.

## 1.0 compatibility principle

Compatibility in 1.0 means **preserving established behavior where required**, not presenting every historical method as equally good design.

The normal documentation therefore teaches the Recommended API first and keeps this page available for migration, existing integrations, and exact historical behavior.

# ARCH-07E — Processing and Structured-Facade Migration

## 1. Status

ARCH-07E is the second production-code slice of ARCH-07. It moves the
processing/structured coordination that is required by the concrete template
facade out of `AbstractOdtTemplate` and into `OdtTemplate`.

This slice does not remove the base class, change state ownership, redesign
styles, or migrate `PageLayoutOdtTemplate`.

## 2. Pre-change responsibility map

Before this slice, the following processing paths were implemented by
`AbstractOdtTemplate`, although every active caller was `OdtTemplate` (or a
subclass of it):

```text
render()/setElement()/foreach processing
        ↓ inherited implementation
AbstractOdtTemplate
        ├── scalar/structured value coordination
        ├── placeholder repair callback
        ├── structured placeholder replacement callback
        └── row-local placeholder replacement
```

The actual algorithms were already separated. `TemplateProcessor` owned
stateless template-language operations, and
`StructuredElementMaterializer` owned ODF subtree insertion. The remaining
problem was the location of the facade callbacks and orchestration.

## 3. Selected migration cluster

The following protected methods now belong directly to `OdtTemplate`:

- `setValuesInDom()`;
- `fixBrokenVariables()`;
- `replacePlaceholderWithDom()`;
- `hasPlaceholder()`;
- `replacePlaceholdersInNode()`;
- `replaceInText()`.

This is one bounded cluster because `setValuesInDom()` coordinates scalar and
structured assignment, while the other methods are the callback and
row-substitution seams reached by the same public rendering and structured
insertion workflows.

## 4. Why the cluster belongs to `OdtTemplate`

These methods are not an abstract template contract. They are invoked by the
concrete facade's `render()`, `setElement()`, and repeating-data paths and use
facade-owned dispatch (`$this`) to preserve subclass behavior. Moving them
makes that ownership explicit without making the facade own the underlying
algorithms or document state.

The migration is not a copy of the base class. The moved code is narrow
coordination and compatibility dispatch; style helpers, lifecycle helpers,
DOM mirrors, and unrelated legacy methods remain outside this slice.

## 5. Previous call chains

### Scalar and structured values

```text
OdtTemplate::render()
  → AbstractOdtTemplate::setValuesInDom()
  → TemplateProcessor::replaceScalarText()
  → AbstractOdtTemplate::applyFilter()

  → AbstractOdtTemplate::replacePlaceholderWithDom()
  → StructuredElementMaterializer::replacePlaceholder()
```

### Structured insertion callbacks

```text
OdtTemplate::setElement()
  → StructuredElementMaterializer::insert()
  → AbstractOdtTemplate::fixBrokenVariables()
  → AbstractOdtTemplate::replacePlaceholderWithDom()
  → AbstractOdtTemplate::hasPlaceholder()
```

### Foreach row replacement

```text
OdtTemplate::applyRepeatingInDom()
  → TemplateProcessor::applyRepeatingInDom()
  → AbstractOdtTemplate::replacePlaceholdersInNode()
  → AbstractOdtTemplate::replaceInText()
```

## 6. Resulting call chains

### Scalar and structured values

```text
OdtTemplate::render()
  → OdtTemplate::setValuesInDom()
  → TemplateProcessor::replaceScalarText()
  → OdtTemplate::applyFilter()

  → OdtTemplate::replacePlaceholderWithDom()
  → StructuredElementMaterializer::replacePlaceholder()
```

`setValuesInDom()` continues to separate `OdtElement` values from scalar
values. Structured values are not taught to `TemplateProcessor`.

### Structured insertion callbacks

```text
OdtTemplate::setElement()
  → StructuredElementMaterializer::insert()
  → OdtTemplate::fixBrokenVariables()
  → OdtTemplate::replacePlaceholderWithDom()
  → OdtTemplate::hasPlaceholder()
```

The materializer still owns inline/block replacement rules. The callbacks
continue to dispatch through the facade instance.

### Foreach row replacement

```text
OdtTemplate::applyRepeatingInDom()
  → TemplateProcessor::applyRepeatingInDom()
  → OdtTemplate::replacePlaceholdersInNode()
  → OdtTemplate::replaceInText()
```

The existing row-local substitution semantics are unchanged. Foreach
processing was not redesigned or merged with scalar processing.

## 7. TemplateProcessor boundary

`TemplateProcessor` remains stateless and owns the template-language
algorithms, including scalar replacement, filters, placeholder repair,
conditionals, repeating block mechanics, and normalization. The facade
supplies the filter and row-replacement callbacks where protected dispatch is
part of the existing behavior.

No generic processor, template context, or second mutable processing state was
introduced.

## 8. StructuredElementMaterializer boundary

`StructuredElementMaterializer` remains responsible for materializing and
replacing constructed ODF subtrees. `OdtTemplate` supplies orchestration and
compatibility callbacks only.

Structured image resource preparation remains package-backed. Named image
replacement remains a separate `TemplateTargetResolver` path; this migration
does not connect `setElement()` to `replaceImageByName()`.

## 9. Protected compatibility handling

The moved methods remain protected with their existing signatures. Existing
ARCH-06C subclasses overriding `fixBrokenVariables()`, `setValuesInDom()`,
`replacePlaceholderWithDom()`, and `replaceInText()` continue to be reached
through dynamic dispatch. The implementation calls callbacks via `$this` and
does not bypass overrides with direct service calls.

No protected hook was made private, removed, or renamed in this slice. Direct
external subclasses of `AbstractOdtTemplate` that relied on inheriting these
specific methods are a known pre-1.0 compatibility consideration for the
later base-class resolution; repository-internal subclasses extend
`OdtTemplate` and remain covered.

## 10. Foreach and row replacement

`replacePlaceholdersInNode()` and `replaceInText()` were migrated together
because they form the callback path for cloned foreach row content and for
the remaining legacy repeating path in `OdtTemplate`. Their behavior is kept
as characterized by ARCH-04, including replacement of missing row keys with
an empty string.

No change was made to `TemplateProcessor::applyRepeatingInDom()` or to the
separate text-based compatibility implementation.

## 11. State ownership

State ownership is unchanged:

```text
OdtPackage
    package/workspace/resources/persistence

OdtDocumentContext
    content.xml/styles.xml/meta.xml DOM state

OdtTemplate
    assignment/render-session state and facade orchestration
```

The historical mirrors `domContent`, `domStyles`, `domMeta`, `templatePath`,
and `tempDir` remain in place. No new DOM, package state, or context object was
introduced.

## 12. Style and resource boundaries

Existing style preparation remains where it was. `StyleMapper`, `StyleWriter`,
default styles, `injectImageStyles()`, and `registerStyles()` were not
redesigned. `STYLE-CONTEXT-01`, `STYLE-API-02`, and `ASSET-CONTEXT` are not
part of ARCH-07E.

`ensureTableCellStyleNodesExist()` and other legacy/style helpers were not
repaired, removed, or newly characterized here.

## 13. Deliberately not migrated

This slice does not change:

- removal or renaming of `AbstractOdtTemplate`;
- `PageLayoutOdtTemplate` or `adjustBulletIndentation()`;
- DOM/path mirror removal or visibility;
- `normalizeTemplateDom()` extraction;
- conditionals, list, textbox, or image processing design;
- package lifecycle, `load()`, `refresh()`, or `save()` semantics;
- style ownership or static style state;
- legacy helper cleanup;
- public API names or signatures.

## 14. Test evidence

No new test was necessary. ARCH-07C identified sufficient characterization
for this bounded migration, and the existing tests exercise both public
flows and the relevant protected dispatch seams.

The focused migration suites passed before final validation, including ARCH-06
compatibility, ARCH-04 processing/control structures, ARCH-05 structured
insertion and image-resource behavior, lifecycle/API, PageLayout, and public
sample coverage.

## 15. Next-slice impact

ARCH-07E leaves the concrete facade owning the processing callbacks needed for
the next structural work, while the base still contains unrelated historical
responsibilities. The next slice should therefore address the remaining
protected/state coupling according to ARCH-07B, without treating this move as
permission to remove mirrors or migrate PageLayout and style ownership in the
same change.

Semantics before implementation.

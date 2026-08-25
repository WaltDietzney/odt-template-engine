# ARCH-03B Change Contract — Low-Coupling ODT Document Services

## Purpose

ARCH-03A identified a technical ODT document layer between template-language processing and the physical ODT package lifecycle.

ARCH-03B extracts the two clearest low-coupling services from that layer:

- metadata handling from `OdtTemplate`;
- page-layout handling from `PageLayoutOdtTemplate`.

The goal is to reduce inheritance-based implementation responsibility while preserving the current public API and LibreOffice output behavior.

This is an internal architectural extraction, not a feature expansion.

## Architectural intent

The intended dependency direction is:

```text
OdtTemplate facade
      │
      ├── MetadataManager
      │        └── OdtDocumentContext::metaDom()
      │
      └── existing template-language and document behavior

PageLayoutOdtTemplate compatibility facade
      │
      └── PageLayoutManager
               └── OdtDocumentContext::stylesDom()

OdtDocumentContext
      └── owned by OdtPackage
```

`MetadataManager` and `PageLayoutManager` are document-scoped technical services. They must not own ZIP/package lifecycle, template-language state, style registries, or application-wide static state.

## In scope

### MetadataManager

Extract the implementation currently behind:

```text
OdtTemplate::setMeta()
OdtTemplate::getMeta()
```

The manager should:

- operate on the current document's `meta.xml` DOM through `OdtDocumentContext`;
- register the existing `office`, `dc`, and `meta` namespaces;
- preserve all currently supported metadata keys;
- update existing nodes;
- create missing supported metadata nodes below `office:meta`;
- ignore unsupported keys exactly as the current implementation does;
- preserve the public return shape of `getMeta()`.

`OdtTemplate::setMeta()` and `OdtTemplate::getMeta()` remain public facade methods and delegate to the manager.

### PageLayoutManager

Extract the implementation currently behind:

```text
PageLayoutOdtTemplate::setPageMargins()
PageLayoutOdtTemplate::setPageLayout()
```

The manager should:

- operate on the current document's `styles.xml` DOM through `OdtDocumentContext`;
- resolve `style:master-page` to `style:page-layout-name` to `style:page-layout-properties`;
- preserve current supported layout keys;
- preserve current validation and exception behavior for empty values and invalid orientation;
- preserve the default master page name `Standard`;
- retain robust XPath literal escaping.

`PageLayoutOdtTemplate` remains source-compatible and delegates to this service.

### Document-context access

`OdtTemplate` may add a narrow protected accessor to the current `OdtDocumentContext` so subclasses such as `PageLayoutOdtTemplate` can construct document-scoped services without reaching into `OdtPackage` internals.

The accessor is a compatibility/internal seam, not a new application-facing feature.

Recommended shape:

```php
protected function documentContext(): OdtDocumentContext
{
    return $this->package->context();
}
```

If another equally narrow naming is more consistent with the codebase, it may be used.

### Constructor/load compatibility finding

Before ARCH-02, `OdtTemplate::__construct()` called the overridable public
`load()` method. Construction now initializes `OdtPackage` directly and
performs the same normal preparation without dispatching through an
overridable `load()` method. Public `load()` behavior itself remains
unchanged and continues to reset from the original source template.

An external subclass that specifically depended on constructor-time overriding
of `load()` would therefore observe a compatibility difference. This avoids
calling an overridable method from the constructor; ARCH-03B does not restore
that dispatch or introduce a new public hook.

## Explicitly out of scope

ARCH-03B must NOT implement or redesign:

- variables, filters, conditions, loops, or template normalization;
- `TemplateProcessor` extraction;
- structured element insertion;
- images or asset ownership;
- style consolidation or `StyleContext`;
- document-wide defaults;
- new page styles or master pages;
- first-page/following-page behavior;
- headers or footers;
- sections or semantic page breaks;
- pagination or HTML rendering;
- table/frame/image layout fixes;
- `AbstractOdtTemplate` removal;
- package lifecycle behavior established by ARCH-02.

## Public API compatibility contract

These calls must remain unchanged:

```php
$template = new OdtTemplate($path);
$template->setMeta([...]);
$meta = $template->getMeta();
```

and:

```php
$template = new PageLayoutOdtTemplate($path);
$template->setPageMargins('1cm', '1cm', '1cm', '1cm');
$template->setPageLayout([
    'page-width' => '29.7cm',
    'page-height' => '21cm',
    'orientation' => 'landscape',
]);
```

No public method is removed, renamed, or given different semantics in ARCH-03B.

## Behavioral invariants

### Metadata

- Existing metadata values remain readable.
- Supported metadata values can be updated.
- Missing supported metadata nodes can be created.
- Unsupported keys remain ignored.
- Metadata survives save/reopen.
- Existing Sample 04 behavior remains unchanged.

### Page layout

- Margins are written to the page layout referenced by the requested master page.
- Page width and height remain writable.
- Orientation remains limited to `portrait` and `landscape`.
- Empty supplied page-layout values remain invalid.
- Missing master page, missing page-layout reference, missing page layout, and missing page-layout-properties retain clear runtime failures.
- Existing `PageLayoutOdtTemplate` chaining via `static` remains source-compatible.

## Characterization tests required before/with delegation

### Metadata service coverage

Add focused tests for at least:

1. reading known metadata;
2. updating existing metadata;
3. creating a supported missing metadata node;
4. ignoring an unsupported metadata key;
5. public `OdtTemplate` metadata save/reopen behavior after delegation.

### Page-layout service coverage

Preserve the existing page-layout integration tests and add focused coverage for at least:

1. page margins;
2. page size and landscape orientation;
3. portrait orientation;
4. invalid orientation;
5. empty supplied option;
6. unknown master page.

Tests may exercise the manager directly through an `OdtPackage`/`OdtDocumentContext` where that provides clean characterization, while public-facade tests must continue to prove compatibility.

## Namespace decision

ARCH-03A noted repeated ODF namespace URIs. ARCH-03B may introduce canonical namespace constants only if doing so clearly reduces duplication between the extracted services without widening scope.

Do not introduce a general XML framework or utility class solely for this milestone.

## Page-layout bullet-indentation override

`PageLayoutOdtTemplate` currently overrides `adjustBulletIndentation()` even though the corrected base implementation now targets only `style:list-level-label-alignment` nodes.

ARCH-03B should verify equivalence but should not remove the override unless tests demonstrate that removal is behaviorally neutral and the change remains clearly within the extraction scope.

If there is any uncertainty, retain it and record it for later `AbstractOdtTemplate` reassessment.

## Completion criteria

ARCH-03B is complete when:

1. metadata DOM implementation lives in a focused document-scoped service;
2. page-layout DOM implementation lives in a focused document-scoped service;
3. `OdtTemplate` metadata methods delegate without API changes;
4. `PageLayoutOdtTemplate` delegates without API changes;
5. `OdtDocumentContext` remains the owner of `meta.xml` and `styles.xml` DOM state;
6. no package, template-language, style-context, image, or document-default redesign is mixed into the milestone;
7. focused tests are green;
8. the full PHPUnit suite is green;
9. public Samples 01–21 smoke tests remain green;
10. representative Sample 04 and Sample 21 output remains correct in LibreOffice;
11. `git diff --check` passes.

## Recommended implementation sequence

1. Add direct characterization tests for metadata and page layout.
2. Introduce `MetadataManager` and delegate public metadata facade methods.
3. Introduce `PageLayoutManager` and delegate `PageLayoutOdtTemplate` methods.
4. Add the narrow document-context access seam required by the compatibility subclass.
5. Re-run existing package/context tests to ensure ARCH-02 ownership remains intact.
6. Run focused tests and full PHPUnit.
7. Run public sample smoke tests.
8. Inspect Samples 04 and 21 in LibreOffice.
9. Perform final diff/architecture review before merging into `develop`.

## Success condition

ARCH-03B should make this statement true:

> Metadata and page-layout operations are technical ODT document services that operate on one `OdtDocumentContext`, while `OdtTemplate` and `PageLayoutOdtTemplate` remain stable public facades.

It should not yet make this statement true:

> All ODT document behavior has been extracted from the template classes.

That remains the work of subsequent architecture milestones.

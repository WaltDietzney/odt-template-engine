# ARCH-02 Change Contract — ODT Package / Document Context

## Purpose

ARCH-02 is the first implementation milestone after the responsibility audit.

Its goal is to separate physical ODT package and document-state handling from template-language rendering while preserving the current public API and behavior of `OdtTemplate` and `PageLayoutOdtTemplate`.

This is an internal architectural extraction, not a feature redesign.

## Architectural intent

Today `OdtTemplate` owns several responsibilities that belong to one document instance:

```text
template path
working directory
content.xml DOM
styles.xml DOM
meta.xml DOM
Pictures/
manifest.xml
ODT ZIP rebuild
cleanup
```

ARCH-02 introduces a focused owner for that state and lifecycle.

The implementation may use one class or a small pair of closely related classes. Working names such as `OdtPackage` and `DocumentContext` are allowed, but the implementation should prefer the smallest model that clearly owns the responsibilities below.

The extraction must leave room for later document-scoped state such as:

- document defaults (`DOCUMENT-DEFAULTS-01`);
- `StyleContext`;
- document-scoped assets;
- page/master-page state.

Those later features are NOT implemented in ARCH-02.

## In scope

ARCH-02 may extract and encapsulate the following responsibilities from `OdtTemplate`:

### Template package opening

- validate the source template path;
- create a unique temporary working directory;
- extract the source ODT ZIP package;
- load `content.xml`, `styles.xml`, and `meta.xml` as `DOMDocument` instances.

### Document XML ownership

The package/context becomes the single document-scoped owner of:

- `content.xml` DOM;
- `styles.xml` DOM;
- `meta.xml` DOM;
- temporary workspace path;
- original template path.

Existing template classes may expose or delegate access internally as required for compatibility during the transition.

### Package finalization

- serialize modified XML back into the temporary workspace;
- update image entries in `META-INF/manifest.xml`;
- rebuild the final ODT ZIP package;
- preserve the ODF requirement that `mimetype` is the first archive entry and stored without compression;
- include all remaining package files with their expected relative paths.

### Workspace cleanup

- recursively remove the per-document temporary workspace;
- preserve current shutdown cleanup behavior;
- make cleanup idempotent where practical.

### Characterization tests

ARCH-02 must add focused coverage around the extracted lifecycle rather than relying only on incidental sample behavior.

## Explicitly out of scope

ARCH-02 must NOT redesign or consolidate the following:

- variable replacement;
- filters;
- `nl2br` handling;
- `if` / `elseif` / `else` / `ifnot` processing;
- `foreach` processing;
- `assign()` / `assignRepeating()` / `render()` semantics;
- structured element insertion;
- `StyleMapper` static registries;
- `StyleWriter` responsibility;
- list-style behavior;
- image positioning APIs;
- `HtmlImporter`;
- metadata public API semantics;
- page-layout public API;
- `AbstractOdtTemplate` removal;
- document defaults;
- page styles, headers, footers, sections, or pagination;
- shared ODT/HTML document model.

If extraction reveals defects in those areas, record them separately and keep ARCH-02 narrowly scoped.

## Public API compatibility contract

The following normal usage must remain unchanged:

```php
$template = new OdtTemplate($templatePath);
$template->assign($values);
$template->assignRepeating('items', $rows);
$template->render();
$template->save($outputPath);
```

The following existing public behaviors must also remain available:

- constructor loads a usable ODT template immediately;
- `load()` remains callable;
- `save()` produces a valid editable ODT package;
- `cleanup()` remains callable;
- `setMeta()` / `getMeta()` keep the same behavior;
- `setImage()` keeps the same public behavior;
- `setElement()` keeps the same public behavior;
- `PageLayoutOdtTemplate` continues to work without API changes.

No public method should be removed or renamed in ARCH-02.

## Behavioral invariants

### Loading

Given a valid ODT template, construction must still result in loaded DOM documents for content, styles, and metadata.

Invalid/missing template paths must continue to fail clearly rather than creating a partially initialized object.

### ZIP/package structure

A saved document must contain at minimum:

```text
mimetype
content.xml
styles.xml
meta.xml
META-INF/manifest.xml
```

The `mimetype` entry must contain:

```text
application/vnd.oasis.opendocument.text
```

and must be stored without compression as required by the existing save path.

### XML persistence

Changes performed through the existing template API before `save()` must still be present in the corresponding XML files after the package is rebuilt.

### Images and manifest

Existing inserted images must still:

- appear below `Pictures/`;
- be referenced from document XML;
- have a corresponding manifest entry with the expected image media type.

ARCH-02 may move the manifest/package mechanics, but must not change image API semantics.

### Reload workflow

A document saved by the engine must remain loadable by a new `OdtTemplate` instance. Existing save/reload metadata behavior must remain green.

### Cleanup

Calling cleanup must remove only the current document's temporary workspace and must not affect generated output files or unrelated workspaces.

Repeated cleanup should not create new failures after the workspace has already been removed.

## Existing coverage to preserve

`OdtTemplateIntegrationTest` already verifies important cross-cutting behavior, including:

- valid ODT package generation;
- required package entries;
- expected mimetype value;
- rendered values and repeating data;
- well-formed `content.xml`, `styles.xml`, and `meta.xml`;
- metadata save/reload;
- image package and manifest persistence;
- native rich content, lists, tables, and HTML-generated structures.

The full public sample smoke suite must remain green after extraction.

## New ARCH-02 test plan

Add focused characterization/integration tests for at least the following.

### 1. Package lifecycle

Construct a template and verify the document context/package is usable through the unchanged public facade.

Do not expose new public testing-only APIs merely to inspect internals.

### 2. Save/reopen round trip

Modify a document, save it, instantiate a fresh `OdtTemplate` from the output, and confirm the package can be loaded again.

### 3. Mimetype ZIP contract

Inspect the produced ZIP and verify:

- `mimetype` exists;
- it contains the expected ODT media type;
- it is the first archive entry where the ZIP API allows this to be asserted reliably;
- the entry is stored rather than deflated where this can be inspected reliably.

### 4. Manifest preservation/update

Start with a representative template, add an image through the existing API, save, and verify the package contains both the asset and the manifest declaration.

### 5. Cleanup isolation

Create two document instances with independent workspaces. Cleanup of one must not invalidate the other.

If internal visibility makes direct workspace assertions inappropriate, characterize this behavior through public operations and a narrowly scoped test subclass only if necessary.

### 6. Multiple independent documents

Two documents generated in the same process must retain independent XML/package state. ARCH-02 should improve ownership without introducing cross-document package contamination.

This test does not attempt to solve the separate static `StyleMapper` contamination tracked by `STYLE-CONTEXT-01`.

## Extraction boundary

The preferred dependency direction is:

```text
OdtTemplate facade
      │
      ├── existing template/render behavior
      │
      └── package/context
             ├── workspace
             ├── XML documents
             ├── package assets
             ├── manifest
             ├── save/rebuild
             └── cleanup
```

The package/context must not call template-language operations such as conditions, loops, or filters.

Likewise, the template-language code should consume document DOM state rather than knowing how the ODT ZIP is extracted or rebuilt.

## DocumentContext vs OdtPackage decision rule

Do not introduce both concepts unless they have distinct responsibilities.

A single class is preferable if one object can coherently own:

- package workspace;
- core DOM documents;
- serialization/rebuild;
- cleanup.

Use two classes only if the implementation demonstrates a useful separation such as:

```text
DocumentContext
└── mutable document-scoped state

OdtPackage
└── physical ZIP/workspace I/O
```

Avoid introducing delegation layers whose only purpose is to make classes smaller.

## Compatibility bridge during extraction

`AbstractOdtTemplate` and `OdtTemplate` currently access protected DOM/workspace properties directly.

ARCH-02 may temporarily keep compatibility accessors/properties while delegating ownership to the new context. The goal is to establish the new ownership boundary without forcing ARCH-03/ARCH-04/ARCH-05 into the same change.

Any temporary bridge must be clearly documented as transitional architecture rather than a second permanent owner of the same state.

### Constructor/load compatibility finding

Before ARCH-02, `OdtTemplate::__construct()` called the overridable public
`load()` method. Construction now initializes `OdtPackage` directly and
performs the same normal preparation without dispatching through an
overridable `load()` method. Public `load()` behavior itself remains
unchanged and continues to reset from the original source template.

An external subclass that specifically depended on constructor-time
overriding of `load()` would therefore observe a compatibility difference.
This avoids calling an overridable method from the constructor; ARCH-02 does
not restore that dispatch or introduce a new public hook.

## Security preservation

The existing XML-loading behavior uses `LIBXML_NOENT | LIBXML_NOCDATA` and therefore assumes trusted ODT template input.

ARCH-02 must not silently change this security model as part of extraction. If XML parser hardening is desired, track it as a separate security change with dedicated tests and compatibility review.

ZIP extraction behavior should likewise not be broadened during this refactor.

## Completion criteria

ARCH-02 is complete only when all of the following are true:

1. Physical ODT package/document-state ownership has a clear extracted home.
2. `OdtTemplate` no longer directly owns the full ZIP/workspace lifecycle implementation.
3. The current public API remains compatible.
4. `PageLayoutOdtTemplate` remains compatible.
5. Existing integration and public sample tests remain green.
6. New focused package/context characterization tests are green.
7. Saved ODT files remain valid packages and reopen successfully.
8. Image assets and manifest entries remain correct.
9. Cleanup remains isolated and safe.
10. No template-language, style-context, page-structure, or document-default redesign is mixed into this milestone.

## Recommended implementation sequence

1. Add ARCH-02 characterization tests around package lifecycle and cleanup.
2. Introduce the smallest package/context abstraction that satisfies the ownership model.
3. Delegate constructor/load lifecycle to the new abstraction while preserving existing behavior.
4. Delegate save/rebuild/manifest/cleanup behavior.
5. Remove only package-specific duplication that is now demonstrably obsolete.
6. Run focused tests.
7. Run full PHPUnit suite.
8. Run public sample smoke tests.
9. Generate representative Samples 01, 04, 06, 18, and 21 and inspect them in LibreOffice.
10. Perform `git diff --check` and final architecture review before merging into `develop`.

## Manual regression set

At minimum verify:

- Sample 01 — basic template package and render/save lifecycle;
- Sample 04 — metadata save/reopen;
- Sample 06 — image/package/manifest behavior;
- Sample 18 — styles plus nested native list structures;
- Sample 21 — complex document and `PageLayoutOdtTemplate` compatibility.

## Success condition

ARCH-02 should make this statement true:

> `OdtTemplate` coordinates document generation, while one document-scoped package/context component owns the physical ODT package and its core XML state.

It should not yet try to make this statement true:

> the entire engine is fully composition-based and free of legacy inheritance.

That is the job of later architecture milestones.

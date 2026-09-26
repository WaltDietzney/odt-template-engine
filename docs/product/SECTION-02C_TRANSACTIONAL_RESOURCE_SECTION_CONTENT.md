# SECTION-02C — Transactional Resource-Bearing Section Content

## A. Goal

SECTION-02C extends `SectionTarget::replaceContent()` to resource-bearing
structured content where the existing package owner can preserve the
document/package atomicity contract. The first supported resource is an
`ImageElement`.

The section container remains in place; its children and required package
resource are committed as one bounded operation. Clone, instantiate, whole
section replacement/removal, and a general Asset Context remain out of scope.

## B. Existing resource pipeline

`ImageElement::toDomNode()` creates a `draw:frame` containing a
`draw:image` whose `xlink:href` points to `Pictures/<basename>`. The element's
`getImageAssets()` exposes the source file. `OdtPackage` owns the extracted
workspace, `Pictures/` files, manifest synchronization during save, and ZIP
creation.

Existing `setElement()` behavior remains unchanged. SECTION-02C uses the same
element resource description but does not route named image replacement through
`replaceImageByName()`.

## C. Transaction strategies considered

- Prepare without mutation: insufficient alone because package files still
  need a bounded commit and rollback path.
- Snapshot/rollback: workable, but would duplicate archive mechanics in the
  section service.
- Temporary complete package staging: stronger isolation, but disproportionate
  to the current package ownership and would introduce another package state.
- Bounded package preparation with rollback: selected. It validates all
  materialized content first, copies only required image files through
  `OdtPackage`, and removes files created by the operation if a later DOM step
  fails.

## D. Chosen transaction model

`SectionMutationService` now performs:

1. strict current-section resolution;
2. detached DOM materialization;
3. legal section-block, image, bookmark, and native-identity validation;
4. source-file validation and package resource preparation;
5. staged section replacement;
6. commit into the existing section node.

The package method tracks files created by this call. If resource preparation
fails part way through, those files are removed immediately. If the subsequent
DOM commit fails, the original section children are restored and the newly
created package files are removed.

The manifest is not edited during replacement. `OdtPackage::saveAs()` performs
the existing manifest synchronization after the document and package workspace
are ready. Thus a failed replacement cannot leave a new manifest entry.

## E. ImageElement support

`OdtTemplate::section()` passes its authoritative `OdtPackage` to the typed
section target. Image-bearing `OdtElement` content is accepted only through
that package-owned path. The generated frame may be unnumbered as a native
author-facing target; this is legal image content. If a frame has `draw:name`,
the existing SECTION-02B type-specific collision rules still apply.

The package resource path remains the existing basename-based
`Pictures/<basename>` convention. Reusing the same image is idempotent when
the existing bytes match. A different source with the same basename is
rejected rather than overwriting an existing package resource.

## F. DrawTextBox and mixed content

Resource-free `DrawTextBox` materialization remains within the existing
resource-free section green zone when its top-level result is a legal section
block. A text box containing an image is rejected if its element contract does
not expose the required package asset list. No new DrawTextBox semantics were
introduced.

`RichText` may combine paragraph and image materialization when all resources
are exposed and preparation succeeds. The whole replacement uses the same
transaction. An element that materializes an image but exposes no asset is
rejected before the section is changed.

## G. Resource paths and manifest

The implementation does not redesign path allocation or future deterministic
identity rewriting. It preserves the existing image basename behavior,
prevents conflicting overwrites, and avoids duplicate archive entries for
repeated use of identical bytes.

Successful save adds the required `Pictures/` manifest entry through the
existing package synchronization. Failed preparation leaves the manifest and
workspace resource set unchanged. Orphan cleanup is deliberately deferred:
global reference analysis is not required for package correctness and belongs
to later resource lifecycle work.

## H. Style boundary

`ImageElement` may register existing frame style information through the
current style machinery. Save/finalization remains responsible for writing
that state. No `StyleContext`, `AssetContext`, `StyleMapper`, or `StyleWriter`
redesign is part of SECTION-02C.

## I. Atomicity and cleanup

The original section children are cloned before the final DOM commit. Any
exception during that commit restores them. Resource preparation itself rolls
back files created earlier in the same operation when a later source is
invalid or unreadable. Existing matching files are never removed during
rollback.

Temporary transaction state is held in memory and in the existing package
workspace only; no additional persistent temporary resource directory is
created by the feature.

## J. Lifecycle and identity

The target remains identity-backed. It resolves and mutates against the
current `OdtDocumentContext`; the package reference is the same owning package
used by the facade. Section name, section node identity, parent, siblings and
attributes remain unchanged. Save/reopen keeps the section addressable and
the image reference resolves to an existing package file.

## K. Tests and package validation

Focused tests cover successful image replacement, manifest/resource output,
repeated resource use, and rollback after a later asset fails. Existing
SECTION-02B, structured-image, lifecycle, addressability and public sample
tests remain regression coverage.

Successful temporary ODT output is checked for ZIP integrity, XML well-formed
core parts, image existence, manifest declaration, and `content.xml` href
resolution. No tracked sample output or baseline is modified.

## L. Visual validation

The change is render-relevant. The agent environment retains the known
LibreOffice `javaldx`/`dconf` limitation, so pixel validation requires a local
run. Use a temporary output and the established renderer; do not overwrite a
baseline:

```bash
./tools/visual-regression/render-odt.sh /tmp/section-resource.odt
```

## M. Compatibility and limitations

Existing `setElement()`, named image replacement, structured insertion,
render/save/load/refresh, and page-layout behavior are unchanged. Resource
support is limited to assets exposed by the existing `OdtElement` contract and
does not provide automatic orphan cleanup, resource renaming, or transactional
style architecture.

## N. Future impact

The bounded package preparation path gives future section clone/instantiate
slices a concrete resource ownership seam. Identity rewriting, shared asset
policy, and broader package transaction semantics still require separate
design before those operations are implemented.

The next slice should not begin clone/instantiate until those identity and
resource policies are explicitly characterized.

# SR-06F.5A Document-Local Image Compatibility Adoption

## Previous leakage path

The legacy `assign()/render()` path registers image graphic definitions in the
static `StyleMapper` registry. During `save()`, the legacy image injector used
to iterate that entire registry. Consequently, a style registered while
rendering document A could be emitted into an unrelated later document B.

## New ownership path

The static registry remains a compatibility facade and is still populated by
`registerLegacyGraphicRequirements()`. Before legacy image finalization, the
current OdtTemplate now adopts only static image definitions whose
`draw:style-name` is referenced by the current content or styles DOM. The
current DOM is the document-local ownership boundary; unrelated static entries
are not copied into the document.

This also preserves direct legacy registration when the active document
contains a matching drawing-style reference.

## Compatibility and lifecycle

`injectImageStyles()`, `injectLegacyImageStyles()`,
`registerLegacyGraphicRequirements()`, and `setValuesInDom()` remain available
with their existing visibility and dispatch. Image style names, repeated
render registration, and repeated save idempotence are preserved. `load()` and
`refresh()` semantics are unchanged.

The static registry is intentionally not reset: context-free legacy callers
may still observe it. It is no longer an implicit source for physical image
styles in a document unless the current document references the style.

## Scope and non-goals

This slice changes only image-style adoption on the legacy save path. Frame and
fill-image channels remain unchanged, as do semantic graphic requirements,
StyleMapper APIs, protected compatibility hooks, `assign()/render()`,
`refresh()`, and physical resource handling. No broad legacy lifecycle or
StyleContext redesign is included. Frame and fill-image document-local bridges
remain follow-up work.

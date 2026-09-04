# SR-06F.5B Document-Local Fill-Image Compatibility Adoption

## Ownership paths

The normal `setElement()` CircularImage path remains semantic: its
`FillImageRequirement` is collected into the document-local registry, its
declaration is materialized by the existing semantic materializer, and the
resource/manifest is prepared by the package/resource path.

The legacy `assign()/render()` path still mutates CircularImage's historical
state and registers fill images in the static `StyleMapper` facade. During
legacy save finalization, only static fill-image entries evidenced by the
current document are now adopted.

## Leakage boundary

Previously `injectLegacyImageStyles()` iterated the complete static fill-image
registry. A declaration registered while rendering document A could therefore
be emitted into document B. Adoption now uses current-document evidence:
direct `draw:fill-image-name` references, or a current `draw:style-name` whose
registered legacy graphic definition contains `draw:fill-image-name`.
Unreferenced static history is not materialized.

The static API remains available and observable. Direct registration is
preserved when the active document contains the corresponding reference. No
static registry reset or hidden current-document pointer is introduced.

## Declaration and resource relationship

Existing target `draw:fill-image` declarations remain authoritative and are
not overwritten or duplicated. The bridge only selects declarations for the
legacy injector; physical resources remain governed by the existing package
and legacy lifecycle behavior. Semantic ownership is not routed through the
static registry.

## Scope and non-goals

This slice changes only legacy fill-image adoption. It does not change frame or
image handling, CircularImage geometry, semantic requirement production,
`assign()/render()` dispatch, public/protected APIs, refresh semantics, or
resource-copy architecture. Frame compatibility adoption remains follow-up
work.

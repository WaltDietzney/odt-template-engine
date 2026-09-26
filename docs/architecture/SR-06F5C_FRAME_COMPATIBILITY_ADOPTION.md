# SR-06F.5C Document-Local Frame Compatibility Adoption

## Two DrawTextBox paths

DrawTextBox can expose a semantic graphic `StyleRequirement` when its mapped
properties are semantically safe. It can also retain the historical frame
carrier identity when options require legacy compatibility. The drawing node's
`draw:style-name` is the physical evidence of which identity the current
document uses.

The legacy `assign()/render()` path continues to register frame definitions in
the static `StyleMapper` facade, and `StyleWriter` remains the compatibility
serializer. During legacy save, OdtTemplate now passes StyleWriter only the
static frame names referenced by the current content/styles DOM. Unreferenced
static history is not implicitly adopted by another document.

## Compatibility boundary

Semantic `setElement()` materialization remains document-local and is not
dependent on the static frame registry. Legacy carrier fallback, direct frame
registration, `assign()/render()`, and the protected compatibility hooks remain
available. Existing style names and mapper output are preserved for referenced
legacy definitions.

The StyleWriter API change is additive: its optional frame-name filter is only
used by the OdtTemplate legacy save boundary. Existing direct calls retain
their prior all-registry behavior.

## Scope

This slice changes only frame adoption. Image and fill-image adoption remain
the F.5A/F.5B paths; semantic graphic classification, geometry, placement,
resource handling, refresh/load semantics, and public/protected APIs are not
redesigned. This completes the three SR-06F.5 graphic compatibility-family
boundaries; later work may address broader lifecycle or carrier migration.

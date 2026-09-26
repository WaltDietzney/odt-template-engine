# SR-06F.2 Normal-Path Compatibility Boundary

## Purpose

SR-06F.1 showed that the normal `setElement()` path still needs several
legacy graphic channels, while semantic requirements are the document
generation authority. F.2 makes the remaining post-materialization graphic
registration explicit without removing or changing any channel.

## Boundary introduced

The post-materialization `StyleRequirementCollector::collect()` pass in
`OdtTemplate::setElement()` now keeps paragraph and text registration in its
existing orchestration block and delegates only the legacy graphic families
to the private method `registerLegacyGraphicCompatibilityState()`.

```text
StyleRequirementCollector::collect(element)
  -> paragraph/text: existing compatibility registration
  -> frame/image/fill-image:
       registerLegacyGraphicCompatibilityState()
  -> existing StyleContext raw graphic state
```

The method is deliberately private and narrow. It does not discover semantic
requirements, traverse elements, serialize XML, access `StyleMapper`, or
change materialization timing.

## Ownership and compatibility

- Semantic `StyleRequirement` and typed fill-image dependencies remain the
  document-generation authority for migrated producers.
- Legacy frame state remains required for current DrawTextBox compatibility
  carriers.
- Legacy image state remains required for the current ImageElement producer.
- CircularImageElement's legacy fill-image state remains registered on the
  normal path even though semantic fill-image ownership makes the physical
  duplicate registration redundant. Existing compatibility observation is
  intentionally preserved.
- The `assign()/render()` path and its separate `StyleMapper`/StyleWriter
  compatibility flow are unchanged.

No public or protected API was removed or changed. Existing raw
`StyleContext` registration methods, `injectImageStyles()`,
`injectLegacyImageStyles()`, `injectDocumentGraphicStyles()`, and StyleWriter
behavior remain available.

## Why no channel is removed

F.2 is a responsibility-clarity refactoring, not a migration or cleanup
slice. Existing tests continue to prove semantic DrawTextBox behavior,
legacy graphic carriers, ImageElement output, CircularImageElement graphic
and fill-image output, and nested/transitive requirements. Physical output and
registration state remain behavior-compatible.

## Deferred work

F.3 may separately address the legacy `assign()/render()` lifecycle, including
its two-DOM `setValuesInDom()` calls, static legacy registries, and StyleWriter
finalization flag. It must not be inferred from this extraction.

Potential removal or narrowing of the redundant normal CircularImage fill-image
registration, migration of ImageElement, and removal of legacy DrawTextBox
carriers all remain deferred until their compatibility contracts are audited.

## Explicit non-goals

F.2 does not:

- change semantic producer requirements or FillImageRequirement collection;
- remove CircularImageElement, ImageElement, or DrawTextBox legacy channels;
- change `assign()`, `render()`, `save()`, `refresh()`, or `load()`;
- modify StyleMapper, LegacyStyleRegistry, StyleContext compatibility APIs, or
  protected OdtTemplate hooks;
- alter XML materialization, output placement, resources, fonts, tables, or
  other style families;
- introduce concrete-element branching or a new coordinator abstraction.

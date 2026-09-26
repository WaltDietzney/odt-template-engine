# SR-06F.3 Legacy Structured Lifecycle Characterization

## Scope and baseline

This note characterizes the implementation after SR-06F.2 commit
`11ed28a1d7831e8149d1793fd03110cf3412b380` on
`architecture/sr-06f-compatibility-closeout`.

No production behavior is changed. The executable evidence is in
`tests/Integration/StyleContextLegacyStructuredLifecycleCharacterizationTest.php`
and the existing SR-06 lifecycle/graphic compatibility tests.

## Lifecycle diagrams

### Normal `setElement()`

```text
setElement(element)
  -> collectSemantic()
  -> document-local semantic StyleContext / dependency registries
  -> semantic materialization
  -> collect() after structured insertion
       -> StyleContext raw frame/image/fill compatibility state
  -> save()
       -> injectDocumentGraphicStyles()
       -> semantic dependency finalizers
       -> StyleWriter with legacy frame flag false
```

CircularImageElement's semantic fill dependency is collected before DOM
materialization. Its historical `getFillImageRequirements()` becomes available
only after `toDomNode()` sets `$fillImageName`; the later legacy collector then
registers a second, raw StyleContext fill-image representation.

### Legacy `assign()` / `render()`

```text
assign()/setValues()
  -> valueStack
  -> render()
       -> setValuesInDom(content.xml)
            -> toDomNode()
            -> registerLegacyGraphicRequirements()
       -> setValuesInDom(styles.xml)
            -> toDomNode()
            -> registerLegacyGraphicRequirements()
       -> legacyStructuredValuesMaterialized = true
  -> save()
       -> injectImageStyles()
            -> injectLegacyImageStyles()
       -> injectDocumentGraphicStyles()
       -> StyleWriter::writeAllStyles(..., true)
```

This path does not call `collectSemantic()` or `FillImageRequirementCollector`.
It writes legacy image/fill state through static `StyleMapper` registries and
legacy frame state through the static frame registry/StyleWriter path.

### `refresh()`

```text
refresh()
  -> injectDocumentGraphicStyles()
  -> semantic font finalization
  -> StyleWriter::writeAllStyles(..., false)
  -> persistCoreDocuments()
  -> load()
       -> reset workspace from original template
       -> replaceCoreDocuments()
       -> clear document-local registries
       -> legacyStructuredValuesMaterialized = false
```

Unlike `save()`, `refresh()` does not call `injectImageStyles()` and therefore
does not invoke `injectLegacyImageStyles()` for legacy structured values.

## Static registry ownership and lifetime

| State | Owner | Registration point | Reset point | Observed lifetime |
|---|---|---|---|---|
| `StyleMapper::$frameStyles` | process-global static facade | `registerLegacyGraphicRequirements()` during legacy render; direct legacy APIs | no supported reset in `OdtTemplate`, `load()`, or `cleanup()` | survives template load and can be read by later documents; consumed by StyleWriter when legacy frame finalization is enabled |
| `StyleMapper::$registeredImageStyles` | process-global static facade | `registerLegacyGraphicRequirements()` during each legacy DOM pass; direct registration APIs | no supported reset in `OdtTemplate`, `load()`, or `cleanup()` | survives documents and repeated operations; later legacy saves read all accumulated entries |
| `StyleMapper::$registeredFillImages` | process-global static facade | `registerLegacyGraphicRequirements()` after CircularImageElement fill-name mutation; direct registration APIs | no supported reset in `OdtTemplate`, `load()`, or `cleanup()` | survives documents and later legacy saves; name-keyed equivalent registration is idempotent |
| `LegacyStyleRegistry::$paragraphStyles` | process-global paragraph compatibility registry | `StyleMapper::registerParagraphStyle()` | no reset API | first-registration-wins process lifetime |
| `StyleContext` graphic arrays | document-local | normal `setElement()` legacy collector / explicit APIs | `OdtDocumentContext::replaceCoreDocuments()` -> `StyleContext::reset()` | isolated per document context; not the same as static StyleMapper state |
| `FillImageRequirementRegistry` | document-local semantic dependency registry | semantic fill collector | `replaceCoreDocuments()` reset | isolated and reset with core documents |

Registrations happen once for each `setValuesInDom()` pass, so a legacy
`render()` invokes the element's `toDomNode()` and registration twice. Repeated
`render()` repeats those calls again. Name-keyed registry assignment makes
equivalent results idempotent, but the element's rendering lifecycle remains
observable (notably CircularImageElement's mutable fill-image state).

There is no supported production reset mechanism for the static registries.
The characterization tests use separate PHP processes rather than inventing a
reset API or mutating production state.

## Producer observations

| Producer | Legacy render registrations | Current observation |
|---|---|---|
| DrawTextBox | `frame` registered during each DOM pass | Static frame state is retained; StyleWriter can materialize it when legacy save finalization is enabled. |
| ImageElement | `image` registered during each DOM pass | Static image state is retained and later legacy saves can materialize all accumulated image styles. |
| CircularImageElement | `image` and `fill-image` registered after each `toDomNode()` pass | `$fillImageName` is empty before rendering and populated by `toDomNode()`; subsequent legacy accessors expose the fill declaration. Static image/fill state is retained. |

The normal `setElement()` path uses document-local raw graphic state for the
legacy compatibility channel and does not register ImageElement styles into
the global image registry. CircularImageElement additionally has semantic
graphic/fill ownership, but the raw fill compatibility state remains visible.

## Save versus refresh matrix

| Operation | Legacy structured flag | Legacy image injector | StyleWriter legacy frame argument | Current result |
|---|---:|---|---:|---|
| `setElement()` -> `save()` | false | no-op | false | document-local normal graphic channels and semantic dependencies are finalized |
| `setElement()` -> `refresh()` | false | no-op | false | document-local graphic state is finalized, persisted, then `load()` resets pending state |
| legacy `assign()/render()` -> `save()` | true | called | true | legacy frame/image/fill declarations are physically materialized |
| legacy `assign()/render()` -> `refresh()` | reset before final post-refresh use | not called | false | legacy image/fill definitions are absent from the refreshed styles DOM; this is an observable asymmetry |
| `render()` -> `refresh()` -> `render()/save()` | cleared by `load()` | depends on subsequent lifecycle | false until a new legacy render sets it | previous legacy structured state is not preserved as the active flag after refresh |

The legacy save/refresh asymmetry is current historical behavior, not a policy
decision made by F.3. Existing template/document data and document-local
semantic state follow their own reset behavior; static legacy state remains
outside that lifecycle.

## Multi-document isolation

Document-local `StyleContext` and typed fill-image registries are isolated.
Static StyleMapper graphic registries are not. A legacy ImageElement rendered
in document A remains in the process-global image registry after A is loaded or
cleaned up. A later document B that uses the legacy `assign()/render()` path
causes the legacy injector to read both A's and B's image registrations, so A's
style can be physically emitted into B. The same ownership risk applies to
static fill-image and frame registries when their corresponding legacy
finalization path is active.

The leakage becomes physically observable during a later legacy `save()`; a
document B using only the normal `setElement()` path does not invoke the
legacy image injector merely because static state exists. This is why
document-local semantic isolation alone does not establish process-wide
legacy isolation.

## Classification of unresolved behaviors

### `REQUIRED_COMPATIBILITY`

- two-pass `setValuesInDom()` legacy registration for `assign()/render()`;
- legacy save finalization through `injectLegacyImageStyles()` and StyleWriter;
- CircularImageElement's post-`toDomNode()` `$fillImageName` compatibility state;
- public/protected legacy registration and finalization surfaces.

### `SAFE_TO_NARROW`

- no candidate is currently proven safe to remove in F.3 without separately
  preserving direct legacy callers; the normal-path registration boundary from
  F.2 is the prerequisite for future narrowing.

### `BUG_BUT_DEFER`

- static graphic registry state can leak into later legacy documents;
- legacy `assign()/render()` followed by `refresh()` does not run the legacy
  image/fill injector and therefore loses those definitions from the refreshed
  styles DOM.

### `UNKNOWN`

- whether external applications depend on cross-document static registry
  accumulation;
- whether any subclass relies on the precise number/timing of `toDomNode()`
  calls during render;
- whether refresh asymmetry is relied upon as a historical reset behavior;
- whether all frame/image/fill static channels leak identically under every
  mixed semantic/legacy sequence.

## F.4 proposed scope

F.4 should choose one narrowly specified compatibility policy for the static
  legacy graphic registries and prove it against direct legacy callers. The
  preferred investigation order is:

1. define whether legacy registries may remain process-global for compatibility
   or need demand-scoped adoption;
2. characterize frame, image, and fill-image leakage independently;
3. decide whether `refresh()` should preserve or intentionally discard legacy
   structured graphic definitions;
4. preserve protected hooks and public StyleMapper APIs through facades;
5. only then implement the smallest ownership/reset bridge.

F.4 must not silently reset global state, alter save/refresh parity, migrate
ImageElement, or remove CircularImageElement compatibility state without that
decision.

## Explicit non-decisions

F.3 does not:

- reset static registries;
- change `save()`, `refresh()`, `render()`, `assign()`, or `load()`;
- remove legacy graphic registration or materialization;
- migrate ImageElement or redesign CircularImageElement;
- change protected/public APIs;
- alter semantic StyleRequirement or D5 ownership traversal;
- touch tables, resources, fonts, page styles, samples, `tmp/`, or lock files.

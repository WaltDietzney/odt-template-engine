# SR-06E.2 — Document-Local Fill-Image Dependency Model

Status: implementation slice

Depends on:

- `SR-06_SEMANTIC_GRAPHIC_STYLE_REQUIREMENTS_CHANGE_CONTRACT.md`
- `SR-06E1_FILL_IMAGE_DEPENDENCY_CONTRACT.md`

## Purpose

SR-06E.2 introduces the document-local semantic model for named ODF `draw:fill-image` declarations without yet connecting producers or materializing declarations into `styles.xml`.

The slice deliberately separates declaration semantics from physical package resources.

## Model

A fill-image requirement represents:

- target document part: `styles.xml`;
- declaration identity: `draw:name`;
- declaration target: `xlink:href`.

It does not own:

- an absolute source image path;
- image copying;
- manifest entries;
- graphic style properties;
- drawing geometry or structure.

Those concerns remain with structured resource/package handling and semantic graphic styles respectively.

## Identity and conflicts

Registry identity is:

```text
(documentPart, draw:name)
```

Registration rules:

- equivalent repeated registration is idempotent;
- the same identity with a different `xlink:href` is a deterministic conflict;
- no process-global registry participates in semantic ownership.

## Document ownership and lifecycle

`OdtDocumentContext` owns one `FillImageRequirementRegistry`.

`replaceCoreDocuments()` resets that registry together with other document-local pending requirements. This prevents dependencies from leaking across `load()` / package-reset boundaries.

## Deliberate non-goals

SR-06E.2 does not:

- add a producer hook to `OdtElement`;
- change `CircularImageElement`;
- collect dependencies transitively;
- inspect existing target-document `draw:fill-image` declarations;
- materialize declarations;
- remove the legacy `StyleContext::$fillImages` path;
- alter `setElement()` orchestration.

Producer integration belongs to SR-06E.3. Target-document resolution and declaration materialization belong to SR-06E.4. Legacy reduction remains SR-06F.

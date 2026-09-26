# SR-05B — Document-local Font Dependency Model

## Chosen representation

`FontFaceRequirement` is a small immutable value object containing:

```text
documentPart   content.xml | styles.xml
fontFaceName   ODF style:font-face identity
fontFamily     ODF svg:font-family value
```

Font-face identity and font family are deliberately independent. The model
supports, for example, `Liberation Sans1` as the identity and `Liberation
Sans` as the family.

## Ownership

`OdtDocumentContext` owns one `FontFaceRequirementRegistry`. The registry is
document-local and is reset by `replaceCoreDocuments()`.

This location is preferred over extending `StyleContext`: font-face
dependencies are document-wide dependencies, not style-definition resolution
state. Keeping them beside the core document DOMs avoids turning
`StyleContext` into a generic dependency container and gives the dependency
state the correct document lifecycle.

## Registration identity and conflicts

The registry key is:

```text
documentPart + fontFaceName
```

Therefore:

- equivalent repeated requirements are idempotent;
- the same identity with a different family in one document part throws
  `FontFaceRequirementConflictException`;
- the same identity in `content.xml` and `styles.xml` is tracked separately;
- different identities may use the same family.

## Explicit non-goals

SR-05B does not:

- discover dependencies from `StyleRequirement`;
- inspect or reconcile existing `office:font-face-decls`;
- materialize `style:font-face` nodes;
- modify `StyleWriter`, `StyleMapper`, `StyleRequirementMaterializer`,
  Paragraph, RichText, or OdtTemplate;
- change save output or rendering;
- add font embedding, default-font behavior, or other SR-05/SR-5 work.

The registry is the semantic foundation for later discovery, resolution, and
materialization slices only.

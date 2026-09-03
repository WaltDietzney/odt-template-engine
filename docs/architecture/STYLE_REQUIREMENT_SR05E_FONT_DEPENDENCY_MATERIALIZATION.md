# SR-05E — Semantic Font Dependency Materialization

## Ownership and ordering

`FontFaceRequirementMaterializer` owns physical declarations for registered
semantic font dependencies. `OdtTemplate::save()` and `refresh()` materialize
those requirements before the existing `StyleWriter::writeAllStyles()` call.
The legacy writer remains enabled for non-semantic compatibility data. Its
existing-name check sees semantic declarations and therefore does not create
or rewrite them.

## Placement and serialization

The requirement document part selects the corresponding `content.xml` or
`styles.xml` DOM. Missing `office:font-face-decls` is inserted before the first
known style/body container (`office:styles`, `office:automatic-styles`,
`office:master-styles`, or `office:body`). Existing containers are reused.
Only `style:name` and `svg:font-family` are written, preserving identity and
family independently. Existing equivalent declarations remain untouched;
resolver conflicts propagate.

## Lifecycle and compatibility

The document-local registry accumulates requirements across `setElement()`
calls. Repeated save/materialization is idempotent, and document replacement
resets the pending registry. Unresolved and ambiguous references remain
non-fatal. Legacy StyleWriter APIs and their physical behavior remain
available outside semantic ownership.

The SR-05A assertion that newly generated semantic declarations collapse
identity and family is intentionally superseded for the migrated semantic path:
SR-05E now preserves the semantic family. Legacy specialized Writer behavior
remains characterized separately.

## Explicit non-goals

This slice does not migrate other style families, fonts as dependency models,
font embedding, or legacy writer internals. It does not add speculative font
attributes or change producer placement semantics.

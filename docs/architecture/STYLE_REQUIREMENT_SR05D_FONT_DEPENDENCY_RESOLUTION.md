# SR-05D — Semantic Font Dependency Resolution

`FontFaceRequirementResolver` performs read-only lookup of registered
`FontFaceRequirement` values against the current document DOMs. Identity is
matched by `style:font-face/@style:name`; `svg:font-family` is compared after
trimming whitespace and removing one matching outer quote pair.

The requirement's document part selects exactly one lookup path:
`styles.xml/office:font-face-decls` or
`content.xml/office:font-face-decls`. A matching declaration with an equivalent
family is `satisfied`; no matching identity is `missing`; an empty or
incompatible family is a `FontFaceResolutionConflictException`. Multiple
matching declarations are satisfied only when all families are equivalent.

The resolver never mutates declarations, creates containers, materializes
missing nodes, or consults legacy writer state. It has no cache, so replacement
of core documents naturally changes the lookup source. Physical materialization
remains a later slice.

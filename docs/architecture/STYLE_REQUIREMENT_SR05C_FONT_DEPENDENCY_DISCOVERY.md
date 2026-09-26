# SR-05C — Semantic Font Dependency Discovery

## Discovery input

`FontFaceRequirementDiscovery` consumes semantic `StyleRequirement` objects.
It considers only `definition` requirements and reads the existing native
`style:text-properties` group. References and legacy registries are not
consulted.

## Native values and family quotes

Both `style:font-name` and `fo:font-family` must be present and scalar. The
font-face identity is taken from `style:font-name`; the family is taken from
`fo:font-family`. Matching outer single or double quotes around the family are
removed, while the family value itself is otherwise preserved. Identity and
family remain independent.

## Document ownership and conflicts

The requirement's `documentPart` is passed directly to
`FontFaceRequirement`. Results are registered in the
`OdtDocumentContext`-owned `FontFaceRequirementRegistry`, which supplies
idempotence and same-part identity conflicts.

## Explicit boundaries

This slice does not inspect existing `office:font-face-decls`, consult legacy
state, mutate `StyleRequirement`, or materialize XML. Missing either native
font value produces no dependency. Physical font-face behavior remains the
existing `StyleWriter` behavior until a later slice.

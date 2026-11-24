# H04-B — Table-column identity change contract (proposal)

Status: **PROPOSED / not approved for implementation** (24 November 2026)

This contract follows H04-A characterization and compares strategies for
generated `table-column` style identity. It is a design boundary only. No H04-B
source change is included in this slice.

## Problem and invariants

Generated columns currently use document-global semantic identities `co0`,
`co1`, ... even though the meaning is local to one generated table definition.
Two tables with different geometry therefore conflict in the semantic path;
the direct-DOM path can instead emit duplicate same-name definitions. H04-B
must keep definitions and references unambiguous within one document while
preserving authored ODT data.

Required invariants:

- equal definitions are reusable;
- unequal definitions cannot silently overwrite one another;
- each emitted table-column reference resolves to exactly one effective
  definition;
- identity allocation is document-local, deterministic, and independent of
  process-global counters;
- repeated save and reload do not drift names or geometry;
- authored styles are never renamed or overwritten implicitly.

## Strategy comparison

| Strategy | Benefit | Risk / disposition |
| --- | --- | --- |
| Keep positional `coN` and reject collisions | Smallest change; preserves current semantic behavior | Cannot compose independently generated tables with different geometry; direct DOM remains ambiguous. Retain only as compatibility behavior for already-authored references. |
| Table-prefixed names | Easy to reason about and locally unique | Requires stable table identity, changes serialized names, and can leak construction order or table naming into style identity. Not preferred as the primary identity. |
| Process-global counter | Simple uniqueness during one process | Violates document-local determinism; names drift across requests, tests, and repeated lifecycle operations. Reject. |
| Hash of definition only | Equal definitions naturally deduplicate and different definitions separate | A definition hash alone does not express document ownership or authored-name collisions; canonicalization and a document-local allocator are still required. Useful as an identity seed, not a complete policy. |
| Definition-derived identity plus document-local collision handling | Stable for equal definitions, separates unequal definitions, and can reserve authored names without mutation | Requires canonicalization, a per-document registry, reference rewrite coordination, and explicit compatibility handling. Preferred H04-B direction. |

## Proposed identity model

For generated definitions, derive a stable seed from the tuple:

```text
family = table-column
semantic property group(s)
canonical property/value map
parent style identity, if applicable
document part and generated-style scope
```

The seed is not a public API name and must not include process state, object
memory identity, insertion order, or localized table names. The document-local
allocator then maps the seed to a valid ODF style name, reserving names in the effective family, scope and document part. Existing
automatic styles in the other part do not by themselves block a content identity.
Header/footer insertion and cross-part reference resolution must be explicitly
checked before expanding the current content-only generated requirement path.

Equal canonical definitions in the same document reuse one generated identity.
Different definitions receive distinct identities. If a derived candidate
collides with an authored style name, the allocator chooses a deterministic
document-local alternative; it does not modify the authored definition.

Existing authored `coN` references remain readable and are not globally
rewritten merely because H04-B is enabled. New generated references should use
the new identity policy. No public positional-name compatibility mode is proposed. Preserve public
method signatures and explicitly review the public StyleWriter helper and
standalone relative DOM reference-only behavior before changing either.

## Canonicalization

Canonicalization is limited to the generated column properties already emitted
by RichTable: absolute width strings and normalized relative width strings.
Use an unambiguous ordered tuple of family, scope, part, property name and
exact emitted value. Preserve existing validation and ratio normalization;
do not introduce unit conversion, default inference, whitespace rewriting or
a generic ODF canonicalization subsystem. Equal means equal emitted definitions,
not visually equivalent lengths. Compare the actual full definition before
reusing a candidate; a hash alone is not proof of equality. Resolve occupied
unequal candidates deterministically (e.g. stable suffix probing) within the
effective document scope. Do not persist allocation on RichTable objects.

Canonicalization must not erase distinctions that affect Writer geometry. In
particular, `style:column-width="5cm"` and
`style:rel-column-width="32766*"` remain different definitions even when a
particular table could render them at a similar width.

## Scope and ownership

The change is limited to generated `table-column` definitions and references
owned by `RichTable` and the existing semantic/direct-DOM integration points.
It does not redesign table width, relative ratio normalization, row/cell
styles, named table styles, or unrelated ODF style families.

Ownership remains:

- generated semantic definitions: automatic styles in `content.xml`;
- authored document styles: existing XML, authoritative and preserved;
- table-column references: the table serializer that emits each table;
- identity allocation and canonical definition registry: one document-owned
  collaborator shared by semantic materialization and any compatible direct
  DOM path;
- conflict diagnostics: the document/style boundary, with the table context
  included in actionable error information.

No process-global registry or `RichTable` static identity counter may be used
for column-style allocation. The existing static table display-name counter is
not a suitable source of style identity.

## Compatibility and lifecycle

H04-B must preserve:

- existing public column APIs (`setColumnWidths()` and
  `setColumnWidthRatios()`);
- authored `coN` definitions and references;
- equal-definition deduplication;
- deterministic geometry through direct DOM, structured insertion, repeated
  save, and reload;
- the current semantic conflict principle: no silent overwrite of unequal
  definitions.

The lifecycle contract is:

1. inspect authored definitions and reserve their identities;
2. collect generated definitions and canonicalize them;
3. derive/reuse document-local identities;
4. emit references and definitions from the same allocation result;
5. materialize once per effective definition;
6. save repeatedly without appending duplicates;
7. reload and preserve the serialized identity-to-geometry mapping.

Preserve the existing snapshot/restore boundary of appendBody and other
construction operations. For setElement, resolve and validate column identity
before emitting references; characterize existing partial registration behavior
and do not introduce a general transaction mechanism in H04-B. Genuine explicit
StyleContext same-identity conflicts remain errors. Do not silently rewrite
caller-supplied requirements.

## Writer gate

Before H04-B implementation is accepted, generate a document containing at
least two tables with different absolute widths, two tables with mixed
absolute/relative definitions, and equal-definition reuse. Open, save, close,
and reopen the result in LibreOffice Writer. The gate must verify:

- each table retains its intended column proportions/absolute widths;
- no table-column style reference is ambiguous;
- authored `co0` content remains intact;
- Writer may rename or consolidate internal styles; effective geometry and
  unambiguous references must survive the roundtrip;
- the document remains editable and opens without repair warnings.

The Writer gate is a required future H04-B acceptance step, not evidence that
H04-A has changed current behavior.

## Review gate

H04-B is not approved. First agree on the exact binding between collection and
serialization, including standalone DOM and public StyleWriter compatibility.
Use the existing document context where possible; no generic allocator framework
or new public API is required by this proposal.

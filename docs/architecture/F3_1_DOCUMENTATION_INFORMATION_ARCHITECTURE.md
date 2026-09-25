# F3.1 Documentation Information Architecture

## Status

**Accepted FINALIZATION-01 F3.1 working contract**

This document records the information-architecture audit and the user journey
for F3. It does not define new runtime architecture or change the Public API
1.0 contract established by F2.

## Evidence reviewed

The F3.1 audit used the current `develop` documentation and navigation,
including:

- `zensical.toml`;
- the documentation landing page and `How the Engine Works`;
- all Getting Started pages;
- Template Language guides;
- Rich Documents and Styling guides;
- user-facing Advanced guides;
- the Sample Guide and example guides;
- the completed 1.0 API Reference.

The canonical sample taxonomy is the L/C/B/S surface documented by the Sample
Guide and established by F1.

## User journey

The primary documentation journey is:

```text
What is the engine?
        ↓
Install it and generate a first editable ODT
        ↓
Choose who owns each piece of document structure
        ↓
Learn the corresponding working model
        ↓
Combine models where appropriate
        ↓
Use examples and canonical samples
        ↓
Consult the precise API Reference
        ↓
Use advanced/internal material only when needed
```

The three working models are:

1. **Simple Template Processing** — Writer owns the surrounding document
   structure; visible template expressions provide scalar values, filters,
   conditions, and lightweight repetition.
2. **Structured ODT Construction** — PHP owns a generated document subtree and
   constructs native ODT elements such as paragraphs, lists, tables, images,
   frames, and text boxes.
3. **Writer-native Document Model** — Writer owns named/native structure and PHP
   inspects, addresses, populates, clones, replaces, or automates only the
   bounded operations defined by the public API.

These models are complementary. A document may use all three. The decision is
made per piece of structure: **who owns it — Writer or PHP?**

Mapping/preflight/automation is an optional advanced workflow on top of the
Writer/template contract. It is not a fourth mandatory document lifecycle.

## Navigation contract

The public documentation navigation is organized in this order:

1. **Introduction**
2. **Getting Started**
3. **Simple Template Processing**
4. **Structured ODT Construction**
5. **Writer-native Document Model**
6. **Document Capabilities**
7. **Examples**
8. **API Reference**
9. **Advanced**

This deliberately moves the API Reference behind the learning guides. The
reference remains authoritative for exact signatures, options, lifecycle
semantics, compatibility, and limitations, but it is not the primary tutorial
path.

Architecture evidence under `docs/architecture/` and milestone/product
evidence remain available from the repository. They are not promoted into the
normal new-user navigation merely because they are important engineering
records.

The documentation edit link targets `develop`, the active development base,
rather than `master`.

## Existing strengths

The current documentation already provides much of the intended F3 story:

- the landing page and `How the Engine Works` already describe three
  complementary ownership approaches;
- Quick Start already uses the recommended template workflow;
- Creating Templates and the practical authoring guide already teach
  template-first ownership;
- the Sample Guide already defines the canonical L01–L12, C01–C05, B01–B02,
  S01b and S03/S03-B public taxonomy;
- the completed API Reference already distinguishes Recommended, Advanced,
  Compatibility, Deprecated, and hidden infrastructure surfaces.

F3 should therefore reconcile and retitle existing material where possible
rather than replace correct documentation wholesale.

## Reconciliation findings for F3.2/F3.3

The audit found bounded documentation inconsistencies that belong to the next
F3 slices:

- terminology still varies between “template expressions”, “programmatically
  generated ODT content”, “addressable native ODT structures”, and the final
  F3 working-model names;
- the documentation landing page still mentions historical numbered Samples
  21 and 25 as architecture evidence even though F1 moved legacy material out
  of the public sample surface;
- some guides incorrectly describe canonical L samples as “historical”,
  notably L07, L08, L10, and L11 references;
- the current guide layer has no dedicated user guide for the integrated
  mapping/preflight/automation workflow; the API Reference documents the exact
  API but does not replace a learning guide;
- Frames and Text Boxes have precise API Reference coverage but no equivalent
  user-facing guide alongside the existing Images guide;
- several “current limitations” passages need verification against the final
  1.0 API because some were written during earlier architecture milestones;
- README terminology and historical-sample references must be reconciled only
  after the guide layer is stable, as required by F3.4.

These are documentation/presentation findings, not evidence for new engine
capabilities.

## F3.1 completion gate

F3.1 is complete when:

- the public navigation reflects the user journey above;
- all existing user-facing pages remain reachable through a justified
  navigation category;
- API Reference remains complete and reachable without interrupting the
  tutorial progression;
- architecture evidence remains separate from normal user guidance;
- known reconciliation work is explicitly handed to F3.2/F3.3/F3.4 rather
  than silently mixed into F3.1;
- the documentation build passes.

No runtime behavior or public API semantics are changed by F3.1.

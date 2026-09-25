# FINALIZATION-01 Plan

## Status

**Planning baseline — pending review and acceptance**

This document defines the bounded work plan for **FINALIZATION-01**, the
repository and product finalization milestone between the completed
TEMPLATE-AUTHORING-01 work and the dedicated RELEASE-1.0 INTEGRATION
PRE-FLIGHT.

The plan is intentionally execution-oriented. Once accepted, it is the
controlling scope for FINALIZATION-01. New findings are classified against this
contract instead of being added to the milestone opportunistically.

## Goal

FINALIZATION-01 turns the completed 1.0 architecture and feature baseline into
a coherent, documented, externally usable **1.0 release-candidate state**.

The milestone does not establish new document architecture. Its purpose is to
finish the public sample surface, turn the completed API audit into usable
reference documentation, reconcile the user-facing documentation and project
presentation, verify distribution from a consumer perspective, and hand a
stable candidate to the separate release integration pre-flight.

The governing rule is:

> **FINALIZATION-01 does not invent new architecture.**

## Scope classification

Any new finding during FINALIZATION-01 must be classified before work is
started:

- **Defect against already accepted 1.0 semantics:** may be a FINALIZATION-01
  blocker. Characterize it first and apply only a bounded fix.
- **Documentation, presentation, packaging, or consistency defect:** fix within
  the appropriate FINALIZATION-01 slice.
- **New capability or architecture:** record in FUTURE_DEVELOPMENT and keep out
  of FINALIZATION-01.
- **Existing Compatibility or Deprecated API:** classify and document
  accurately; do not remove or redesign it merely as part of finalization.
- **Unrelated cleanup:** keep out of scope unless it directly blocks the
  release-candidate contract.

Refactoring and behavior changes must not be mixed merely for convenience.

## Execution model

FINALIZATION-01 is divided into six ordered blocks. A subsequent block does not
begin until the preceding block has passed its defined completion gate.

Within a block, the same rule applies where slices have an explicit dependency.
A slice may produce findings for later work, but it must not silently expand
its own scope.

The sequence is:

```text
F1  Canonical Sample Surface
 |
 v
F2  Public API 1.0
 |
 v
F3  Documentation & Learning Path
 |
 v
F4  Project Presentation & Support
 |
 v
F5  Distribution & Consumer Readiness
 |
 v
F6  Finalization Closeout
 |
 v
RELEASE-1.0 INTEGRATION PRE-FLIGHT
```

The normal development base remains `develop`. FINALIZATION work should use
focused branches/commits and return to `develop` only after the relevant
slice/block gate has passed.

---

## F1 — Canonical Sample Surface

### Objective

Finish the already-decided sample migration so that the public sample
repository presents one canonical learning and showcase system.

The final public `samples/` surface contains only canonical **L**, **C**, **B**
and **S** samples.

Historical numbered Samples 01–29 are transitional material and must not remain
in the final 1.0 public sample surface. Tests do not determine the public sample
taxonomy. Historical artifacts that remain technically necessary must be moved
to an appropriate test-fixture or architecture-evidence location.

This is an implementation of an existing architecture decision, not a new
sample-design exercise.

### F1.1 — Legacy dependency inventory

Before moving or deleting files:

- enumerate remaining numbered sample scripts, templates, assets, registry
  entries, outputs, and documentation references;
- identify direct test, demo, tooling, documentation, and architecture-evidence
  dependencies;
- classify each historical artifact as:
  - obsolete and removable;
  - required test fixture;
  - required architecture evidence;
  - replaced by a canonical L/C/B/S artifact;
- explicitly inspect special historical/support cases such as S02 and S03-B
  rather than retaining them merely because a test currently references them.

The inventory decides **where required historical material belongs**, not
whether numbered samples remain public.

### F1.2 — Physical migration

Apply the inventory mechanically:

- remove Samples 01–29 from the public `samples/` surface;
- relocate technically required fixtures/evidence outside the public sample
  surface;
- remove obsolete artifacts;
- remove transitional migration entries from the public sample registry;
- make Sample Explorer expose only the canonical sample taxonomy;
- update tests, tooling, and documentation paths affected by relocation;
- preserve sample-output handling rules and do not modify unrelated local
  generated artifacts.

### F1.3 — Sample consistency gate

F1 closes only when:

- `samples/` contains only canonical L/C/B/S public samples and their required
  canonical templates/assets;
- no public Sample Explorer path presents the numbered legacy track;
- no active documentation teaches the numbered legacy track;
- test-only historical artifacts live outside the public sample surface;
- SampleRegistry/PublicSampleSmoke coverage passes;
- relevant integration tests and the full normal test suite pass;
- PHP lint, documentation build where affected, and `git diff --check` pass;
- rendering-sensitive relocations receive the appropriate LibreOffice
  regression check if their semantics or generated output could have changed.

---

## F2 — Public API 1.0

### Objective

Turn the completed public API discovery/classification work into a trustworthy
1.0 end-programmer reference.

`docs/architecture/PUBLIC_API_INVENTORY.md` remains audit evidence. It is not
itself the user-facing API reference.

Every supported 1.0 end-programmer API must have an explicit audience
classification and sufficient reference documentation to establish, where
applicable:

- purpose;
- signature;
- parameters and types;
- accepted options and allowed values;
- defaults;
- return behavior and fluent behavior;
- lifecycle requirements and side effects;
- validation and relevant exceptions;
- ownership/ODF semantics where they affect correct use;
- limitations or compatibility caveats;
- a minimal usage example.

The reference must distinguish **Recommended**, **Advanced**, and
**Compatibility** surfaces. Infrastructure/public-for-technical-reasons
surfaces are not taught as normal application API unless extension authors
genuinely require them.

### F2.1 — API Reference Contract

Define the reference information architecture and one consistent page/entry
contract before writing the reference.

Decide:

- reference location and navigation;
- grouping by user-facing responsibility rather than accidental source layout;
- required fields for methods/classes/options;
- how Recommended, Advanced, Compatibility, Deprecated, and Infrastructure
  classifications are represented;
- how option dictionaries are documented;
- how aliases and historical APIs point to preferred replacements;
- how known limitations are linked without turning the reference into an
  architecture-history document.

Produce a small representative reference example and review it before
proceeding.

### F2.2 — Core / Lifecycle API

Document the normal facade and document lifecycle, including the relevant
`OdtTemplate` surfaces:

- construction/loading boundary;
- classic assignment and repeating data;
- `render()` and `save()`;
- structured insertion entry point;
- metadata;
- images;
- document defaults and document style entry point;
- reset/cleanup behavior where part of the supported public contract.

The documented lifecycle must preserve the characterized rule that
`save()` does not implicitly replace the classic `render()` step.

### F2.3 — Structured Content API

Document PHP-owned structured document construction:

- RichText and Paragraph;
- text/hyperlink behavior;
- lists;
- tables and cells;
- images;
- frames/text boxes;
- relevant fluent APIs;
- all supported user-facing style/layout option keys, types, values, and
  defaults.

Where current behavior contains a known compatibility defect or limitation,
document it rather than silently redefining semantics.

### F2.4 — Writer-native API

Document the Writer-owned/native-object model:

- current-document inspection;
- unified template inspection;
- bookmarks;
- sections;
- tables;
- frames;
- Writer User Fields;
- native population/replacement operations;
- the boundary between Writer-owned structure and PHP-owned generated
  structure.

The reference/guidance must make clear when native targets are preferable to
constructing equivalent content in PHP.

### F2.5 — Mapping & Automation API

Document the optional template-driven automation workflow as its own coherent
advanced capability:

- template contract inspection;
- mapping;
- concrete preflight;
- declarative execution where directly exposed;
- automation stages where public;
- the atomic high-level automation call;
- the bounded lifecycle, including one successful common Phase-E invocation per
  working document lifecycle;
- non-mutating inspection/mapping/preflight boundaries.

Do not imply that automation is a mandatory `OdtTemplate` lifecycle.

### F2.6 — Compatibility & Advanced Reference

Document retained non-primary surfaces without teaching them as equivalent
recommended paths.

This includes, where the audit confirms the classification:

- historical assignment/repeating aliases;
- immediate-mutating repeating compatibility behavior;
- lifecycle/reset compatibility methods;
- legacy/current-working-DOM extraction;
- diagnostics;
- specialized automation stages;
- other public compatibility/advanced surfaces established by the audit.

Preferred replacements and material semantic differences must be explicit.

Deprecated APIs remain a separate cleanup concern unless a concrete 1.0 release
blocker is established.

### F2.7 — API completeness audit

Mechanically reconcile:

```text
public PHP surface
        <->
PUBLIC_API_INVENTORY
        <->
1.0 API Reference
```

Every public symbol must have a justified disposition. Every Recommended and
Advanced end-programmer surface must be documented at the agreed reference
depth. Compatibility surfaces must be discoverable where users need migration
or behavioral information without dominating the normal learning path.

F2 closes only when the mechanical reconciliation has no unexplained public
surface and the reference builds successfully.

---

## F3 — Documentation & Learning Path

### Objective

Make the existing documentation understandable as one product rather than a
history of successive architecture milestones.

The documentation should orient users around three complementary working
models:

1. **Simple Template Processing**
2. **Structured ODT Construction**
3. **Writer-native Document Model**

These models describe who owns document structure and help users choose an
appropriate API. They do not create new runtime architecture.

### F3.1 — Information architecture

Review documentation navigation and entry points.

Establish a user journey that answers:

- What is the engine?
- How do I install it and create my first document?
- Which of the three working models fits my problem?
- Where do I learn the corresponding workflow?
- Where is the precise API reference?
- Where are advanced/internal topics?

Architecture evidence remains available but must not substitute for user
documentation.

### F3.2 — Getting Started

Reconcile installation and first-document guidance with the final 1.0 API.

The shortest successful path should use Recommended API only and lead from a
LibreOffice-authored template to an editable generated ODT without requiring
knowledge of internal ODF architecture.

### F3.3 — Guides reconciliation

Review existing guides rather than rewriting correct material.

At minimum reconcile guidance for:

- template language;
- rich/structured documents;
- styles;
- tables and lists;
- images, frames and text boxes;
- native/addressable structure;
- template inspection and authoring;
- mapping/preflight/automation;
- advanced ODT behavior.

Remove stale historical recommendations and numbered-sample learning paths.
Preserve documented compatibility behavior where it remains part of 1.0.

### F3.4 — README

Update the README after the reference and learning path are stable.

The README is the project entrance, not a replacement for the full
documentation. It should:

- explain the product and its three working models concisely;
- provide a minimal Recommended-API quick start;
- point to the canonical samples and Sample Explorer;
- point to the API reference and learning guides;
- avoid obsolete numbered-sample language;
- present project/support links consistently with F4.

F3 closes when README, documentation navigation, guides, API reference, and
canonical sample terminology tell a consistent 1.0 story.

---

## F4 — Project Presentation & Support

### Objective

Make the public project surfaces present the same product and make voluntary
support visibly discoverable without turning the project into a donation
landing page.

In this plan, **Support** means support **for the project**: ways users can help
continued development, including the existing project-site support area
(`https://odt.walter-dietz.de/#support`), GitHub starring, PayPal, and Bitcoin
Lightning where currently offered. It does not mean technical help/support.

### F4.1 — Website / Sample Explorer presentation audit

Review the actual public project website and Sample Explorer from a new-user
perspective after F3 has stabilized terminology and navigation.

Check:

- first-screen product explanation;
- relationship between project site, docs, repository and Sample Explorer;
- canonical sample taxonomy;
- primary next actions;
- stale architecture or sample terminology;
- links into documentation and source.

### F4.2 — Support visibility

Improve discoverability of the existing project-support path.

The final presentation should let a user who values the engine readily
understand how to support it, while keeping support voluntary and secondary to
the product itself.

Review consistent placement and wording across the project website, README and
other appropriate public entry points. Preserve the existing support methods
unless a separate decision explicitly changes them.

### F4.3 — Presentation consistency

Reconcile project website, README, docs and Sample Explorer so that they use:

- the same product description;
- the same canonical sample taxonomy;
- the same recommended API story;
- consistent documentation/source destinations;
- consistent project-support destinations.

F4 closes after link/navigation checks and the relevant web/demo validation.

---

## F5 — Distribution & Consumer Readiness

### Objective

Verify that the repository behaves as a consumable PHP package rather than only
as a development checkout.

### F5.1 — Package audit

Review the release-facing package surface, including:

- Composer metadata;
- PHP and extension requirements;
- PSR-4/autoload configuration;
- packaged/excluded files;
- license metadata;
- README installation commands;
- security/contribution references;
- version/release assumptions;
- documentation URLs and other release-facing links.

Do not introduce package restructuring without evidence of a release blocker.

### F5.2 — Clean consumer installation

Perform a clean-room consumer exercise outside the development checkout.

Using only published/documented installation and Recommended API guidance:

1. create a fresh consumer project;
2. install the package through Composer using the appropriate pre-release
   source/version for the test;
3. create/use a small real ODT template;
4. execute the documented workflow;
5. produce an editable result ODT;
6. open/validate it with LibreOffice where available.

Any need to rely on undocumented repository internals is a documentation or
packaging defect.

### F5.3 — Supported environment check

Reconcile stated support with CI and package reality:

- PHP versions;
- required PHP extensions;
- Composer behavior;
- LibreOffice-related requirements or optional validation assumptions;
- filesystem/runtime requirements that an external consumer must know.

F5 closes when a fresh consumer can install and execute the documented
Recommended path without relying on development-repository knowledge.

---

## F6 — Finalization Closeout

### Objective

Close FINALIZATION-01 and hand a stable candidate to the separate
RELEASE-1.0 INTEGRATION PRE-FLIGHT.

F6 is not another feature or documentation development block.

### F6.1 — Repository-wide consistency/preflight

Run the normal finalization validation appropriate to the changed surface,
including at minimum:

- focused tests for affected areas;
- relevant integration tests;
- PublicSampleSmokeTest;
- full `composer test`;
- PHP lint for `src/` and `tests/`;
- Sample Explorer lint where relevant;
- `composer validate` where relevant;
- documentation build;
- `git diff --check`;
- repository/reference scans needed to prove removed legacy sample paths or
  stale public terminology are gone.

Rendering-sensitive changes require the established manual LibreOffice
regression workflow; automated tests do not replace visual validation.

### F6.2 — Finalization closeout record

Record:

- completed F1–F5 gates;
- any bounded defects fixed during finalization;
- deferred findings and their FUTURE_DEVELOPMENT destinations;
- remaining known compatibility/deprecated surfaces;
- validation results;
- explicit confirmation that no new architecture was silently introduced.

### F6.3 — Handoff

The output of FINALIZATION-01 is a fixed release-candidate baseline for:

**RELEASE-1.0 INTEGRATION PRE-FLIGHT**

The release pre-flight, not FINALIZATION-01, performs the final integrated proof
that the candidate is suitable for 1.0.

---

## Explicit exclusions

Unless a concrete accepted-1.0 release blocker is demonstrated,
FINALIZATION-01 does **not** include:

- new document-model architecture;
- new template syntax;
- broad new style APIs;
- programmatic page-style authoring;
- new named-element architecture;
- new list/table/frame capabilities beyond already accepted 1.0 semantics;
- speculative API symmetry work;
- general removal of deprecated/compatibility APIs;
- unrelated refactoring;
- post-1.0 future topics already recorded in FUTURE_DEVELOPMENT.

Known future topics remain future work, including
TEMPLATE-FORMAT-PRESERVATION-01, TEMPLATE-AUTHORING-UX-01, STYLE-API-02
follow-up work, PAGE-STYLE-AUTHORING-01 and other accepted post-1.0 findings.

## Global completion criteria

FINALIZATION-01 is complete only when all six blocks are closed and:

1. the public sample surface is canonical L/C/B/S only;
2. the supported 1.0 API surface is classified and user-reference documented;
3. the learning path and guides consistently teach the final 1.0 product;
4. project website, README, docs and Sample Explorer present a consistent
   product and a clearly discoverable voluntary support path;
5. a clean external consumer workflow succeeds using documented Recommended
   API only;
6. normal repository validation is green;
7. all new findings are either resolved within the bounded contract or
   explicitly deferred;
8. the resulting baseline is suitable for the separate
   RELEASE-1.0 INTEGRATION PRE-FLIGHT.

## Review gate

This document is intentionally created before F1 implementation.

No FINALIZATION-01 implementation slice should begin until this plan has been
reviewed and accepted. Review changes to this document are planning changes,
not implementation scope drift.

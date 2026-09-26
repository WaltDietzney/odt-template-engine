# TEMPLATE-AUTHORING-01B Slice 4 — Native Ownership & Declarative Candidates

Status: COMPLETE / GATE GREEN

## Scope

Slice 4 completes the native-ownership and declarative-candidate portion of unified template inspection.

It builds on the source-oriented native-object inventory from Slice 1 and the scoped dependency/control graph from Slices 2 and 3. The slice adds deterministic native-object identity, explicit native containment/ownership, duplicate-name diagnostics, and conservative recognition of bounded declarative Section-name candidates.

The slice remains inspection-only. It does not execute declarative native controls, bind native Writer fields, or introduce high-level mapped rendering.

## Completed semantics

Slice 4 establishes:

- deterministic source-oriented identities for native Sections, Bookmarks, Tables, and Frames;
- explicit native containment/ownership references where reliably derivable;
- preservation of duplicate native-object occurrences;
- type-scoped duplicate-name diagnostics without collapsing evidence;
- separation of native objects from semantic control candidates carried by them;
- conservative recognition of explicit native Section declarations;
- native declarative candidates with support state RECOGNIZED rather than executable support;
- dependency and data-scope projection where a declaration is unambiguous;
- nested native foreach scopes;
- source-aware binding projection inside recognized native foreach ownership;
- deterministic serialization of the resulting graph.

## Native ownership graph

Native containment is represented independently from data-scope and control ownership.

For example:

    Section #foreach:experience
    └── Table ExperienceTable
        └── Frame CompanyLogo

is projected as distinct native objects with deterministic identities and owner references.

A contained Table references the containing Section identity. A contained Frame can preserve the containing Section/Table chain.

These identities are opaque contract identities. DOM nodes and process-local object identities are not exposed publicly or serialized.

Temporary DOM identity may be used internally while projecting one source traversal, but public native/evidence identities are derived deterministically from bounded source provenance.

## Duplicate native names

Duplicate authored names do not collapse native evidence.

For example, two Tables named Duplicate remain two NativeObjectDescriptor instances with distinct identities and produce duplicate_native_name diagnostics.

Equal textual names across different native-object kinds remain distinct and are not treated as a global collision. A Section named Profile and a Table named Profile therefore remain separate native objects without cross-type deduplication.

Duplicate diagnostics preserve inspectability and do not destroy the partial contract.

## Declarative native Section candidates

Slice 4 recognizes only the bounded Phase-B candidate forms established by the change contract:

    #foreach:<name>
    #if:<expression>
    #ifnot:<expression>

Recognized declarations are projected as semantic control candidates with:

    representation = NATIVE_SECTION_DECLARATION
    support_state  = RECOGNIZED

They are not reported as supported declarative-render execution.

Each recognized candidate references the concrete native Section that carries the declaration through carrierNativeObjectId().

This preserves the distinction:

    Native Section
        -> declaration evidence
        -> semantic control candidate

The native Section itself is not collapsed into the semantic control.

## Native data scopes

An unambiguous native foreach candidate may contribute dependency and data-scope information without implying execution support.

For example:

    Section #foreach:experience
        {{company}}
        Section #if:current
            ...
        Section #foreach:projects
            {{project_name}}
        Section #ifnot:archived
            ...

can project:

    ROOT
    └── experience[]
        ├── company
        ├── current
        ├── archived
        └── projects[]
            └── project_name

with derived dependency paths such as:

    experience[]
    experience[].company
    experience[].current
    experience[].archived
    experience[].projects[]
    experience[].projects[].project_name

Conditional native candidates inherit their containing data scope. Nested native foreach candidates create nested collection-item scopes.

Dependency mapping remains distinct from declarative execution support.

## Conservative recognition and malformed declarations

Ordinary Section names are not inferred to be controls merely because they contain a leading hash or resemble application naming conventions.

For example:

    #notes

remains an ordinary native Section.

A name that clearly resembles a bounded declaration but does not match its grammar, for example:

    #foreach experience

is not fuzzy-corrected. It remains native evidence and produces the machine-readable diagnostic:

    malformed_native_section_declaration

No semantic foreach control is invented from the malformed declaration.

Phase D retains authority over final declarative grammar and execution semantics.

## Provenance

Slice 4 preserves the B1.2 source/provenance model.

Declarative candidates retain, where applicable:

- source part;
- authored region;
- master-page owner;
- concrete header/footer carrier;
- representation kind;
- native owner chain;
- deterministic source order.

Page-owned declarations in styles.xml are therefore distinguishable from body declarations while still participating in the appropriate logical data scope.

The Slice-4 gate explicitly verifies a declaration under:

    styles.xml
    -> master-page Standard
    -> style:header

with MASTER_PAGE_CONTENT provenance.

## Diagnostics, support state, and readiness

Slice 4 preserves the B1.3 separation between:

    diagnostic severity
    semantic support state
    capability readiness

Recognized declarative candidates remain RECOGNIZED even when inspection and dependency mapping are READY.

Duplicate names and malformed declarations are diagnostics; they do not erase source evidence or automatically invalidate unrelated contract semantics.

No global valid() semantic is introduced.

## Compatibility

The following existing public behavior remains semantically unchanged:

- inspect();
- inspectTemplateStructure();
- classic render();
- save();
- existing section/bookmark/table/frame APIs.

Additional native-owner context retained by TemplateExpressionDescriptor is used by unified inspection without changing the established inspectTemplateStructure() serialized compatibility surface.

The original-source lifecycle established by Slice 1 remains unchanged.

No Phase-C native field binding, Phase-D declarative execution, or Phase-E high-level render orchestration is introduced.

## Closeout verification

The local Slice-4 closeout gate was reported green.

Verified checks include:

- focused Slice 4 integration tests;
- combined Slice 0 through Slice 4 integration gates;
- relevant PHP syntax checks from the implementation gate;
- git diff --check develop...HEAD.

The focused Slice-4 gate verifies, among other things:

- deterministic native-object identity;
- native Section/Table/Frame containment;
- preservation of duplicate occurrences;
- type-scoped duplicate diagnostics;
- equal names across different native-object kinds;
- #foreach candidate recognition;
- #if candidate recognition;
- #ifnot candidate recognition;
- RECOGNIZED rather than SUPPORTED support state;
- carrier-native-object references;
- native foreach collection-item scopes;
- nested native foreach scopes;
- binding dependencies inside native scopes;
- malformed candidate diagnostics without fuzzy correction;
- ordinary Sections remaining ordinary;
- styles.xml master-page/header provenance;
- deterministic contract serialization.

## Exit criterion

Slice 4 is complete.

The implementation satisfies the Slice-4 boundary of the TEMPLATE-AUTHORING-01B change contract and the relevant B1.1, B1.2, and B1.3 design decisions:

- native containment is distinct from semantic control ownership and data-scope nesting;
- native source evidence remains identifiable under duplicates;
- declarative Section-name forms are recognized conservatively;
- recognized native controls may contribute dependency/scope information without claiming execution support;
- provenance remains source-aware across content.xml and page-owned styles.xml content;
- diagnostics preserve partial inspectability;
- Phase D retains authority over final native control grammar and execution semantics.

Proceed to:

TEMPLATE-AUTHORING-01B Slice 5 — Diagnostics, Readiness & Serialization Stabilization

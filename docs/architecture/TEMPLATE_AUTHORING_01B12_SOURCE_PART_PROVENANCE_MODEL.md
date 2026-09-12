# TEMPLATE-AUTHORING-01B1.2 — Source-Part Coverage & Provenance Model

Status: ACTIVE DESIGN / NO PUBLIC API DECISION / NO PRODUCTION CHANGE

## Purpose

Define how a unified source-template contract identifies where authored evidence comes from without exposing mutable DOM nodes or confusing document-part location with data scope.

B1.0 established the concept taxonomy. B1.1 established that native containment, control ownership, data scope, and dependency references are separate graph dimensions.

B1.2 answers:

> Which source document parts participate in the authored template contract, and how can every discovered declaration be traced back to a stable-enough source location for diagnostics, tooling, and graph construction?

This is a provenance model, not an execution model.

## 1. Governing rule: the contract describes the authored source package

The unified template contract is derived from the original ODT archive.

Its authoritative inspection input is the authored source package, not the already-normalized or already-rendered working DOM.

```text
original ODT archive
├── content.xml
└── styles.xml
        ↓
source inspection
        ↓
Template Contract
```

This preserves authored physical expression topology, original native object names, original control declarations, original source-part ownership, and diagnostics that could disappear after normalization or rendering.

The existing live DocumentInspection remains a different view of the current mutable document state.

## 2. Bounded 1.0 source-part coverage

For template semantics, B1.2 proposes that the bounded 1.0 source contract inspect:

```text
content.xml
styles.xml
```

Both parts can contain Writer-authored content relevant to template rendering.

### 2.1 content.xml

Primary authored body content, including relevant native structures such as paragraphs/headings, lists, tables, frames, Sections, Bookmarks, visible template expressions, and supported fields/declarations.

### 2.2 styles.xml

Page/master-style-owned content can contain ordinary document content under master pages, especially headers and footers.

PAGE-FLOW-01 already established the architecture rule:

> Header/footer content under style:master-page is normal structured ODF content and remains part of template processing and document lifecycle.

Therefore unified source inspection must not treat styles.xml as style definitions only.

Relevant page-owned content may contain scalar/template expressions, named Tables, named Frames, native fields, and other supported template evidence.

B1.2 proposes source inspection symmetry where semantics are valid, even though the current DocumentInspector has asymmetric native-object coverage.

## 3. Explicitly excluded package parts for B1

B1.2 does not make every ODT XML file a template-semantic source.

The initial unified contract does not derive template bindings/controls from meta.xml, settings.xml, manifest metadata, embedded object XML, or arbitrary package files.

Those parts may be useful to other engine capabilities, but including them in the template contract requires a concrete semantic need.

## 4. Document part and authored region are different concepts

A provenance location needs more precision than document_part = styles.xml because styles.xml contains both style declarations and page-owned document content.

B1.2 therefore distinguishes source part from authored region.

### 4.1 Source part

Physical package part:

```text
content.xml
styles.xml
```

### 4.2 Authored region

Semantic content region inside that part.

Minimum conceptual region kinds:

```text
BODY
MASTER_PAGE_CONTENT
OTHER_SUPPORTED_CONTENT_REGION
```

For page-owned content, provenance should preserve additional native context where available: master-page name and concrete header/footer carrier element kind.

Example:

```text
part: styles.xml
region: MASTER_PAGE_CONTENT
master-page: First Page
carrier: style:header
```

The exact enum/class names are deferred.

## 5. Do not flatten all styles.xml nodes into template content

Scanning styles.xml does not mean running template expression discovery over every style-definition attribute and style property indiscriminately.

The contract is about authored document content semantics.

B1.2 therefore requires content-region discovery before expression/native-object discovery.

```text
styles.xml
├── office:styles                 style definitions, not text-flow template content
├── office:automatic-styles       style definitions, not text-flow template content
└── office:master-styles
    └── style:master-page
        └── page-owned content    template-inspectable content region
```

This avoids false positives from style metadata.

## 6. Body and page-owned content share the ROOT data scope by default

Source-part location is orthogonal to application-data scope.

Example:

```text
content.xml / BODY:
    {{name}}

styles.xml / header:
    {{name}}
```

Both sites can reference one logical dependency ROOT.name.

Provenance differs; data scope does not.

Therefore dependency deduplication must not use source part or authored region as part of the default logical dependency key.

## 7. Provenance record — conceptual fields

Every authored evidence site should be traceable through a provenance record.

Conceptually useful fields include:

```text
source_part
region_kind
region_owner
carrier_kind
native_owner_chain
representation_kind
source_name/raw_declaration
source_order
physical_scope
```

These are semantic fields, not an approved DTO.

### 7.1 source_part

Examples: content.xml, styles.xml.

### 7.2 region_kind

Examples: BODY, MASTER_PAGE_CONTENT.

### 7.3 region_owner

For page-owned content, this may include the master-page name. For body content, the owner may be the document body region rather than a named native object.

### 7.4 carrier_kind

Preserve the concrete ODF carrier when it matters, for example office:text, style:header, or style:footer.

B1.2 intentionally does not invent an exhaustive header/footer taxonomy beyond what the source actually contains.

### 7.5 native_owner_chain

The ordered containing native objects relevant to structural ownership.

Example:

```text
Section #foreach:experience
  -> Table ExperienceTable
```

### 7.6 representation_kind

Examples:

```text
visible_expression
classic_control_marker
native_section_name
native_object_name
native_field
```

This supports the B1.1 requirement that similar semantics can retain different authored provenance.

### 7.7 source_name/raw_declaration

Examples: {{company}}, #foreach:experience, PortraitFrame.

### 7.8 source_order

A deterministic document-order ordinal inside a bounded source region/part can distinguish duplicate or repeated evidence sites without exposing DOM nodes.

It is a locator aid, not semantic identity.

### 7.9 physical_scope

Existing expression-topology information such as paragraph, heading, table cell, Section, or text box remains useful for diagnostics.

## 8. Stable-enough source identity

B1.2 rejects two extremes.

### 8.1 DOM object identity is unsuitable

Values such as spl_object_id() or DOMNode references are process-local and mutable. They must not appear in the public contract.

### 8.2 Native name alone is insufficient

A name can be duplicated in malformed documents, repeated legally across different object types, absent, changed by the author, or unavailable for visible expressions.

Therefore native names are provenance data, not universal contract IDs.

### 8.3 Proposed identity principle

A contract evidence item needs an opaque inspection identity that is:

- unique within one contract snapshot;
- deterministic for the same unchanged source package and inspection algorithm;
- independent of live DOM object identity;
- not promised to survive author edits that change source structure;
- not interpreted by application code as business identity.

Exact syntax is deferred to B1.4/implementation.

A deterministic identity may be derived from bounded provenance such as source part, region, representation kind, structural/source order, and relevant native/raw declaration data.

The important contract is behavioral, not the hash/string format.

## 9. Native object identity under duplicates

Duplicate native names must not collapse source objects into one node.

Example:

```text
Section name="Repeated"
Section name="Repeated"
```

The source contract needs two distinct NativeObject evidence nodes with the same native_name plus a duplicate-name diagnostic.

Name-based lookup may be ambiguous, but source evidence remains fully inspectable.

This is essential because A1 proved that classic repetition can generate duplicate native identities.

## 10. Cross-type equal names remain distinct

Existing native inspection semantics allow the same textual name across different object types without a global collision.

Example:

```text
Section: Profile
Table: Profile
Frame: Profile
```

Unified source provenance must preserve type + source identity + native name and must not merge these nodes merely because their names match.

Any future global semantic naming rule would require a separate explicit decision.

## 11. Evidence-site identity versus logical dependency identity

These identities have different lifetimes and purposes.

Example:

```text
content.xml body:   {{name}}
styles.xml header:  {{name}}
```

Source graph:

```text
Evidence A
    provenance: content.xml / BODY

Evidence B
    provenance: styles.xml / MASTER_PAGE_CONTENT
```

Both reference one LogicalDependency ROOT.name.

Thus there are two evidence identities and one dependency identity.

This distinction is mandatory for diagnostics and generic mapping.

## 12. Source order and authored order

Document order is relevant to deterministic inspection output, classic marker pairing/ranges, diagnostics, explaining source occurrence, and some Writer field semantics in later Phase C.

B1.2 therefore treats authored order as provenance.

However, source order must not be confused with control/data ownership.

Nearest-by-order is not permission to infer ambiguous semantics.

## 13. Native owner chain

For every evidence site, the inspector should be able to project the containing native-object chain where relevant.

Example:

```text
styles.xml
  master-page Standard
    header
      Section HeaderContact
        Table ContactTable
          {{phone}}
```

Provenance:

```text
source_part: styles.xml
region: MASTER_PAGE_CONTENT
region_owner: Standard
carrier: style:header
native_owner_chain:
  Section HeaderContact
  Table ContactTable
physical_scope:
  table:table-cell / text:p
```

Data semantics can still be ROOT.phone.

This example demonstrates why provenance and data scope must stay separate.

## 14. Page-owned content provenance

PAGE-FLOW-01 established page-owned header/footer content as normal structured content.

B1.2 therefore proposes that source inspection identify page-owned content with enough context to answer:

- Which master page owns this content?
- Which concrete header/footer carrier contains it?
- Which native objects contain the evidence?
- Which logical dependency/control does it participate in?

It does not require the template contract to model Writer pagination.

A header under a master page is provenance, not a page-number prediction.

## 15. Multiple master pages

The same logical dependency can occur under multiple master pages.

Example:

```text
First Page header: {{document_title}}
Standard header:   {{document_title}}
```

Contract:

```text
Evidence A -> First Page / header
Evidence B -> Standard / header

both -> ROOT.document_title
```

The contract must preserve both sites while deduplicating the logical dependency.

This is useful to tooling that wants to show every authoring occurrence.

## 16. Source/native inspection versus live DocumentInspection

B1.2 concludes that the unified authored template contract cannot simply embed the current live DocumentInspection snapshot as-is and claim source provenance.

Reasons:

1. DocumentInspection inspects the mutable working DOM.
2. Its current object coverage is asymmetric by document part.
3. It is optimized for current native addressability, not authored-source declaration sites.
4. Duplicate/native evidence needs source identities beyond first-by-name access.
5. The source contract needs page-owned region provenance.

Existing descriptor semantics remain reusable architectural evidence, but a source-oriented projection/adaptation layer is likely required.

This is a design conclusion, not an implementation class decision.

## 17. Source-native descriptors should remain semantic, not DOM wrappers

Whatever source-oriented native-object representation B eventually uses, it should follow the successful current descriptor pattern:

- immutable;
- no DOM exposure;
- machine-readable;
- deterministic;
- structurally meaningful;
- diagnostics attached or referenceable.

The unified contract should not become a remote-control interface over source DOM nodes.

## 18. Expression provenance

Current TemplateExpressionDescriptor already provides useful physical evidence: raw text, logical kind, scope, fragment count, style names, bookmark intersections, and normalization classification.

B1.2 proposes retaining these semantics while adding source-part/region ownership externally or through a source-aware projection.

Important rule:

> Do not discard authored physical topology merely because higher-level binding/control semantics can be derived.

That topology drives authoring diagnostics.

## 19. Classic control provenance

Classic controls require evidence for multiple marker sites and their paired/range relation.

Example:

```text
{{#foreach:items}}     Evidence A
...
{{#endforeach}}        Evidence B
```

The semantic control is not identical to either marker.

Provenance should therefore support:

```text
Control C
├── declared_by Evidence A
├── closed_by Evidence B
└── owns compatibility range R
```

The exact range-locator representation is deferred.

This is another reason a flat expression list is insufficient.

## 20. Native declaration provenance

For a future declarative Section control such as a text:section whose text:name is #foreach:experience, the evidence can be attached to one native object.

Conceptually:

```text
Native Section S
    source identity: S1
    native name: #foreach:experience

Declaration Evidence E
    representation: native_section_name
    source owner: S1
    raw declaration: #foreach:experience

Control C
    interpreted from E
    carried by S1
    owns S1
```

This is substantially cleaner than classic marker-range provenance and is one practical reason native declarations are attractive.

## 21. Native fields and authored order

Phase C will decide bounded native-field semantics.

B1.2 reserves provenance sufficient to support field families where document-flow order matters.

RESEARCH-01 established that Set/Get Variable semantics can depend on preceding sets.

Therefore field evidence may require source part, authored region, source order, native field kind/name, and containing structural owners.

B1.2 does not inspect or execute those fields yet.

## 22. Partial source coverage must be visible

If the unified inspector intentionally does not cover a package part or unsupported region, tooling must not be misled into believing the entire archive was semantically inspected.

The contract should eventually be able to describe its coverage boundary.

Conceptual metadata:

```text
inspected_parts:
  - content.xml
  - styles.xml

unsupported_or_uninspected:
  - embedded objects
  - other package parts
```

Exact serialization belongs to B1.4.

## 23. Provenance and diagnostics

Diagnostics should be traceable to provenance whenever possible.

Examples:

```text
duplicate_native_name
    -> NativeObject evidence A + B

unsupported_template_expression
    -> expression evidence E

ambiguous_control_ownership
    -> declaration evidence E
       candidate owner evidence S1/S2
```

A diagnostic should not need a live DOM node to explain where the author should look.

This is a key requirement for future authoring tooling.

## 24. Provenance and generic integrations

Most integrations will primarily consume the logical dependency projection.

But provenance allows advanced tooling to answer:

- Where is this dependency used?
- Is it in body or header/footer?
- Which master page contains it?
- Which Section/Table/Frame owns it?
- Which control caused this data requirement?
- Is the same input used in multiple places?
- Which authoring occurrence is unsafe?

This supports an eventual template authoring/debugging experience without coupling applications to ODF XML.

## 25. Provenance does not imply mutation API

A source locator exists to explain and correlate inspection findings.

It does not automatically authorize mutation methods on contract sites.

Mutation remains a separate API concern.

This preserves read-only inspection boundaries.

## 26. Proposed source-inspection pipeline

Conceptually:

```text
Original ODT archive
        ↓
read source content.xml + styles.xml
        ↓
discover supported authored content regions
        ↓
project native objects + visible expressions + declaration evidence
        ↓
attach provenance / deterministic evidence identities
        ↓
interpret supported bindings and controls
        ↓
build ownership + data-scope graph
        ↓
derive logical dependencies
        ↓
attach diagnostics / coverage metadata
        ↓
Template Contract
```

This is architecture sequencing, not production implementation approval.

## 27. Decisions established by B1.2

Subject to design review, B1.2 proposes:

1. The template contract is source-oriented and derived from the original ODT archive.
2. Bounded 1.0 template-semantic source coverage includes content.xml and supported content regions in styles.xml.
3. styles.xml is not scanned indiscriminately; page/master-owned document content is distinguished from style-definition data.
4. Source part, authored region, physical scope, native ownership, and data scope are distinct dimensions.
5. BODY and page-owned content share ROOT application-data scope by default.
6. Evidence-site identities are distinct from logical dependency identities.
7. Native names are provenance/semantic data, not sufficient universal IDs.
8. Duplicate native names preserve multiple evidence nodes plus diagnostics; they are never collapsed.
9. Equal names across native object types remain distinct.
10. Source order is preserved as provenance but cannot resolve semantic ambiguity by itself.
11. Page-owned provenance includes master-page/carrier context without modeling pagination.
12. The current live DocumentInspection cannot by itself serve as the authored source-template contract.
13. Source-oriented descriptors should remain immutable and DOM-free.
14. Inspection coverage boundaries must be reportable.
15. Provenance is explanatory/read-only and does not imply mutation APIs.

## 28. Questions carried into B1.3

B1.3 — Diagnostics / Support-State Composition must decide:

1. How native and template diagnostics appear in one contract.
2. Whether severity and semantic support state are orthogonal.
3. How recognized-but-not-executable Phase C/D declarations are represented.
4. How current classic-control defects/limitations are reported without marking otherwise discoverable data dependencies as nonexistent.
5. How ambiguous ownership or scope affects overall contract validity.
6. Whether contract validity is global or capability-specific.
7. How diagnostics reference evidence/native/control/dependency identities.
8. Which conditions block high-level rendering versus merely warn authoring tools.
9. How unsupported source regions/coverage limitations are surfaced.
10. How duplicate native identities affect inspectability versus executability.

## 29. B1.2 design thesis

A useful template contract must answer both:

> What does this template mean?

and:

> Where in the authored ODT did that meaning come from?

The second answer requires source provenance rich enough to distinguish body, page-owned content, native ownership, duplicate declaration sites, and authored order without exposing mutable DOM nodes.

That provenance makes the semantic graph explainable, debuggable, and usable by generic tooling while keeping Writer responsible for layout and the engine responsible for declared template semantics.

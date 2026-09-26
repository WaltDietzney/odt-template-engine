# RESEARCH-01 — Native ODF Authoring Capabilities

## Status

**Strategic research phase complete. Remaining research topics are preserved for later evidence-driven work. No public API or implementation decision is implied by this document.**

RESEARCH-01 investigated which authoring, template, layout, style, page-flow, and addressable-object semantics are already provided natively by ODF and LibreOffice Writer, and how the ODT Template Engine should make use of them.

The research was intentionally prior to the next major implementation milestone. Its strategic result is recorded in [`RESEARCH-01_1_0_REASSESSMENT_DECISION.md`](RESEARCH-01_1_0_REASSESSMENT_DECISION.md), which defines the remaining mandatory path to version 1.0 and deliberately defers non-blocking native ODF capabilities.

The original RESEARCH-01 map remains useful as a research backlog. RESEARCH-01B through RESEARCH-01E are **not claimed complete** by this closeout. They should be resumed when a concrete architecture milestone or product need requires their evidence.

## 1. Motivation

The engine has evolved from placeholder-oriented ODT processing toward a structured document model. Recent work on named sections, bookmarks, typed targets, structured insertion, semantic style requirements, and document-local style ownership has shown that important problems can often be solved more naturally by preserving and addressing native ODF structures than by reproducing document semantics in PHP.

This changes the planning question.

The primary research question is no longer only:

> Which ODT feature should the engine implement next?

It is:

> Which semantics do ODF and LibreOffice Writer already provide, and how can the engine expose or preserve them in a way that is useful to template authors and PHP developers?

The engine should not rebuild Writer's layout and document model in PHP where native ODF semantics already provide a stronger solution.

At the same time, **native-first is not a dogma**. A native Writer mechanism is not automatically preferable when a simple engine feature is substantially clearer, safer, or easier to author. The relevant criteria are authoring clarity, ODF fidelity, reliable processing, interoperability, and developer usability.

## 2. Research principles

### 2.1 Semantics before implementation

Research must establish actual ODF/LibreOffice behavior before public APIs are designed.

A research finding does not imply a public API decision.

### 2.2 Preserve the two authoring perspectives

The engine serves two distinct but complementary authoring modes:

- **Template-driven authoring:** a template author or editor designs and maintains the document in LibreOffice without needing to program document layout in PHP.
- **Code-driven authoring:** a developer generates or changes document structure through the PHP API.

Neither mode should accidentally erase the value of the other.

### 2.3 LibreOffice should remain a visual template designer

Where practical, document structure, layout, and reusable styles should be authored in LibreOffice and preserved by the engine rather than reconstructed in application code.

### 2.4 Existing syntax remains valid until deliberately reconsidered

RESEARCH-01 may discover native alternatives to `{{variable}}`, filters, conditions, loops, or other template-language constructs. Such discoveries do not deprecate or remove existing syntax by themselves.

Any later compatibility or product decision must be explicit.

### 2.5 Separate evidence from product design

For each research topic, distinguish:

1. **Observation** — what LibreOffice exposes to an author.
2. **ODF evidence** — how the behavior is represented in `content.xml`, `styles.xml`, or other package parts.
3. **Behavioral evidence** — what happens when Writer opens, updates, saves, exports, clones, or nests the structure.
4. **Engine relationship** — what the current engine already preserves, understands, or conflicts with.
5. **Product candidate** — what useful capability might follow.
6. **API decision** — deliberately deferred until the evidence and product need justify it.

## 3. Research map

### RESEARCH-01A — Fields, Variables, and Conditional Content

**Research priority: 1 — strategic evidence complete for the current planning decision**

Investigate native Writer/ODF mechanisms for data-bearing fields and conditional content.

Topics include:

- variable fields and user-defined fields;
- field value representation;
- conditional text;
- hidden text;
- hidden paragraphs;
- conditional/hidden sections;
- expressions and condition syntax;
- string, numeric, boolean, and empty-value behavior where applicable;
- update/evaluation behavior when opening or exporting a document;
- headless LibreOffice behavior where relevant;
- nesting and interaction with named sections;
- preservation of paragraph, character, and surrounding document formatting;
- interaction with current scalar placeholder replacement;
- relationship to `{{variable}}`, filters, `if`/`elseif`/`else`, and similar visible template syntax.

A central question is whether structural selection can be represented as native, visually authored document structure rather than visible control syntax.

Sample 10 should serve as a negative/legacy authoring benchmark: determine how much of its visible control structure can be represented more clearly without sacrificing template-author control.

#### RESEARCH-01A empirical findings

The following findings were established with small LibreOffice Writer fixtures and direct inspection of `content.xml`. They characterize observed Writer/ODF behavior; they do not define engine APIs.

##### Variable Set/Get

Writer's Set Variable / Show Variable mechanism is represented by a variable declaration plus position-dependent set/get fields, for example:

```xml
<text:variable-decl office:value-type="string" text:name="customer"/>
<text:variable-set
    text:name="customer"
    text:display="none"
    text:formula="ooow:Walter"
    office:value-type="string"
    office:string-value="Walter"/>
<text:variable-get text:name="customer">Walter</text:variable-get>
```

The mechanism has document-flow semantics: a get resolves the applicable preceding set for the same variable. Writer field types matter; a text variable must be authored as text rather than accidentally using the default numeric representation.

##### User Fields

Writer User Fields provide a different model: a central declaration carries the value and any number of references display it.

```xml
<text:user-field-decl
    office:value-type="string"
    office:string-value="Walter"
    text:name="customer"/>
<text:user-field-get text:name="customer">Walter</text:user-field-get>
```

A controlled fixture changed only the central declaration from `Walter` to `Maria`, leaving the visible character data of the field references unchanged. Writer displayed `Maria` immediately on normal open. A direct headless PDF conversion also rendered `Maria`.

This establishes the central declaration as semantically authoritative for Writer evaluation. The character data of `text:user-field-get` acts as materialized/display data rather than the authoritative application value. Other ODF consumers may behave differently, so robust cross-viewer handling remains a separate concern.

##### Conditional Text

Writer Conditional Text is represented as a binary value-selection field:

```xml
<text:conditional-text
    text:condition="ooow:customer == &quot;Walter&quot;"
    text:string-value-if-true="Bedingung ist WAHR"
    text:string-value-if-false="Bedingung ist FALSCH">
    Bedingung ist WAHR
</text:conditional-text>
```

Changing only the central User Field from `Walter` to another value caused Writer to recalculate the displayed branch on open.

A second fixture placed an engine placeholder inside both branches, for example:

```text
true:  Sehr geehrte Frau {{lastname}},
false: Sehr geehrter Herr {{lastname}},
```

Replacing `{{lastname}}` only in the currently displayed character data was not persistent: Writer recalculated the field and restored the branch value containing the unresolved placeholder. Replacing the placeholder in both `text:string-value-if-true` and `text:string-value-if-false`, as well as the current materialized character data, survived reevaluation and branch switching.

This is an important semantic boundary for the engine:

> For `text:conditional-text`, the true/false attribute values are authoritative branch content. Placeholder processing that supports native Conditional Text must process all authoritative branches, including an inactive branch; text-node replacement alone is insufficient.

Conditional Text is binary value selection. It is not a direct replacement for the engine's general `if` / `elseif` / `else` structural control, which can select arbitrary document subtrees and more than two branches.

##### Hidden Text

Writer Hidden Text can conditionally suppress inline content. A fixture used a string User Field `gender = female` and:

```xml
<text:hidden-text
    text:condition="ooow:gender != &quot;female&quot;"
    text:string-value="FEMALE-TEXT"
    text:is-hidden="true">
    FEMALE-TEXT
</text:hidden-text>
```

After changing only the central User Field to `male` by script, headless PDF conversion correctly suppressed `FEMALE-TEXT` and retained the surrounding paragraph text.

In the tested DOCX conversion, the hidden inline text was effectively materialized away: the suppressed text did not appear in the resulting Word document. This behavior is promising but should not yet be generalized beyond the characterized conversion case.

##### Hidden Paragraph

Writer Hidden Paragraph can conditionally suppress the complete paragraph containing the field. With `gender = female` and condition `gender != "female"`, the paragraph remained visible. The ODF representation included:

```xml
<text:hidden-paragraph
    text:condition="ooow:gender != &quot;female&quot;"/>
```

After changing only the central User Field to `male` by script, headless PDF conversion correctly removed the complete paragraph from rendered output.

DOCX conversion did not preserve the same result reliably: the paragraph text remained as an ordinary Word paragraph and no equivalent conditional semantics were observed in the generated `word/document.xml` during the experiment.

This creates an interoperability distinction between correct ODF/LibreOffice rendering and conversion to a format that cannot directly carry the same ODF semantics.

##### Conditional Sections

Conditional Writer Sections proved to be the strongest native structural-selection mechanism investigated in RESEARCH-01A.

A three-way salutation was authored as three sibling named Sections:

```text
SalutationFemale
    hide if gender != "female"
    Sehr geehrte Frau {{lastname}},

SalutationMale
    hide if gender != "male"
    Sehr geehrter Herr {{lastname}},

SalutationDefault
    hide if (gender == "female") OR (gender == "male")
    Sehr geehrte Damen und Herren,
```

The sections were confirmed as sibling `text:section` elements, not nested sections. Their ODF representation combines the semantic condition with `text:display="condition"`; Writer may additionally materialize the current hidden state as `text:is-hidden="true"`.

For example:

```xml
<text:section
    text:style-name="Sect1"
    text:name="SalutationMale"
    text:condition="ooow:gender != &quot;male&quot;"
    text:is-hidden="true"
    text:display="condition">
```

The fixture was verified manually for `female`, `male`, and a third/default value. Parenthesized comparisons in the OR expression produced the intended default behavior. This observation does not establish that parentheses are universally required by Writer's condition grammar.

Most importantly, changing only the central `gender` User Field by script and then converting directly with headless LibreOffice produced the correct PDF branch for both `male` and an unknown/default value. No Writer GUI open/save step was required.

This establishes a useful server-side model:

```text
LibreOffice-authored conditional structure
    -> application changes semantic User Field value
    -> LibreOffice headless evaluates native conditions
    -> rendered PDF contains the selected structure
```

##### DOCX interoperability and finalization

The Conditional Section fixture exposed an important export boundary. LibreOffice converted the `gender` User Field to a Word `DOCVARIABLE`, but the tested DOCX did not retain the ODF Section conditions as equivalent Word structural conditions. Section branch content could remain as ordinary Word paragraphs.

Together with the Hidden Paragraph result, this suggests that native ODF conditional semantics must not be assumed to survive DOCX conversion.

A strong architecture candidate therefore emerges for export interoperability:

```text
semantic template ODT
    -> bind application data
    -> evaluate/materialize template semantics
    -> remove inactive structures/content where required
    -> finalized static ODT
    -> PDF and/or DOCX conversion
```

For PDF, LibreOffice can already evaluate the characterized native conditions during headless rendering. A common explicit finalization stage may nevertheless be valuable if multiple export formats must receive the same resolved document state. Whether such a stage becomes an engine responsibility is an architecture decision for FINALIZATION-01.

##### Field scope and section instantiation

The field experiments expose an important scope distinction for repeated native structures.

A Writer User Field has a central declaration. Cloning a Section containing several `text:user-field-get` references would therefore clone references to the same document-global field rather than automatically create item-local values for each Section instance. That makes User Fields a natural candidate for document-global data, but not an automatic replacement for item-local placeholders inside a repeated collection block.

Set Variable / Show Variable has different, position-dependent document-flow semantics and may therefore interact differently with cloned Sections. This remains an empirical research question. No field-localization or field-identity-rewriting mechanism is currently assumed.

The current working preference is therefore deliberately conservative:

> `{{variable}}` remains the preferred general and portable data-binding mechanism unless a native Writer field provides a clear semantic or authoring advantage.

This preference is strengthened by DOCX interoperability: engine placeholders can be fully materialized to ordinary document content before conversion, whereas native field and conditional semantics may not survive conversion consistently.

Native Writer fields remain valuable research candidates, especially for document-global values, Writer-authored conditions, and cases where native field semantics provide a concrete authoring benefit. RESEARCH-01 does not aim to replace the existing placeholder model with Writer fields.

##### Relationship to engine placeholders

The experiments suggest a hybrid model rather than a competition between native Writer semantics and the existing template language.

A useful working distinction is:

- `{{variable}}` remains a concise, portable value-binding mechanism;
- scalar filters such as `{{upper:name}}` remain value transformations rather than structural control;
- native ODT structures can carry structural template semantics;
- native Writer field/condition mechanisms can express richer Writer-owned conditional semantics where useful;
- optional inline content can use Hidden Text where appropriate;
- optional complete paragraphs can use Hidden Paragraph where appropriate;
- optional complex document blocks can use Conditional Sections.

This leads to an important research principle:

> **Value binding and structural control do not need to use the same template mechanism.**

For example, a repeated native Section can own the repeatable document block while the content inside each instantiated Section continues to use simple item-local placeholders:

```text
Section: #foreach:experience

    {{from}} – {{to}}
    {{position}}
    {{company}}
```

The Section describes what happens to the block; the placeholders describe which values are materialized inside each resulting instance.

##### Research hypothesis: declarative structural operators on named Sections

The current engine already has structured Section instantiation semantics, including repeated instantiation, local scalar binding, deterministic identity rewriting, nested Section resolution, prototype removal, and rollback behavior. This suggests a declarative authoring layer rather than a second structural-processing implementation.

A Writer-authored named Section can carry a candidate semantic name such as:

```text
#foreach:experience
```

The empirical evidence is recorded in [`RESEARCH-01A_DECLARATIVE_SECTION_FIXTURE.md`](RESEARCH-01A_DECLARATIVE_SECTION_FIXTURE.md). Writer stores the name unchanged, the current Section resolver accepts it, and existing `instantiateMany()` successfully clones the Section, binds item-local placeholders, rewrites identities, removes the prototype, saves, and reopens the result.

Conceptually, a future declarative layer could map:

```text
LibreOffice Section: #foreach:experience
    -> discover structural declaration
    -> resolve collection: experience
    -> existing instantiateMany()
    -> bind {{...}} values in each local instance
    -> finalize prototype/result
```

The important architectural property is ownership:

- the **template** decides that this native document block is repeatable and owns its layout, styles, and internal structure;
- the **engine** interprets the declaration, resolves the collection, performs structured instantiation, validates the operation, and binds each local data context;
- the **application** supplies data without having to restate document structure in PHP.

This is not merely a hidden form of the existing text-based loop. The native Section itself provides the structural boundary, so no textual `#endforeach` marker is required. ODT structure replaces part of the control syntax.

The same principle may be useful for a deliberately small set of simple structural operators. Research candidates include:

```text
#foreach:experience
#if:profile
#ifnot:photo
```

These remain **research candidates, not approved syntax**. Complex expressions should not automatically be pushed into Section names; Writer's own field and conditional mechanisms are more plausible candidates where their semantics and export behavior are suitable.

The declarative `#foreach` candidate is classified by the 1.0 reassessment as high-value but non-blocking. It may be resumed later without changing the mandatory 1.0 sequence.

### RESEARCH-01B — Sections and Native Layout

**Research priority: 2 — deferred; resume from PAGE-FLOW-01 or another concrete layout need**

Investigate Sections as native layout containers beyond the structured cloning semantics already established by SECTION-03.

Topics include:

- single-column and multi-column sections;
- equal and unequal column widths;
- column gaps and separators;
- nested sections;
- interaction with page boundaries;
- section-local styles and layout properties;
- section names as stable authoring identities;
- interaction with frames, tables, lists, and generated structured content.

The page-boundary subset is now explicitly promoted into PAGE-FLOW-01 because it is 1.0-blocking. Broader multi-column and layout research remains available for later work and is not claimed complete.

### RESEARCH-01C — Native Style Semantics

**Research priority: 3 — deferred; resume when a concrete style capability requires it**

Investigate the native Writer style families from the perspective of template authors and engine ownership.

Topics include paragraph, character, page, frame, list, and table-related styles; inheritance; named styles versus automatic styles; template-authored versus generated definitions; and the relationship to the completed STYLE-CONTEXT-01 / STYLE-API-02 architecture.

The style architecture is already sufficiently coherent for the 1.0 sequence. PAGE-FLOW-01 will necessarily research page-style semantics, but RESEARCH-01C as a broad survey is not a prerequisite for resuming implementation work.

### RESEARCH-01D — Page Styles and Document Flow

**Research priority: 4 — promoted into PAGE-FLOW-01**

The originally broad page/document-flow research is now a concrete 1.0-blocking architecture milestone.

PAGE-FLOW-01 must characterize and design around:

- page/master styles and referenced page layouts;
- first-page versus following-page behavior;
- headers and footers as page-style-owned content;
- explicit page breaks and page-style transitions;
- paragraph keep-with-next / keep-together semantics;
- widow/orphan behavior;
- structured Section behavior across page boundaries;
- interactions with tables and lists where relevant to pagination.

The engine must express or preserve native semantics and leave actual pagination to LibreOffice/Writer rather than calculating page geometry in PHP.

### RESEARCH-01E — Named and Addressable Native Objects

**Research priority: 5 — deferred; SECTION-03 remains the proven baseline**

Investigate which additional native Writer/ODF objects have stable enough identity and lifecycle semantics to become typed addressable targets.

Potential future operations remain distinct:

```text
replace content
replace object
clone
remove
```

Potential targets include frames, text boxes, tables, images/drawing objects, and other stable named structures. This work remains post-1.0 unless a mandatory milestone exposes a concrete dependency.

## 4. Cross-cutting authoring and developer UX

### Template Author UX

LibreOffice should remain the visual template designer where practical. Research and later tooling should favor discoverable native structures over visible source-code-like control syntax when the native model provides a clear semantic advantage.

### Developer UX

The PHP API should remain explicit, coherent, and discoverable. Native ODF complexity should not leak into application code merely because the file format exposes it. The engine should provide typed semantics where a stable abstraction is justified and preserve authored native structures where PHP does not need to own them.

## 5. Evidence and fixture methodology

Research fixtures should remain small and purpose-specific. For each behavior:

1. author the smallest useful fixture in LibreOffice;
2. inspect `content.xml`, `styles.xml`, and other relevant package parts;
3. modify the semantic value or structure externally where useful;
4. reopen/render/export through LibreOffice;
5. compare ODT/PDF/DOCX behavior where interoperability matters;
6. characterize current engine behavior before changing production code.

The project-local `research/` directory may hold exploratory fixtures. Versioning of individual fixtures must be deliberate. Exploratory files must not be scattered into public samples or tests merely because they were useful during discovery.

## 6. Strategic conclusion and transition

RESEARCH-01 has produced enough evidence to make the remaining path to 1.0 explicit without exhausting the entire native ODF surface.

The planning decision is recorded in [`RESEARCH-01_1_0_REASSESSMENT_DECISION.md`](RESEARCH-01_1_0_REASSESSMENT_DECISION.md).

The mandatory sequence is:

```text
PAGE-FLOW-01
    ↓
TABLE-LAYOUT-01
    ↓
FRAME-LAYOUT-01
    ↓
TEMPLATE-RELIABILITY-01
    ↓
FINALIZATION-01
    ↓
RELEASE-1.0 INTEGRATION PRE-FLIGHT
    ↓
1.0
```

The central scope decision is equally important: RESEARCH-01B through RESEARCH-01E do not need to be completed as broad surveys before implementation resumes. Their unanswered questions remain valid and should be pulled into concrete milestones when needed.

High-value but non-blocking directions such as declarative `#foreach:collection`, broad Writer-field APIs, document-default redesign, general named-object operations, document import, and renderer-neutral abstraction remain available for post-1.0 or opportunistic work without delaying the 1.0 foundation.

The immediate next architecture milestone is therefore **PAGE-FLOW-01**, beginning again with real Writer/ODF evidence and characterization rather than API invention.

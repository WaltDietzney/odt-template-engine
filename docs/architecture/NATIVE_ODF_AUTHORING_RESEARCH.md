# RESEARCH-01 — Native ODF Authoring Capabilities

## Status

**Research / discovery. No public API or implementation decision is implied by this document.**

RESEARCH-01 investigates which authoring, template, layout, style, page-flow, and addressable-object semantics are already provided natively by ODF and LibreOffice Writer, and how the ODT Template Engine should make use of them.

The research is intentionally prior to the next major implementation milestone. Its results will be used to define the remaining scope for version 1.0, post-1.0 development, and—only after the technical and product foundations are understood—possible commercial capabilities built on top of the engine.

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

**Research priority: 1**

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

The following findings were established with small LibreOffice Writer fixtures and direct inspection of `content.xml`. They characterize observed Writer/ODF behavior; they do not yet define engine APIs.

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

Conditional Writer Sections proved to be the strongest native structural-selection mechanism investigated in RESEARCH-01A so far.

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

The fixture was verified manually for `female`, `male`, and a third/default value. Parenthesized comparisons in the OR expression produced the intended default behavior. This observation does not yet establish that parentheses are universally required by Writer's condition grammar.

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

For PDF, LibreOffice can already evaluate the characterized native conditions during headless rendering. A common explicit finalization stage may nevertheless be valuable if multiple export formats must receive the same resolved document state. Whether such a stage becomes an engine responsibility is an architecture decision for a later phase, not a conclusion of RESEARCH-01A itself.

##### Field scope and section instantiation

The field experiments expose an important scope distinction for repeated native structures.

A Writer User Field has a central declaration. Cloning a Section containing several `text:user-field-get` references would therefore clone references to the same document-global field rather than automatically create item-local values for each Section instance. That makes User Fields a natural candidate for document-global data, but not an automatic replacement for item-local placeholders inside a repeated collection block.

Set Variable / Show Variable has different, position-dependent document-flow semantics and may therefore interact differently with cloned Sections. This remains an empirical research question. No field-localization or field-identity-rewriting mechanism is currently assumed.

The current working preference is therefore deliberately conservative:

> `{{variable}}` remains the preferred general and portable data-binding mechanism unless a native Writer field provides a clear semantic or authoring advantage.

This preference is strengthened by DOCX interoperability: engine placeholders can be fully materialized to ordinary document content before conversion, whereas native field and conditional semantics may not survive conversion consistently.

Native Writer fields remain valuable research candidates, especially for document-global values, Writer-authored conditions, and cases where native field semantics provide a concrete authoring benefit. RESEARCH-01 does not currently aim to replace the existing placeholder model with Writer fields.

##### Relationship to engine placeholders

The experiments now suggest a more specific hybrid model rather than a competition between native Writer semantics and the existing template language.

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

The current engine already has structured Section instantiation semantics, including repeated instantiation, local scalar binding, deterministic identity rewriting, nested Section resolution, prototype removal, and rollback behavior. This suggests a declarative authoring layer that should be investigated rather than a second structural-processing implementation.

A Writer-authored named Section could declare repetition through a semantic name such as:

```text
#foreach:experience
```

A manual LibreOffice authoring experiment confirmed that Writer accepts a Section name in this form in the Section editor. This is useful authoring evidence, but it is not yet sufficient to approve the syntax. The resulting ODF name, save/reopen stability, cloning behavior, and relevant conversion behavior should still be characterized before a naming contract is chosen.

Conceptually, the template would declare that the Section represents a repeatable block bound to the `experience` collection. Internally, the engine could map that declaration to its existing named-Section and `instantiateMany()` semantics:

```text
LibreOffice Section: #foreach:experience
    -> discover structural declaration
    -> resolve collection: experience
    -> instantiateMany()
    -> bind {{...}} values in each local instance
    -> finalize prototype/result
```

The important architectural property is ownership:

- the **template** decides that this native document block is repeatable and owns its layout, styles, and internal structure;
- the **engine** interprets the declaration, resolves the collection, performs structured instantiation, validates the operation, and binds each local data context;
- the **application** supplies data without having to restate document structure in PHP.

This is not merely a hidden form of the existing text-based loop. The native Section itself provides the structural boundary, so no textual `#endforeach` marker is required. ODT structure replaces part of the control syntax.

The same principle may be useful for a deliberately small set of simple structural operators. Current research candidates are:

```text
#foreach:experience
#if:profile
#ifnot:photo
```

These are **research candidates, not approved syntax**. In particular, `#if`/`#ifnot` still require semantic and lifecycle analysis before any implementation decision.

Complex expressions should not automatically be pushed into Section names. Syntax such as:

```text
#if:(country==DE && age>=18) || privileged
```

would recreate a general expression language inside native object names and undermine the goal of clear Writer authoring. Writer's own field and conditional mechanisms are a more plausible candidate for complex conditions where their semantics and export behavior are suitable.

This suggests a possible division of labor:

```text
{{name}}, {{upper:name}}, ...
    -> value binding and value formatting

#foreach:experience, #if:profile, ...
    -> simple engine-owned structural operations declared by native ODT objects

Writer User Fields / native conditions
    -> richer Writer-owned conditional semantics where justified
```

No final operator set is decided. Additional candidates such as local-scope constructs (`#with`) should be added only in response to demonstrated template needs, not for language completeness.

##### Existing SECTION-03 and inspection relationship

Repository review confirms that declarative repetition would primarily add a mapping/discovery layer rather than a second collection engine. Existing SECTION-03 behavior already characterizes collection instantiation, item-local scalar binding, deterministic instance naming, prototype removal for `instantiateMany()`, empty collections, rollback on failure, nested collection instantiation, and save/reopen behavior.

The current `TemplateStructureInspector` should not simply be expanded into a generic native-semantic parser. Its responsibility is inspection of visible `{{...}}` template-language expressions across ODF text-flow scopes. It already recognizes `text:section` as a text-flow boundary and can report expression scopes such as `section:<name>`, which provides a useful bridge without conflating the two models.

This suggests two conceptually distinct inspection concerns:

```text
native ODT document structure
    -> sections, tables, frames, bookmarks, ...

visible template expressions
    -> {{...}} and current textual control expressions
```

A future declarative structural layer would interpret semantic declarations carried by native objects and map them to existing structured operations. Its architecture, naming, diagnostics, and relationship to existing inspection APIs remain design questions.

##### Template / Engine / Application responsibility model

The research increasingly points toward a three-party responsibility model:

| Layer | Primary responsibility |
| --- | --- |
| LibreOffice template | Own visual/native document structure, styles, layout, and selected structural declarations |
| Engine | Interpret declarations, bind data, perform safe structured transformations, validate, rewrite required identities, and possibly materialize final export state |
| Application | Supply business/application data and explicitly requested orchestration that does not belong to the template |

This boundary is important not only for convenience but for authority: each layer should have a clear answer to **who owns a structure, who may transform it, and who supplies its data**.

A declarative Section such as `#foreach:experience` would move the decision that a block is repeated from application code into the template while keeping the transformation itself engine-owned. The application would no longer need to know that `ExperienceEntry` must be cloned; it would only supply `experience` data.

##### Interim semantic map

The current evidence supports the following research map, without yet making it a public API contract:

| Need | Current preferred/candidate mechanism |
| --- | --- |
| Scalar application value | `{{variable}}` |
| Scalar formatting | existing/simple `{{filter:variable}}` filters |
| Document-global Writer value | User Field where native semantics provide value |
| Binary text/value selection | Conditional Text where appropriate |
| Optional inline content | Hidden Text where appropriate |
| Optional paragraph | Hidden Paragraph where appropriate |
| Simple optional complex block | native Section declaration such as `#if:...` under investigation |
| Complex Writer-owned condition | native Writer condition / Conditional Section |
| Repeatable complex block | native named Section + existing structured instantiation; `#foreach:...` under investigation |

The strongest current working direction is therefore:

> **Use native ODT objects for document structure and selected structural template semantics, keep `{{...}}` for simple portable value binding, and use Writer field/condition semantics selectively where they add genuine authoring or document-semantic value.**

This direction deliberately avoids both extremes: rebuilding Writer semantics in PHP and replacing a simple, portable placeholder mechanism merely because a native field mechanism exists.

### RESEARCH-01B — Sections and Native Layout

**Research priority: 2**

Investigate Writer sections as both semantic template objects and native layout containers.

Topics include:

- single- and multi-column sections;
- equal and unequal column widths;
- column gaps and separators;
- section styles and their location in ODF;
- backgrounds, borders, and other section formatting;
- text flow through columns;
- nested sections;
- named sections as layout blocks;
- interaction with current section cloning and instantiation;
- repeatable structures inside and outside multi-column sections;
- interaction with conditional sections and field semantics;
- suitability for CV/sidebar and other professional document layouts.

The research must determine how much layout can remain LibreOffice-authored while PHP only addresses, selects, clones, or binds structured content.

### RESEARCH-01C — Native Style Semantics

**Research priority: 3**

Investigate Writer/ODF style families from the perspective of template authorship and application authorship.

Topics include:

- paragraph styles;
- character/text styles;
- page styles;
- frame/graphic styles;
- list styles;
- table-related styles and Writer table-style behavior;
- inheritance and parent-style relationships;
- automatic versus named styles;
- style references versus definitions;
- authored-template styles versus generated styles;
- style precedence and document defaults where relevant.

This research builds on the completed STYLE-CONTEXT-01 and STYLE-API-02 architecture. It must not reintroduce global registries or generic style APIs merely for symmetry.

A key product question is when applications should reference LibreOffice-authored named styles instead of constructing equivalent formatting in PHP.

### RESEARCH-01D — Page Styles and Document Flow

**Research priority: 4**

Investigate the native mechanisms required for reliable professional multi-page documents.

Topics include:

- page styles and master pages;
- first-page versus following-page layouts;
- transitions between page styles;
- page margins;
- headers and footers;
- page numbering;
- explicit page breaks;
- keep-with-next;
- keep-together;
- widow/orphan behavior;
- paragraph pagination properties;
- interaction between paragraph styles and page flow;
- interaction with dynamically instantiated content.

A professional CV is an important benchmark, but conclusions must remain generally useful for reports, letters, offers, invoices, and other ODT documents.

### RESEARCH-01E — Named and Addressable Native Objects

**Research priority: 5**

Survey additional native ODF/Writer structures that may be useful as addressable template objects.

Existing engine concepts include sections, bookmarks, tables, and frames. Research should determine which additional native structures have stable identity and useful semantics, including where relevant:

- text boxes;
- images and image-bearing frames;
- reference marks;
- fields;
- lists;
- page/master-style related structures;
- other Writer objects discovered during empirical research.

Do not assume that every addressable object requires the same operations. Replacement, content replacement, selection, cloning, instantiation, and removal are distinct capabilities.

## 4. Cross-cutting research: Authoring and Developer Experience

Authoring UX is not a separate late-stage cosmetic concern. It must be evaluated throughout RESEARCH-01.

### 4.1 Template Author UX

Ask for every candidate mechanism:

- Can a non-programmer understand the template in LibreOffice?
- Does the template still look substantially like the resulting document?
- Can content and formatting be changed without editing PHP?
- Is control information visible only where it helps the author?
- Does the mechanism cause layout drift in the editable template?
- Can the author discover and inspect the relevant object through normal Writer tools such as styles, fields, sections, or the Navigator?

### 4.2 Developer UX

Ask in parallel:

- Can a PHP developer use the capability without knowing internal ODF XML details?
- Are missing/invalid template structures diagnosable?
- Can the engine inspect and report what a template contains?
- Is the distinction between template-owned and application-owned structure clear?
- Can capability-specific APIs remain simpler than generic object manipulation?

Possible later capabilities include improved inspection, validation, diagnostics, naming guidance, and CLI tooling. These are candidates, not approved APIs.

## 5. Benchmarks

### 5.1 Sample 10 — Template Authoring UX benchmark

Sample 10 represents the strengths and limitations of visible template-language authoring. Variables and simple filters can remain compact, while structural controls such as multi-line conditions and loops can make the editable document diverge significantly from its rendered appearance.

RESEARCH-01 should use Sample 10 to compare native alternatives with the existing syntax rather than assuming either approach is universally superior.

### 5.2 Sample 25 — Structured Document benchmark

Sample 25 represents the newer model in which LibreOffice-authored native structure and PHP-driven data binding/instantiation cooperate.

Research should preserve the architectural lesson that native structure can remain template-owned while the engine provides typed, semantic operations over it.

### 5.3 Future professional authoring proof

After research and prioritization, a future sample may prove the combined model with capabilities such as:

- authored named styles;
- first/following page styles;
- multi-column sections;
- conditional native content;
- repeatable named sections;
- images;
- pagination controls such as keep-with-next;
- a template that remains understandable in LibreOffice before rendering.

This is a target benchmark, not an approved implementation task.

## 6. Research evidence and fixtures

Where Writer behavior is not obvious from the ODF specification or existing repository evidence, prefer small empirical LibreOffice fixtures.

For each fixture, record:

- the authoring steps in LibreOffice;
- relevant `content.xml` and `styles.xml` structures;
- behavior before and after Writer save/reopen;
- behavior after engine load/render/save where relevant;
- headless export behavior where relevant;
- interaction with cloning, instantiation, or nesting where relevant.

Characterization tests should be added when a discovered behavior becomes important to current or planned engine semantics.

Do not turn exploratory fixtures into permanent repository artifacts without deciding their long-term purpose.

## 7. Post-research capability assessment

After the research areas are sufficiently understood, candidate capabilities will be evaluated separately from research order.

At minimum, assess:

- **User value** — how commonly and materially the capability helps real documents;
- **Template-author value** — how much it improves visual/redactional authoring in LibreOffice;
- **Developer value** — how much complexity it removes from application code;
- **Architectural leverage** — which later capabilities depend on it;
- **ODF fidelity** — whether it uses stable native semantics rather than fragile reconstruction;
- **implementation and compatibility risk** — how deeply it affects current behavior and APIs.

Research priority must not be mistaken for implementation priority.

## 8. Version 1.0 and later product planning

RESEARCH-01 should conclude with enough evidence to define a deliberate product boundary.

The resulting planning pass should classify candidate work into at least:

1. **Required for version 1.0** — capabilities needed for a coherent, reliable, professionally useful core engine.
2. **Post-1.0 development** — valuable capabilities that do not need to delay a stable 1.0 release.
3. **Commercial/product-layer candidates** — optional higher-level tooling or packaged capabilities that may be suitable for a paid offering after the open engine foundation is understood.

No open-source/commercial boundary is decided by RESEARCH-01 itself. Fundamental document correctness, stable ODF semantics, and a coherent core API must not be weakened merely to manufacture a commercial distinction.

Potential commercial value should be assessed at the product/tooling layer only after technical dependencies and user value are known.

## 9. Expected RESEARCH-01 outcome

RESEARCH-01 is complete when it provides:

- an evidence-based map of relevant native ODF/Writer capabilities;
- explicit findings for fields/conditions, sections/layout, styles, page flow, and addressable native objects;
- a clear account of what the current engine already supports, preserves, or conflicts with;
- identified gaps without premature API invention;
- an authoring-UX assessment using the existing samples as benchmarks;
- a prioritized capability set for the next implementation phase;
- a proposed version 1.0 boundary;
- a post-1.0 backlog adjustment;
- a separately reasoned assessment of possible commercial/product-layer capabilities.

## 10. Immediate next step

Complete the remaining RESEARCH-01A characterization of declarative Section naming and field/Section scope, including the exact ODF representation and save/reopen behavior of a Writer Section named like `#foreach:experience`.

Then evaluate the render lifecycle ordering required for declarative structural processing (`data -> structural expansion/selection -> local scalar binding -> finalization`) before moving to **RESEARCH-01B — Sections and Native Layout**.

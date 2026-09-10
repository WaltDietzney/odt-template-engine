# PAGE-FLOW-01C — Section and Structured-Content Flow Evidence

## Status

**Research evidence. No public API or implementation decision is implied by this document.**

This companion document records the normative ODF analysis, Writer-authored empirical evidence, current SECTION-03 code analysis, automated characterization, and manual LibreOffice regression evidence collected for PAGE-FLOW-01C. It supplements `PAGE_FLOW_01_RESEARCH_AND_DESIGN.md` and should be read under the governing PAGE-FLOW-01 rule:

> **The engine describes or preserves native ODF flow semantics; LibreOffice/Writer computes actual pagination.**

PAGE-FLOW-01C is considered empirically complete for its bounded research question. Remaining architecture decisions belong to the later PAGE-FLOW-01 synthesis and Change Contract.

## 1. Research method

PAGE-FLOW-01C used a more efficient evidence order than the initial fixture-by-fixture research plan:

1. consult the ODF specification for normative structure, ownership, and allowed properties;
2. compare those semantics with the current SECTION-03 implementation;
3. use one bundled Writer-authored fixture containing clearly separated cases;
4. add isolated experiments only where normative semantics, Writer serialization, interoperability, or engine behavior remain uncertain;
5. characterize the relevant engine path automatically;
6. perform one final manual LibreOffice regression on engine-generated repeated content.

The practical rule is:

> **Spec first. Writer verification second. Isolated experiments only where semantics, serialization, interoperability, or engine behavior remain uncertain.**

This method avoids using Writer experiments to rediscover semantics already defined by ODF while retaining empirical validation where the engine and Writer interact.

## 2. Normative ODF model of `text:section`

### 2.1 Section is a structural container, not a pagination container

ODF defines `text:section` as a named structural content region. A Section can reference a Section style and can contain ordinary document structures including paragraphs, headings, lists, tables, and nested Sections.

The relevant model is:

```text
text:section
├── structural identity / name
├── optional Section-style reference
└── native contained document content
```

The Section-style family and its Section properties do not provide the paragraph-flow vocabulary characterized in PAGE-FLOW-01B. In particular, the researched Section properties do not define a generic Section-level equivalent of:

```text
fo:keep-with-next
fo:keep-together
fo:break-before
fo:break-after
fo:widows
fo:orphans
```

This supports the bounded conclusion:

> **A native ODF Section is not a generic "keep this whole block on one page" pagination primitive.**

Flow behavior is expressed by the contained native elements and their styles, while Writer computes physical pagination.

### 2.2 Paragraph flow remains paragraph semantics

A Section may contain ordinary paragraphs. Their paragraph styles retain the flow semantics established in PAGE-FLOW-01B.

Conceptually:

```text
Section "Entry"
├── period paragraph
├── position paragraph      → paragraph-flow semantics
├── company paragraph       → paragraph-flow semantics
└── description paragraph   → paragraph-flow semantics
```

Structural grouping and pagination constraints are therefore separate semantic layers.

### 2.3 Nested Sections are native ODF structure

ODF permits `text:section` to contain another `text:section`. Nested Sections are therefore not a SECTION-03 invention or a LibreOffice-specific workaround.

Nesting does not by itself introduce a separate pagination authority. Each nested Section remains a structural region containing native flow-bearing elements.

### 2.4 Tables and lists remain native contained structures

A Section may contain a native table or list. The Section does not flatten or reinterpret those structures.

Tables have their own flow/layout semantics. Detailed table pagination remains within the later TABLE-LAYOUT-01 milestone rather than being pulled into PAGE-FLOW-01C.

ODF structure also constrains Section/list overlap: a list may be contained by a Section, but arbitrary Section boundaries must not be assumed to be legal inside list items. This is relevant to long-term structured-document authoring but does not require a new PAGE-FLOW-01C API.

## 3. Writer-authored bundled fixture

A single Writer research document, `page-flow-01c-section-flow.odt`, was used to cover the relevant Section cases. It is an exploratory research artifact rather than a public sample.

### 3.1 C1 — normal Section across a page boundary

`PF01C_NormalSection` contained ordinary paragraphs and was deliberately long enough to cross a physical page boundary.

Writer paginated the content across pages and stored a `text:soft-page-break` inside the Section content.

This empirically confirms:

> **Writer does not treat a normal `text:section` as an indivisible page block.**

The `text:soft-page-break` remains a calculated/cached pagination marker as characterized in PAGE-FLOW-01B; it is not a Section-level semantic break request.

### 3.2 C2 — paragraph-flow styles inside a Section

`PF01C_ParagraphFlow` contained paragraphs referencing named paragraph styles. The inspected Writer styles included a useful inheritance case:

```text
PF01CPosition
├── parent: Standard
├── keep-with-next: always
└── keep-together: always

PF01CCompany
└── parent: PF01CPosition
```

The fixture therefore provided evidence not only that paragraph-flow styles operate inside a Section, but also that flow semantics may reach a Section child through ordinary named-style inheritance.

This was an authoring consequence of the Writer style hierarchy, not special Section behavior.

The important architectural observation is:

> **A Section boundary does not replace normal paragraph-style ownership or inheritance.**

### 3.3 C3 — nested Sections across a page boundary

The fixture contained a true nested hierarchy:

```text
PF01C_Outer
├── outer paragraph
├── PF01C_Inner
│   ├── inner heading
│   ├── inner body 1
│   └── inner body 2
└── outer paragraph
```

Writer stored a calculated page boundary inside content of the nested `PF01C_Inner` Section.

This empirically confirms that nested native Sections can participate in normal document flow across physical page boundaries. Nesting does not make the inner Section an indivisible pagination block.

### 3.4 C4 — table and list inside a Section

`PF01C_ComplexContent` contained both a native table and a native list.

The inspected structure remained conceptually:

```text
PF01C_ComplexContent
├── paragraph
├── table:table
└── text:list
```

The table retained its own table-style reference and the list retained its own list structure/style reference.

This confirms the bounded PAGE-FLOW-01C concern: Section containment preserves the native structural identity of tables and lists. Detailed table/list page-boundary behavior is not reimplemented by Section semantics and is deferred to the appropriate later milestone where necessary.

### 3.5 C5 — repeatable SECTION-03 prototype

The corrected C5 fixture contained a real named Section prototype:

```xml
<text:section text:style-name="Sect1" text:name="PF01C_Entry">
    <text:p text:style-name="PF01CMeta">{{period}}</text:p>
    <text:p text:style-name="PF01CHeading">{{position}}</text:p>
    <text:p text:style-name="PF01CCompany">{{company}}</text:p>
    <text:p text:style-name="Standard">{{description}}</text:p>
</text:section>
```

This provided the bridge from Writer-authored native structure to the existing SECTION-03 `instantiateMany()` path.

## 4. Current SECTION-03 implementation analysis

The current implementation is structurally aligned with the ODF model required by PAGE-FLOW-01C.

### 4.1 Deep cloning preserves the native subtree

SECTION-03 cloning begins from a deep DOM clone of the source Section subtree. Deterministic identity rewriting then updates identities that must become unique, such as Section names and other named/native identities.

Paragraph-style references are not pagination state and are not part of the identity rewrite responsibility.

The expected preservation model is therefore:

```text
prototype Section
├── Section identity                 → rewritten as required
├── nested named identities          → rewritten as required
├── paragraph style references       → preserved
├── Section style reference          → preserved
├── table/list structure             → preserved
└── native contained flow semantics  → preserved through their references/structure
```

### 4.2 Instantiation binds local content without becoming a layout engine

SECTION-03 instantiation clones the native subtree and performs local template binding within the clone. `instantiateMany()` repeats that structural operation and removes the prototype according to the established collection semantics.

No SECTION-03 path is responsible for calculating page height, predicting page count, or choosing physical page boundaries.

This matches the native model:

> **`instantiateMany()` is a structural operation, not a pagination operation.**

## 5. Automated preservation characterization

The durable automated characterization is:

`tests/Integration/PageFlow01CSectionFlowPreservationTest.php`

The test models the relevant Writer-authored C5 structure and characterizes `instantiateMany()` across save/reopen.

It verifies that:

- three instances are created with deterministic Section identities;
- the collection prototype is removed;
- each instance retains the authored Section style reference `Sect1`;
- each instance retains the exact paragraph-style reference sequence:

```text
PF01CMeta
PF01CHeading
PF01CCompany
Standard
```

- template expressions are bound rather than left in the generated content;
- `PF01CPosition` retains its authored `fo:keep-with-next="always"` and `fo:keep-together="always"` properties;
- `PF01CCompany` retains its `style:parent-style-name="PF01CPosition"` inheritance relationship;
- generated Sections remain addressable after `save()` and reopen through `OdtTemplate`.

The focused local preflight completed successfully with:

```text
Tests: 1
Assertions: 61
Warnings: 0
```

PHP syntax validation and `git diff --check` were also clean.

An initial version of the synthetic fixture exposed missing namespace declarations for attributes copied/finalized into `content.xml`. The fixture was corrected to declare the required namespaces. The resulting warnings were fixture-construction issues, not SECTION-03 flow-preservation failures.

## 6. Manual LibreOffice regression after `instantiateMany()`

A final local regression generated multiple C5 entry instances from the Writer-authored prototype and opened the resulting ODT in LibreOffice Writer.

The generated entries contained bound values for period, position, company, and description and extended far enough to exercise normal multi-page flow.

Visual inspection confirmed the behavior required by PAGE-FLOW-01C:

- bound Section instances appeared in document order;
- no `{{...}}` template expressions remained in the generated entry content;
- the repeated content participated in ordinary Writer page flow;
- Writer was free to place physical page boundaries according to the preserved paragraph/style semantics and available page space;
- there was no evidence that SECTION-03 attempted to keep an entire Section on one page;
- no visible Section corruption or structural loss was observed;
- the intentionally plain research fixture was not treated as a production-layout benchmark.

The visual result was not intended to be aesthetically polished. PAGE-FLOW-01C deliberately separates flow correctness from CV/layout design.

## 7. Evidence matrix

| Research question | Evidence | Result |
| --- | --- | --- |
| Can a normal Section span a page boundary? | ODF model + Writer C1 | Yes; Section is not an indivisible page block |
| Do paragraph-flow rules remain active inside a Section? | ODF model + Writer C2 | Yes |
| Can flow semantics arrive through named-style inheritance? | Writer C2 + style inspection | Yes; ordinary style inheritance remains relevant |
| Can nested Sections cross page boundaries? | ODF model + Writer C3 | Yes |
| Can a Section contain a native table? | ODF model + Writer C4 | Yes; table remains native structure |
| Can a Section contain a native list? | ODF model + Writer C4 | Yes; list remains native structure |
| Does SECTION-03 preserve Section style references during collection instantiation? | automated characterization | Yes |
| Does SECTION-03 preserve paragraph style references? | automated characterization | Yes |
| Does SECTION-03 preserve inherited flow-style relationships? | automated characterization | Yes |
| Does the result survive engine save/reopen? | automated characterization | Yes |
| Does repeated generated content remain usable under Writer pagination? | manual LibreOffice regression | Yes |

## 8. PAGE-FLOW-01C conclusions

The evidence is sufficient to state the following without making a public API decision:

1. **A native `text:section` is primarily a structural ownership/container primitive, not a generic pagination container.**
2. **There is no evidenced need for a Section-level "keep whole Section together" abstraction in PAGE-FLOW-01.**
3. Paragraph-flow semantics remain owned by paragraph styles and paragraphs inside Sections.
4. Named-style inheritance remains ordinary style semantics across Section boundaries.
5. Nested Sections remain native structural containers and may participate in normal flow across page boundaries.
6. Tables and lists retain their own native structure and flow/layout responsibilities when contained by a Section.
7. SECTION-03's deep-clone/identity-rewrite/bind model is aligned with these semantics.
8. `instantiateMany()` must remain structural; it must not acquire PHP-side pagination responsibility.
9. The current SECTION-03 path preserves the authored Section style, paragraph-style references, inherited flow-style relationships, and bound content across save/reopen.
10. LibreOffice Writer remains the authority that turns those preserved semantic constraints into physical page boundaries.

The resulting architecture statement is:

> **Sections provide structural ownership; contained native elements provide their own flow semantics; Writer remains the pagination authority. SECTION-03 preserves those semantics rather than interpreting or reproducing them.**

## 9. Scope consequences

PAGE-FLOW-01C does not justify:

- a PHP pagination engine;
- a `keepSectionOnPage()` convenience API;
- page-height or remaining-space calculations;
- CV-specific entry pagination logic;
- pulling detailed table pagination into PAGE-FLOW-01;
- rebuilding Writer-authored Section content during instantiation.

Generated PHP-owned paragraphs may still need fuller paragraph-flow authoring support as identified by PAGE-FLOW-01B. That is a paragraph-style capability question, not Section pagination logic.

Detailed table geometry and table-specific page behavior remain bounded to TABLE-LAYOUT-01 where applicable.

## 10. Research status and next step

For the bounded Section/structured-content question, PAGE-FLOW-01C is **empirically complete**.

No additional isolated Writer fixture is currently justified unless later PAGE-FLOW research exposes a specific Section interaction that contradicts these findings.

The next research area is PAGE-FLOW-01D: headers, footers, and page-owned content. PAGE-FLOW-01D should build on the ownership facts already established in PAGE-FLOW-01A rather than rediscovering them.

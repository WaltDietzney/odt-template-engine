# TEMPLATE-AUTHORING-01F — B02 Professional Report Builder Change Contract

**Status:** Approved  
**Milestone:** TEMPLATE-AUTHORING-01F — Authoring Documentation & Samples  
**Sample:** B02 — Professional Report Builder  
**Base:** `develop`

## 1. Purpose

B02 demonstrates that the existing structured authoring APIs of the ODT Template Engine can be composed into a professional, multi-page, editable ODT report.

B02 is simultaneously:

- a public Builder sample;
- an integration showcase for existing structured authoring capabilities;
- a visual quality benchmark;
- a technical benchmark for combining already completed architecture milestones;
- a design reference for the later S03 Structured Professional Report.

The central Builder statement is:

> **PHP constructs a structured, professional and editable report using the existing ODT document model.**

B02 does **not** introduce new engine semantics.

## 2. Relationship to S03

B02 and S03 deliberately explore two different authoring models.

**B02:** PHP constructs the report.

**S03:** LibreOffice Writer constructs/designs the report; the engine addresses and modifies meaningful native document structures.

B02 must not anticipate S03 by introducing new named-object mutation APIs. The visual and domain concept developed for B02 may later serve as the design reference for S03.

## 3. Fictional sample identity

All organizations, programs, people and report data must be unmistakably fictional.

Working identity:

- **Asteria Demo Foundation**
- **Pathways 360 Demo Program**
- **Annual Performance Report 2026**
- Reporting period: **January–December 2026**

The report must contain a clearly visible notice:

> **SAMPLE DOCUMENT · FICTIONAL ORGANIZATION AND DATA**

A fuller disclaimer should appear in an appropriate secondary location:

> This document is a demonstration sample created for the ODT Template Engine. All organizations, programs, persons, figures and report data shown in this document are fictional and are used solely for demonstration purposes.

No plausible real-world postal addresses, telephone numbers, personal email addresses or accidentally real organizational domains should be invented. Any branding created for Asteria must also be fictional.

## 4. Document objective

The output should look and behave like a report that could plausibly be delivered to management, a commissioning organization, program partners, or stakeholders.

It must **not look primarily like a technical test document**.

A reader should first perceive:

> “This is a professional report.”

Only after examining its implementation should a developer perceive:

> “This was constructed programmatically with the ODT Template Engine.”

## 5. Content model

The report uses six conceptual regions. Actual pagination remains Writer-controlled; these are document regions, not pixel-fixed pages.

### Region 1 — Cover

Contains:

- fictional Asteria branding/logo;
- prominent visual;
- `Annual Performance Report 2026`;
- `Pathways 360 Demo Program`;
- reporting period;
- final-report date/status;
- clearly visible fictional/sample notice.

The cover forms its own page.

### Region 2 — Executive Summary

Contains:

- `Executive Summary`;
- approximately 180–250 words of realistic report prose;
- four headline KPIs:
  - 248 participants;
  - 87% completion rate;
  - 64% positive outcomes;
  - 42 employer partners;
- highlighted `Key Finding`.

The prose must read as actual management reporting rather than filler text.

### Region 3 — Program Overview & Delivery

Contains a concise program description, reporting period, number of locations, participants, delivery team, employer partners, program objectives, and activities delivered.

Activities table:

| Activity | Delivered | Participants |
| --- | ---: | ---: |
| Individual coaching sessions | 1,146 | 231 |
| Skills workshops | 84 | 218 |
| Employer events | 18 | 156 |
| Digital skills sessions | 46 | 174 |

### Region 4 — Performance & Outcomes

Contains a target/actual performance table:

| Indicator | Target | Actual | Status |
| --- | ---: | ---: | --- |
| Participants enrolled | 240 | 248 | Achieved |
| Program completion | 80% | 87% | Above target |
| Employment / training outcome | 60% | 64% | Above target |
| Employer partners | 35 | 42 | Above target |
| Digital skills completion | 75% | 78% | Achieved |

Also contains an outcomes visualization using approximately:

- Employment — 41%
- Further training — 23%
- Active progression — 21%
- Other / no recorded progression — 15%

The visualization is inserted as an image resource. **No native ODF chart support is introduced.**

A short interpretation follows the visualization.

### Region 5 — Findings & Analysis

Contains substantive prose rather than only metrics, including integrated support and outcomes, differences in digital confidence, employer engagement, and delivery challenges.

Contains a visually distinct **PROGRAM INSIGHT** callout and a concise Challenges area.

### Region 6 — Recommendations & Outlook

Contains approximately three or four recommendations, including earlier/deeper employer involvement, differentiated digital support, and improved outcome tracking.

Ends with a short 2027 outlook, organization/program identification, and document closing information.

## 6. Visual design contract

B02 uses a restrained professional editorial design.

Desired:

- contemporary;
- professional;
- calm;
- information-oriented;
- generous whitespace;
- clear hierarchy.

Not desired:

- dashboard appearance;
- excessive colored boxes;
- decorative office-template appearance;
- full-grid tables everywhere;
- web-page styling transferred literally to ODT;
- visual clutter.

### Color system

Use one dark primary tone, one restrained accent color, dark neutral body text, light neutral backgrounds, and white. Additional colors should not be introduced merely to decorate statuses.

### Typography

Establish a coherent hierarchy approximately corresponding to:

- Report Title: 28–34 pt
- H1: 18–20 pt
- H2: 12–14 pt
- Body: 9.5–10.5 pt
- Metadata/captions: 8–9 pt

Exact values may be refined during implementation and LibreOffice visual review. Hierarchy should rely on size, spacing, position, restrained color, and weight where appropriate.

## 7. Table design contract

Tables should demonstrate professional report typography rather than default office grids.

Preferred characteristics:

- clear header treatment;
- appropriate column proportions;
- numerical alignment;
- controlled cell padding;
- restrained row separation;
- minimal or absent vertical borders;
- intentional whitespace;
- consistent typography.

`RichTable` and `RichTableCell` are the intended existing structured mechanisms.

The KPI strip may also use a `RichTable` as a stable horizontal layout structure. This does not establish a general architectural rule that tables are the preferred layout mechanism for arbitrary document design.

## 8. Images and graphics

B02 should use two distinct visual roles:

- **Cover visual:** document identity / emotional visual emphasis.
- **Outcome chart:** information visualization.

Images must be locally available sample assets with suitable licensing/origin for repository inclusion.

The chart should be a prepared image asset. It must not trigger implementation of native chart authoring.

Image/frame geometry may use existing `ImageElement` and Frame Layout capabilities. If achieving the cover requires excessive coordinate-driven PHP layout, move appropriate static geometry into the Writer prototype instead.

## 9. Page-flow semantics

B02 should deliberately exercise the completed PAGE-FLOW-01 capabilities, including:

- cover ending before report body;
- major sections starting deliberately where appropriate;
- headings kept with following content;
- sensible paragraph cohesion;
- widows/orphans where relevant;
- avoiding isolated headings;
- appropriate table/content grouping.

Writer remains responsible for **physical pagination**.

B02 must not implement a custom pagination engine or rely on fragile assumptions such as content beginning at an absolute vertical coordinate.

The intended output is approximately **5–7 pages**, but exact page count is not an architectural invariant.

## 10. Existing capabilities intended for composition

B02 is expected to exercise an appropriate subset of:

- `OdtTemplate`;
- `RichText`;
- `Paragraph`;
- `ListElement`;
- `RichTable`;
- `RichTableCell`;
- `ImageElement`;
- `DrawTextBox`;
- existing Frame Layout;
- existing paragraph/text style support;
- existing Table Layout support;
- existing Page Flow semantics;
- Document Defaults where appropriate;
- metadata support.

Use must follow the current public API and existing architecture on `develop`. Protected/internal APIs must not be promoted merely to make the sample convenient.

## 11. Explicit non-goals

B02 must **not introduce or require**:

- Native Table Row Repeat;
- replacement/mutation semantics for Writer-authored native tables;
- universal Semantic Named Elements;
- generalized `replaceNamedElement()`;
- programmatic Writer `SectionElement`;
- new Section authoring semantics;
- `CLASSIC-FOREACH-SCOPE-01`;
- PAGE-STYLE-AUTHORING-01;
- STYLE-API-02 or STYLE-CONTEXT-01 extensions;
- native ODF charts;
- a new layout engine;
- absolute/pixel-oriented pagination;
- a general report abstraction/framework;
- a new mapping layer merely for B02.

No speculative API should be introduced for anticipated S03 needs.

## 12. Template responsibility

B02 is a **Builder**, but “Builder” does not mean PHP must recreate every static Writer concern.

The prototype/template may own appropriate document-level presentation concerns such as page size, margins, static header/footer infrastructure, basic page regions, and other Writer-native static layout where appropriate.

PHP owns the structured report content and demonstrates its construction through the public element model. This boundary should remain understandable from the sample source.

## 13. Sample code quality

The public sample must remain educational.

It should not become a single enormous procedural PHP file merely because the report is large. At the same time, B02 must **not introduce a speculative report framework** simply to make the sample shorter.

Use small local builder/helper functions where they improve readability, with ordinary existing engine objects underneath. A reader should still be able to understand how the report is constructed.

## 14. Compatibility contract

B02 is additive and must not intentionally change existing behavior.

In particular:

- no existing public API changes;
- no render lifecycle changes;
- no changes to `render()/save()` semantics;
- no protected compatibility facade removal;
- no changes to classic foreach semantics;
- no changes to native object mutation semantics;
- no changes to `content.xml` / `styles.xml` processing merely for convenience.

If implementation appears to require such a change, B02 enters **BLOCKED** state pending architecture review.

## 15. BLOCKED conditions

Coding work must stop and return for architecture discussion if:

1. the approved visual design cannot reasonably be achieved using existing public APIs;
2. an engine bug appears to prevent legitimate use of an already-supported capability;
3. implementation requires a new public API;
4. implementation requires changing existing semantics;
5. implementation requires relying on undocumented internal behavior;
6. Writer output differs materially from the intended structure in a way that cannot be solved at sample/template level;
7. achieving the design requires fragile coordinate manipulation inconsistent with the current layout architecture.

A discovered bug is **not automatically permission to refactor or redesign the affected subsystem**.

## 16. Automated verification

At minimum, B02 should gain appropriate smoke/integration coverage verifying structural facts rather than screenshot appearance.

Tests should establish appropriate facts such as:

- sample executes successfully;
- resulting ODT exists and is structurally valid enough for the existing test infrastructure;
- expected report text exists;
- expected table structures exist;
- expected image/frame resources exist;
- intended major structured elements were generated;
- no unresolved template markers remain where applicable.

`PublicSampleSmokeTest` should include B02 if consistent with existing sample policy.

Automated tests must **not attempt to encode subjective visual quality as XML assertions**.

## 17. Manual LibreOffice acceptance

B02 requires manual LibreOffice inspection. This is mandatory because its purpose includes professional visual composition.

Review should examine:

- cover;
- typography;
- whitespace;
- page flow;
- headings near page boundaries;
- table appearance;
- KPI layout;
- image placement;
- Program Insight callout;
- chart placement and quality;
- header/footer;
- unexpected overflow;
- unwanted blank pages;
- editing behavior;
- overall visual consistency.

The `.odt` must remain an ordinary editable Writer document.

## 18. Preflight

After implementation, normally run:

- focused B02 tests;
- relevant table/frame/page-flow integration tests;
- `PublicSampleSmokeTest`;
- full `composer test`;
- PHP lint for relevant `src/`, `tests/`, and sample PHP;
- `composer validate` if Composer metadata is touched;
- `git diff --check`;
- documentation build if documentation is modified.

Local `samples/output/*.odt` artifacts must not be casually committed, restored, deleted or regenerated outside explicit B02 output work. LibreOffice `.~lock.*#` files must never be committed.

## 19. Acceptance criteria

B02 is complete when all of the following are true:

**Functional:** The sample generates a valid editable ODT using the existing engine architecture.

**Structural:** The report contains the approved major content regions, structured tables, graphics, callout and multi-page content.

**Visual:** Manual LibreOffice review finds the report convincingly professional and internally coherent.

**Architectural:** No new engine semantics were introduced merely to satisfy the sample.

**Educational:** The sample source remains understandable as an example of structured programmatic document construction.

**Regression:** Existing test suites remain green.

**Documentation:** B02 is correctly represented in the sample registry and relevant Phase F documentation.

## 20. Architectural success criterion

> **B02 succeeds by composing existing capabilities, not by adding capabilities. Any newly discovered engine requirement is an architectural finding to be reviewed separately, not implicit scope for B02.**

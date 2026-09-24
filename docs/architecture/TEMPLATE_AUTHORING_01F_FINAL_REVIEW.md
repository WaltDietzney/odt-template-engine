# TEMPLATE-AUTHORING-01F Final Review

## Status

This document records the evidence and open work for the final review of
TEMPLATE-AUTHORING-01F. It is a review record, not a declaration that Phase F
is closed.

The review is performed against the current repository state. Semantics must
be established from implementation and tests before documentation is treated
as an API contract.

## Purpose

Phase F must close as one coherent public authoring story rather than as a
collection of individually successful samples. The final review therefore
covers:

- canonical sample coverage and legacy-sample migration;
- the public L/C/B/S learning path;
- Sample Explorer and public documentation coherence;
- canonical sample documentation quality;
- the complete public API surface and its actual contracts;
- separation of 1.0 behavior from post-1.0 work.

FINALIZATION-01 and the release integration pre-flight remain separate later
milestones.

## Completed evidence entering this review

Phase F has established the canonical Learn, Capability, Builder, and
Professional Showcase model. Relevant completed work includes L12 Writer Table
Population, B02 Professional Report Builder, S01b Professional CV, and S03
Writer-authored Structured Professional Report.

S03-A and S03-B provide the current strongest template-first evidence: Writer
owns stable visual and semantic structure while PHP performs bounded
application-owned mutations through User Fields, Bookmarks, named Tables,
Sections, Frames/images, and metadata.

## Legacy coverage review

### Result

No legacy numbered sample currently demonstrates a public capability that
requires a new canonical sample before 1.0.

The legacy collection therefore does not justify adding an L13, C06, or an
additional showcase merely to preserve historical sample coverage.

### Coverage conclusions

| Legacy sample | Capability | Canonical coverage |
| --- | --- | --- |
| 01 | Mixed variables/images | L01 + L06 |
| 02 | Filters | L01 |
| 03 | Conditions | L02 |
| 04 | Metadata | B02/S03 + API reference |
| 05/05b/06 | Image replacement/settings | L06 + API reference |
| 07 | Generated paragraphs/contact content | L04 |
| 08 | HTML import | L08 |
| 09 | RichText block | L04/L05 |
| 10 | Larger template-language document | L01-L03 + invoice work |
| 11/12/13/15/20/26 | Table construction/layout/styling | L07/C02 + API reference |
| 14/16 | Tabs/layout | L04/C03 + professional samples |
| 17 | Text fields/frames | C03 |
| 18 | Lists | L05 |
| 19 | HTML table import | L08 |
| 21 | Programmatic CV | superseded as public learning path by canonical ownership story |
| 22 | Bookmark text replacement | L09 |
| 23 | Section content replacement | L09 |
| 24 | Section/image replacement | L09/L06 |
| 25 | Native Section collections | S01b/C04 |
| 27 | Frame layout | C03 |
| 28 | Template inspection | L11 |
| 29 | Writer User Fields | L10 |

Legacy samples may remain as repository/regression evidence where useful.
Retention in the repository does not make them part of the public learning
path.

### Migration cleanup still required

The registry still contains migration-era state and stale targets. Examples
include empty targets for legacy samples now covered by canonical samples,
references to S01a, and historical S02 references around invoice work.

These are migration/documentation issues, not evidence for a missing new S02
implementation. Final taxonomy must describe the work that actually exists
rather than recreate an obsolete planning label.

## Sample Explorer review

The Sample Explorer correctly consumes the sample registry, which is a useful
single-source design. Its public presentation is still migration-oriented,
however.

For 1.0 the public explorer should present the canonical L/C/B/S learning path
as the recommended path. Legacy samples may remain repository evidence but
should not appear as a parallel public curriculum with migration/planned
labels.

The explorer introduction should also reflect the engine's three established
authoring models:

1. simple template processing;
2. programmatic structured ODT construction;
3. addressable Writer-native document structures.

A placeholder-only description is no longer sufficient for the engine.

## Public documentation review

### Sample Guide

The current guide contains several migration-era statements that must be
reconciled:

- the Learn path is described as still being introduced;
- it stops at L11 although L12 exists;
- S03 is absent from the Professional Showcase presentation;
- B01 still points toward a later S02 Professional Invoice;
- the numbered legacy samples remain a large parallel learning path;
- Sample 21 and Sample 25 are still taught as parallel CV showcases.

The canonical public progression should be centered on L01-L12, C01-C05,
B01/B02, and the actual professional showcases such as S01b and S03.

### Template Authoring Guide

The guide still describes L01-L11 and contains table wording from before
TABLE-ROW-01. In particular, statements that named tables are read-only or
that named-table row population is absent are no longer true after
TableTarget::populate() and L12.

The corrected documentation must remain bounded: TableTarget does not become a
general table editing API merely because populate() exists.

### README and documentation index

Both still give migration-era prominence to Sample 21 and Sample 25 as CV
architecture showcases. Their architectural evidence remains valuable, but
S01b is the canonical public synthesis and should be the public entry point.

### CV architecture documentation

The comparison of Samples 21 and 25 remains useful architecture history. It
should be reframed beneath S01b rather than maintained as a competing public
learning path.

### Canonical sample documentation pass

Canonical samples are product documentation, not merely regression fixtures.
They require concise English headers/comments where these explain:

- the sample's place in the learning path;
- Writer/PHP ownership;
- architecturally important API choices;
- non-obvious compatibility behavior.

B02 specifically still requires a stronger architectural header. Trivial
line-by-line comment clutter is not desired.

## Public API review requirement

The final review must produce a complete end-programmer API reference from
actual source and tests, not by extrapolating from samples.

For each public capability the review must establish, where applicable:

- class/facade/target/element;
- constructor and method signature;
- parameters and return/fluent behavior;
- every supported option key;
- defaults and exact option semantics;
- deterministic validation/failure behavior;
- lifecycle/preconditions;
- compatibility behavior;
- ownership/context constraints;
- whether the surface is recommended, advanced, compatibility, extension, or
  infrastructure API.

Samples demonstrate usage but do not define API completeness.

## Known documentation/contract warning

Image APIs already demonstrate why this audit is mandatory.

setImage() and ImageElement can derive a missing dimension from the source
image aspect ratio. replaceImageByName(), by established compatibility
behavior, applies legacy 5cm x 3cm defaults before its missing-dimension
branches and therefore behaves differently:

- neither dimension: 5cm x 3cm;
- width only: supplied width x 3cm;
- height only: 5cm x supplied height;
- both: supplied dimensions.

Richer fitting behavior remains post-1.0 IMAGE-LAYOUT-01 work and must not be
silently introduced during documentation cleanup.

## Open 01F work

1. Preserve the public API inventory in a dedicated review artifact.
2. Verify each candidate public API against implementation and tests.
3. Characterize all option-array contracts.
4. Classify surfaces as recommended, advanced, compatibility, extension, or
   infrastructure.
5. Build the end-programmer API reference from verified contracts.
6. Reconcile registry migration state and canonical taxonomy.
7. Align Sample Explorer, Sample Guide, Authoring Guide, README, docs index,
   and CV documentation.
8. Perform the canonical sample documentation pass.
9. Perform a final public-coherence review before declaring 01F complete.

No new engine capability has yet been identified by this final review as a
required Phase-F implementation gap.


### API closure rule clarified during Image audit

The 01F review does not open new architecture work for 1.0. Poor, obsolete, or
misleading public methods are not promoted merely because PHP marks them
public. The final 1.0 reference should prioritize Recommended API, document
Advanced API where genuinely useful, constrain Compatibility API to necessary
legacy guidance, and omit infrastructure surfaces from normal end-programmer
documentation.

Capabilities that are genuinely needed but require a new semantic/API design
are recorded in FUTURE_DEVELOPMENT instead. The custom-shape bitmap-fill
replacement use case discovered during the image audit is now tracked there as
CUSTOM-SHAPE-FILL-IMAGE-REPLACEMENT-01 rather than being designed inside 01F.


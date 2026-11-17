# SA-02 Section style and column authoring contract

Status: implementation and technical/visual acceptance complete on `feature/section-authoring-01`, baseline `84a3d99`, 2026-11-17.

The 65535 Writer interoperability evidence is related to, but independent from,
the table-only [SR-07H relative-column contract](SR-07H_RELATIVE_COLUMN_WIDTH_CONTRACT_CORRECTION.md);
this Section contract does not change `RichTable`.

## Public surface

```php
DocumentStyles::defineSection(string $name, array $options): void
SectionElement::setSectionStyle(string $name): self
```

`defineSection()` is document-local and idempotent for an equivalent semantic
definition. A conflicting definition fails. An existing authored Section style
is authoritative and cannot be redefined; reuse it by passing its name to
`SectionElement::setSectionStyle()`. A same-name style in another family is an
explicit collision and fails. Definitions are automatic `section` styles
under `content.xml/office:automatic-styles`; Section instances reference them
with `text:style-name`. This is deliberately different from common paragraph
styles in `styles.xml`.

Supported options are `margin-left`, `margin-right`, `background-color`, and
`columns`. `columns` requires a positive integer `count` (maximum 16383) (conservative Writer interoperability limit, not an ODF limit) and
supports `gap`, positive integer `relative-widths` (one weight per column),
boolean `balance`, explicit `column-indents` (`start`/`end` per column), and
`separator` with `style`, `width`, `color`, and percentage `height`.

Relative weights are normalized by the internal stateless
`RelativeColumnWidthNormalizer` to Writer's deterministic 65535-star
materialization scale. This is a generated Section convention, not an ODF
mandate or a physical-width claim. The normalizer first reduces all weights by
their greatest common divisor, so equivalent ratios (including large common
factors) are identical. It then computes each exact
`floor(65535 * weight / reduced-sum)` using bounded integer arithmetic; no
float fallback or silent clamp is permitted. Every ideal floor must be
positive, including the final column, and the final materialized column
receives the remainder. A reduced sum that overflows the bounded integer
representation is rejected with `InvalidArgumentException`. `balance: true`
emits
`text:dont-balance-text-columns="false"` on `style:section-properties`;
`false` emits `true` (the native attribute is inverted). Equal-width columns
use `fo:column-gap`. Unequal widths use native per-column
`fo:start-indent`/`fo:end-indent` children and omit `fo:column-gap`; the
requested gap is split equally across adjacent column edges and outer edges
are zero. `gap` and `column-indents` are mutually exclusive. Automatic columns with a separator omit explicit
column children unless weights or indents are supplied. Explicit indents
without weights materialize equal normalized weights; every explicit
column has a relative width.

Only bounded scalar ODF values are accepted. Bare zero lengths normalize to
`0cm`; split gaps that would round a positive half-gap to zero, and non-finite
numeric lengths, are rejected. Background colors are `#RRGGBB`
or `transparent`; separator colors are `#RRGGBB`; separator height is `0%` to
`100%`; separator styles are `none`, `solid`, `dashed`, `dotted`, or `double`.
Separator data requires at least two columns and a style. Unsupported,
malformed or contradictory options, wrong counts/weights, overflowed weight
sums, and wrong value types fail before document mutation.

## Lifecycle and resolution

Section references are validated before insertion, including nested Sections.
Missing styles and same-name styles in the wrong family fail atomically. The
existing document-local style context/materializer and package snapshot are
the rollback boundary. `inspect()` sees working Sections; source-oriented
inspection remains unchanged; `load()` resets authored working content and
styles. Repeated save/render is stable. Existing paragraph `break-before`
already maps `column` to `fo:break-before="column"`; SA-02 adds no new break API.

Background images, protection, visibility, conditions, notes, DDE/external
links, and page-layout authoring remain outside this slice.

## Acceptance evidence (2026-11-17)

Independent review verified 1006 normalization cases against an exact integer
reference, equivalent same-name definitions, and the corrected automatic
separator/implicit equal-width paths in a Writer roundtrip. The final host
`composer test` passed: 1035 tests, 7981 assertions, exit 0; existing one
warning, four deprecations and eight PHPUnit deprecations remain. The first
run inherited the C06 output override and failed the canonical-output smoke
assertion; rerunning without that override passed. PHP lint (370 files),
Composer strict validation without lock checking and diff check passed.
Zensical was unavailable; documentation build remains unverified.

C06 was generated in an isolated candidate directory under
`tmp/visual-regression/`, rendered using `tools/visual-regression/render.sh`,
and the complete one-page PNG visually inspected. No clipping or overlap
observed; equal/unequal/three-column layouts, separators, backgrounds,
column break and mixed content visible. Writer ODT roundtrip retained all
12 named Sections, explicit weights, balancing flags, separators and the
paragraph column break. Writer rounds some lengths to its native precision.
This is a visual acceptance, not a pixel comparison to an approved SA-02
baseline. Existing sample outputs and locks are excluded from versioning.
Technical and visual GO; no commit, push or merge authorized by this gate.

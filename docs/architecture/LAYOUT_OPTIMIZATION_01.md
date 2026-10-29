# LAYOUT-OPTIMIZATION-01 — Planning Boundary

Status: planning only; no AI integration or optimization loop is implemented.

## Purpose

LAYOUT-OPTIMIZATION-01 describes the later control boundary between the ODT
Template Engine, LibreOffice rendering, and a future visual layout evaluator:

```text
ODT Template Engine
        ↓
LibreOffice rendering
        ↓
visual layout assessment
        ↓
bounded parameter proposal
        ↓
deterministic engine mutation
        ↓
repeatable rendering
```

The engine remains responsible for deterministic ODF mutation. LibreOffice
remains responsible for text flow and pagination. The evaluator is not part of
this capability.

## Existing prerequisites

The current engine already provides useful foundations:

- named Sections, bookmarks, frames, and other addressable native objects;
- document-local paragraph styles with stable identity and inheritance;
- `DocumentStyles::defineParagraph()` for new semantic styles;
- `DocumentStyles::updateParagraph()` for bounded mutation of existing authored
  paragraph styles in `styles.xml` and automatic paragraph styles in
  `content.xml`;
- independent `fo:margin-top`, `fo:margin-bottom`, `fo:line-height`, and flow
  properties such as `fo:keep-with-next` and `fo:keep-together`;
- native `text:list` and `text:list-item` generation through `ListElement`;
- deterministic save/reopen and LibreOffice/PDF validation workflows.

These capabilities make a proposal such as “increase the spacing between
experience entries within approved bounds” technically expressible, but they
do not yet define an optimization contract.

## Required future contract

Before implementation, the following concepts need explicit public semantics:

1. **Stable target identity** — a proposal must address a named style, Section,
   bookmark, list region, or other supported object without relying on a PDF
   coordinate alone.
2. **Parameter ownership** — distinguish style-owned spacing from local
   paragraph overrides and preserve the existing owner when changing a value.
3. **Bounds and units** — define minimum, maximum, step, unit, and whether a
   value is allowed to affect pagination. Invalid or out-of-range proposals
   must fail before mutation.
4. **Change provenance** — record target, old value, new value, reason, and
   originating evaluation request in an application-owned change record.
5. **Flow protection** — proposals must not remove or weaken existing
   keep/break/widow/orphan rules unless a separately approved capability says so.
6. **Reproducible rendering** — use a fixed LibreOffice version, isolated
   profile, stable fonts, explicit locale, and recorded input/output artifacts.
7. **Separation of decision and execution** — the evaluator proposes a bounded
   change; the engine validates and executes it. The engine must not contain
   CV-specific visual heuristics.

## Candidate first slice

The safest initial slice would be a transaction-like, deterministic mutation
of existing paragraph styles:

```text
target style + parameter + min/max bounds
    → validate
    → updateParagraph()
    → save/reopen
    → LibreOffice render
```

Candidate parameters are paragraph spacing before/after and line height. Page
styles, list geometry, Sections, and object coordinates should remain separate
capabilities until their ownership and roundtrip behavior are characterized.

## Explicit non-goals

This planning document does not implement:

- AI or image analysis;
- PDF assessment;
- automatic optimization loops;
- page-number or pagination heuristics;
- CV-specific layout rules;
- a second renderer or a renderer-neutral layout model.

## S01b evidence

The S01b regression demonstrates the required separation. Native list layout
is used for activity bullets, while the complete experience block continues to
be protected by paragraph flow rules. The Operations Analyst and Knowledge
sharing corrections remain intact. No automatic aesthetic redistribution is
performed.

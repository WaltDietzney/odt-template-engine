# SR-06 — Semantic Graphic Style Requirements — Closeout

Status: **COMPLETE / FINAL GO**

SR-06 migrates the graphic-style semantics required by structured insertion into the document-local semantic requirement architecture while preserving compatibility paths and keeping drawing layout, geometry, positioning, wrapping, and physical package resources as distinct concerns.

## Scope completed

SR-06 was delivered in two integration stages:

- PR #45 integrated SR-06A through SR-06E: graphic/drawing boundary characterization, semantic graphic producers, document-local graphic resolution/materialization, and explicit fill-image dependency handling.
- PR #46 completes SR-06F and SR-06G: legacy compatibility closeout, document-reference-based compatibility adoption, regression tooling, and the manual LibreOffice visual gate.

The resulting conceptual boundary is:

```text
structured drawing element
        ├── drawing structure
        ├── geometry / size
        ├── anchor
        ├── positioning / relation
        ├── wrapping
        ├── semantic graphic-style requirements
        ├── drawing declarations / dependencies
        └── physical package resources
```

Only semantic graphic-style definitions and references belong to `StyleRequirement` family `graphic`.

## Architectural outcomes

### Semantic graphic styles

Graphic style definitions can be represented, resolved, conflict-checked, and materialized through the same document-local semantic requirement model already used for paragraph and text styles.

Existing target-document definitions remain authoritative when the requested semantic identity already exists.

`DrawTextBox` produces semantic graphic requirements for the bounded appearance-property subset. Placement, geometry, anchor, and other drawing behavior are not reclassified as graphic-style semantics merely because historical mapper APIs mixed those concerns.

Normal `ImageElement` deliberately does not invent a semantic graphic style where none is required.

### Fill-image dependencies

`draw:fill-image` is not modeled as another style family. It is a drawing declaration/dependency with its own document-local requirement and registry.

The dependency chain is explicit:

```text
graphic style
    -> draw:fill-image-name
    -> draw:fill-image declaration
    -> Pictures/... package resource
```

The declaration and the physical bitmap therefore remain separate ownership concerns.

### Compatibility ownership

Document-local ownership is authoritative semantics.

Historical static `StyleMapper` registries remain compatibility facades, but a static registration does not implicitly become owned by every later document. During legacy finalization, compatibility state is adopted only when the current document provides the required reference evidence.

The family-specific compatibility bridges are:

- image styles: adoption from current-document `draw:style-name` references;
- fill-image declarations: direct or transitive current-document fill-image references, with package resource preparation where the concrete element owns the source asset;
- frame styles: adoption from current-document `draw:style-name` references before legacy `StyleWriter` materialization.

No process-global current-document pointer and no blanket static-registry reset were introduced.

Public/static compatibility APIs and protected lifecycle hooks remain available.

## Explicitly deferred behavior

SR-06 does not redesign drawing layout semantics.

In particular, the known `DrawTextBox` positioning behavior observed in Sample 17 predates SR-06. Percentage-like positioning options do not currently establish a reliable public semantic equivalent to concepts such as right/center positioning across LibreOffice drawing contexts.

That work remains assigned to:

- `FRAME-LAYOUT-01` — unified frame positioning;
- `FRAME-LAYOUT-02` — DrawTextBox positioning;
- `IMAGE-LAYOUT-01` — image anchor, wrap, and position.

Likewise, SR-06 does not absorb table geometry, public style API redesign, or document-default semantics.

Two characterized legacy lifecycle issues remain deferred rather than silently changed during the architecture migration:

- process-global legacy registry behavior outside the narrowed document-adoption boundaries;
- legacy assign/render -> refresh behavior does not provide save-equivalent graphic finalization.

These belong to later lifecycle/compatibility work where their compatibility impact can be decided explicitly.

## Validation

The final local automated preflight on the SR-06F/G line reported:

- `composer validate --no-check-publish`: PASS;
- PHPUnit: **480 tests, 2901 assertions, 0 failures, 0 errors**;
- one previously known PHPUnit deprecation;
- PHP syntax checks for `src/` and `tests/`: PASS;
- `git diff --check`: PASS after removal of one documentation-only trailing blank line.

The visual regression gate is recorded separately in `SR-06G_VISUAL_REGRESSION_CLOSEOUT.md`.

Representative LibreOffice rendering results:

- Sample 05: pixel-identical PASS;
- Sample 05b: pixel-identical PASS;
- Sample 06: pixel-identical PASS;
- Sample 17: PASS with reviewed, pre-existing positioning deviation;
- Sample 21: both pages pixel-identical PASS.

The visual baseline was not regenerated to hide the reviewed Sample 17 difference.

During the visual preflight, `sample_05b_replaceImage.php` was also repaired as a bounded sample-infrastructure defect: it now loads Composer and resolves its template/assets/output relative to the sample directory rather than depending on the caller's working directory. This is not an SR-06 semantic change.

## Final decision

**SR-06 is COMPLETE / FINAL GO.**

The semantic graphic-style and fill-image dependency foundation is accepted architecture baseline.

The next planned semantic family migration is **SR-07 — Semantic Table / Table-Cell Requirements**. D5F lifecycle/materialization integration remains intentionally after the required style-family migrations so it can simplify a coherent semantic model rather than institutionalize the transitional mixed architecture.

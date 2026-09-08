# STYLE-API-02H — Legacy Getter / Facade Cleanup

Status: **ACCEPTED CHANGE CONTRACT — SEMANTICS BEFORE IMPLEMENTATION**
Baseline: `develop` at `9402f65e99fb8a922f8e365edbb05e98097dc202`

## Problem

The semantic document-local style model is established, but historical public
facades and array projections remain alongside it. In particular, StyleMapper
still exposes process-wide paragraph/text registration and getter methods,
StyleContext still resolves references through that process-wide state, and
elements expose transitive array aggregations that duplicate collector-owned
traversal.

## Current evidence

- Normal structured style ownership is provided by `StyleRequirementCollector`,
  `StyleContext`, and semantic materializers.
- The remaining StyleMapper registration/getter methods are not used by normal
  structured ownership. They feed only StyleContext's legacy paragraph/text
  fallback, tests, diagnostics, or historical samples.
- `LegacyStyleRegistry` has no writer role after 02G. Its remaining role is
  exactly the process-wide paragraph/text fallback exposed through StyleMapper.
- `OdtElement` and composite elements still expose transitive array getters
  such as `getRequiredStyles()` and `getImageAssets()`. Canonical collectors
  already traverse `ownedElements()` and direct semantic/resource hooks.
- The legacy assign/render graphic path still needs direct graphic requirement
  data. Its own graphic requirement hooks remain bounded compatibility hooks
  until a later graphic API decision; transitive traversal belongs to
  collectors.
- `OdtTemplate::registerStyles(array)` remains a protected compatibility
  facade after 02G. It is not used by normal semantic insertion and is
  removed in this milestone because document style authoring is provided by
  `styles()->defineParagraph()` and semantic requirements.
- `ensureTextStylesExist()` and `ensureParagraphStylesExist()` remain lifecycle
  helpers for template preparation and sample compatibility. They are not
  generic style authoring facades and remain unchanged unless a direct caller
  migration is required.

## Target public API

1. Recommended application authoring: element options and fluent APIs.
2. Document style authoring: named references and
   `$template->styles()->defineParagraph(...)`.
3. Structured extension: `ownedElements()`, semantic style requirements,
   typed resource/dependency hooks, and `toDomNode()`.

Legacy registry ownership and duplicate array projections are not a fourth
public API layer.

## Decisions

### StyleMapper and LegacyStyleRegistry

Remove all StyleMapper registration/getter/registry facade methods and remove
`LegacyStyleRegistry`. StyleMapper remains a stateless mapping and identity
utility. No replacement global registry is introduced.

### StyleContext fallback

Reference resolution order is:

1. authored styles in the current document;
2. document-local semantic definitions;
3. unresolved reference.

Process-wide paragraph/text fallback is removed. Unresolved references remain
explicitly unresolved and are not silently materialized from another document
or process-global registry.

### Element getter families

Remove old transitive style aggregators where collector traversal already
provides the behavior, including `getRequiredStyles()`-style paragraph/text
and table aggregations and their duplicate paragraph-definition views.

Keep direct graphic compatibility hooks required by the legacy assign/render
path (`getOwnFrameStyleRequirements()`, `getOwnImageStyleRequirements()`, and
`getOwnFillImageRequirements()`). The bounded transitive graphic and image
asset views remain only where current assign/render and section mutation paths
still call them; they are compatibility traversal, not style ownership, and
are not expanded.

Keep `getOwnImageAssets()` as the direct resource hook. Remove or narrow
transitive `getImageAssets()` callers where `StructuredResourceCollector`
already owns traversal.

### OdtTemplate facades

Remove the protected generic `registerStyles(array)` facade. Its behavior is
not required by normal production and bypasses the canonical document-local
style API. Keep `ensureTextStylesExist()` and
`ensureParagraphStylesExist()` as lifecycle/template-preparation helpers
because current production and samples use them.

## Breaking changes

- StyleMapper registration/getter methods are removed.
- LegacyStyleRegistry is removed.
- Named paragraph/text references no longer resolve from process-global PHP
  registration; callers must use authored template styles or document-local
  semantic definitions.
- Redundant transitive element style/resource getters are removed where their
  callers migrate to collectors.
- Protected `OdtTemplate::registerStyles(array)` is removed.

Migration is through element-local options, `DocumentStyles`, semantic
requirements, `ownedElements()`, and typed resource hooks.

## Non-goals

- STYLE-API-02I final documentation/API closeout;
- StyleMapper mapping redesign;
- new style families or symmetric `define*()` APIs;
- table layout redesign;
- graphic API redesign;
- template syntax or lifecycle redesign;
- unrelated element API redesign;
- StyleContext public exposure.

## Exit criteria

1. StyleMapper exposes no registry ownership methods.
2. LegacyStyleRegistry is absent.
3. StyleContext has no process-global paragraph/text fallback.
4. Normal semantic paragraph/text, table, graphic, image, and resource paths
   remain green and document-local.
5. Semantic style and resource collectors own normal traversal; the remaining
   transitive graphic/resource compatibility views have explicit active callers.
6. Legacy assign/render output remains covered and functional through its
   bounded direct compatibility hooks.
7. `ensureTextStylesExist()` and `ensureParagraphStylesExist()` remain
   functional for their lifecycle/sample callers.
8. No replacement global registry or generic style facade is introduced.

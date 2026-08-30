# STYLE-CONTEXT-01F-B — Paragraph Finalization and Legacy Compatibility

## Scope

This slice resolves paragraph-style finalization only. Text styles, fonts,
graphic styles, images, frames, tables, table-cell styles and lists remain
outside its implementation boundary.

## Evidence and current behavior

The current code has two paragraph-style paths:

1. `OdtTemplate::setElement()` collects `getRequiredParagraphStyles()`,
   registers the definitions in the current `StyleContext`, and immediately
   materializes them into that document's `styles.xml`.
2. Context-free `StyleMapper::registerParagraphStyle()` stores definitions in
   `LegacyStyleRegistry`; `StyleWriter::writeAllStyles()` reads that registry
   during save and direct writer use.

The first path is document-local in simultaneous-document and reversed
save-order characterization. The second path intentionally retains its
process-wide first-registration-wins and cross-document leakage behavior.

Current call-site evidence includes `Paragraph`, `HtmlImporter`,
`samples/sample_21_cvProfile.php`, and
`samples/sample_richttext_simple.php`. `StyleWriter` is also used directly by
the save/refresh path and by compatibility tests.

## Ownership contract

Pending paragraph requirements produced through the structured document API
belong to the current `OdtDocumentContext::styleContext()`.

An operation on document A must not affect paragraph-style output of document B.
This remains true under interleaved editing, either save order, and repeated
saves.

Template-authored paragraph styles already present in `styles.xml` remain
authoritative document data. Existing same-name DOM collision behavior is
unchanged: materialization skips a style whose name already exists; comparing
its definition is deferred.

## `OdtTemplate::save()` semantics

`save()` finalizes paragraph styles from the current document's already
materialized DOM/`StyleContext` path only. It must not import unrelated
paragraph definitions from `LegacyStyleRegistry` into that document.

The existing immediate materialization performed by `setElement()` remains in
place for compatibility and observability. Finalization is therefore
document-aware without moving all paragraph writing to a new late phase.

## `refresh()` semantics

`refresh()` keeps its existing lifecycle: it writes the current document,
persists core documents, and reloads them. Its paragraph finalization follows
the same document-aware rule as `save()`. No broader refresh/reset redesign is
introduced.

## Legacy facade and direct writer behavior

The following static methods remain available with their existing behavior:

- `StyleMapper::registerParagraphStyle()` stores process-wide legacy state;
- `StyleMapper::getParagraphStyles()` exposes that state;
- `StyleMapper::getRegisteredStyles()` and
  `StyleMapper::getAllRegisteredStyles()` retain their existing paragraph
  contribution;
- direct `StyleWriter::writeAllStyles($dom)` remains a compatibility path and
  continues to consume legacy paragraph registrations.

The distinction is deliberate: direct context-free writer use is legacy
global behavior, while `OdtTemplate::save()` is document-aware. No current
document pointer, constructor reset, save-order ownership, or broad registry
clearing is introduced.

## Compatibility impact

Applications using structured elements through `setElement()` retain their
paragraph styles and gain isolation from unrelated legacy registrations.
Applications intentionally calling `StyleMapper` and then direct
`StyleWriter::writeAllStyles()` retain the old behavior. Applications relying
on an explicit legacy registration being copied into an unrelated
`OdtTemplate::save()` are using an unsupported global coupling and will no
longer receive that accidental contribution.

Public element APIs, `StyleContext` conflict semantics, style placement and
ODF serialization remain unchanged.

## Invariants

- A and B have isolated structured paragraph output.
- Save order and interleaving do not change either document's structured styles.
- Repeated save is idempotent.
- `load()` / core-document replacement resets pending `StyleContext`
  requirements.
- Equivalent document-scoped registration is idempotent.
- Conflicting pending document-scoped definitions fail before conflicting
  materialization.
- Existing authored DOM styles are not redefined.
- A legacy static registration remains first-registration-wins and global when
  observed through the legacy facade/direct writer path.

## Failure and atomicity

StyleContext conflicts remain explicit errors. Structured registration occurs
before its relevant DOM materialization, so a conflict does not partially write
the conflicting paragraph style. Save uses the current document state and does
not silently fall back to unrelated legacy paragraph state.

## Later slices

01F-C and later may migrate additional style families and define the eventual
fate of context-free legacy finalization. This document does not deprecate or
remove `StyleMapper`, redesign `StyleWriter`, or address other registries.

# F3.2 Getting Started Reconciliation

## Status

**COMPLETE — bounded FINALIZATION-01 F3.2 documentation slice**

## Contract

F3.2 reconciles installation and first-document guidance with the completed
Public API 1.0 contract. The shortest successful path must use Recommended API
only and lead from a LibreOffice-authored template to an editable generated ODT
without requiring knowledge of internal ODF architecture.

No runtime architecture or API semantics are introduced by this slice.

## Evidence

The reconciliation was checked against:

- the F3 contract in `FINALIZATION_01_PLAN.md`;
- the completed Template & Document lifecycle reference;
- the completed Values & Repeating Data reference;
- current `composer.json` package requirements;
- the existing Installation, Quick Start, Creating Templates, and practical
  Template Authoring guidance.

## Result

The canonical first-document path is now explicit:

```text
Composer installation
        ↓
LibreOffice-authored .odt
        ↓
new OdtTemplate(...)
        ↓
assign(...)
        ↓
render()
        ↓
save(...)
        ↓
editable .odt
```

This path uses only Recommended API.

The Getting Started documentation now makes the following 1.0 semantics
explicit without requiring the reader to consult architecture evidence:

- construction loads the template; a normal first document does not call
  `load()`;
- `assign()` and `assignRepeating()` stage classic template data;
- `render()` applies the visible template language;
- `save()` writes the current ODT and does not implicitly call `render()`;
- the output directory/path is an application responsibility;
- the result remains a normal editable ODT;
- ODF package/XML knowledge is not a prerequisite for normal template
  authoring.

The three F3 working-model names are also used consistently at the first
decision point:

1. Simple Template Processing;
2. Structured ODT Construction;
3. Writer-native Document Model.

The Quick Start begins with Simple Template Processing rather than forcing the
reader to understand all three models before producing a first document. The
other two models are introduced only as next choices once PHP-owned or
Writer-native structure is needed.

## Scope decisions

The existing practical Template Authoring Guide already contains substantial
correct advanced authoring guidance. F3.2 does not rewrite it.

Guide-wide historical terminology, stale capability statements, mapping/
preflight/automation guidance, frames/text-box guidance, and advanced ODT
reconciliation remain F3.3 work as recorded by F3.1.

README reconciliation remains F3.4.

## Completion gate

F3.2 is complete when:

- installation requirements agree with the package metadata;
- the documented first document uses Recommended API only;
- the lifecycle agrees with the completed API Reference;
- a new user is not required to understand ODF internals;
- the first-document path clearly produces an editable ODT;
- Getting Started introduces the final three-model terminology without making
  advanced models prerequisites;
- documentation validation passes.

No PHP runtime behavior is changed by F3.2.

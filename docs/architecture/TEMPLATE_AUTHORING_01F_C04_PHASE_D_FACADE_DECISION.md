# F6-C04 — Public Phase-D Facade Decision

**Status:** Accepted and implemented

## Decision

Expose the already accepted TEMPLATE-AUTHORING-01D declarative Section
execution through the additive public method
`OdtTemplate::executeDeclarative(TemplateContract $contract, array $values)`.
The name distinguishes direct Phase-D execution from `render()` and the
Phase-E `automate…()` entry points. No better existing public naming
convention was found for this operation.

The facade delegates directly to the existing internal
`DeclarativeConditionExecutor::execute()` with the current document context.
The executor remains `@internal`; no execution service or second control
semantics were added.

## Preserved contract

- The caller supplies a previously inspected `TemplateContract` and values
  already shaped with template dependency names.
- Recognized `#foreach`, `#if`, and `#ifnot` controls retain Phase-D BODY,
  supported header/footer, nested-scope, filter, failure, and invocation-local
  rollback behavior.
- The facade performs no inspection, mapping/preflight, Phase-E automation,
  implicit render, save, or finalization.
- `assign()` values are not an implicit data source.
- No new lifecycle marker or repeated-execution guarantee is introduced.
- Existing classic rendering, imperative Section operations, and Phase-E
  entry points remain unchanged.

## C04 / C05 distinction

C04 demonstrates direct declarative execution using template-shaped data.
C05 demonstrates application-shaped data mapped explicitly through
`MappingDefinition`, concrete-preflighted, and executed through atomic
Phase-E `automate()`. Sample 25 remains evidence for imperative Section
collections and S01b.

# TEMPLATE-AUTHORING-01B Slice 3 — Classic Controls & Data Scopes

Status: COMPLETE / GATE GREEN

## Scope

Slice 3 completes the classic-control and scoped-dependency portion of unified template inspection.

It builds on the ROOT dependency projection established by Slice 2 and adds semantic classic controls, collection dependencies, collection-item scopes, nested repetition scopes, and condition dependencies in the current data scope.

The slice remains inspection-only. It does not add or change declarative native-control execution, native Writer field binding, or high-level mapped rendering.

## Completed semantics

Slice 3 establishes:

- immutable semantic classic-control descriptors;
- a distinction between semantic controls and their authored marker evidence;
- classic FOREACH collection dependencies;
- COLLECTION_ITEM data scopes;
- nested repetition scopes;
- bindings resolved against their current semantic data scope;
- conditions resolved against their current semantic data scope;
- IF, IFNOT, ELSEIF, ELSE, and closing-marker evidence relationships;
- deterministic control, scope, dependency, evidence, and serialization identities;
- compatibility findings for known classic nested-control runtime limitations without corrupting the intended semantic graph.

A classic repetition such as {{#foreach:experience}} ... {{company}} ... {{#endforeach}} is represented conceptually by a ROOT collection dependency experience[], a collection-item scope experience[], and a VALUE dependency experience[].company.

Nested repetition is likewise representable, for example experience[].projects[].project_name.

Readable paths remain derived projections. Scope and dependency identity are not reduced to lexical path strings.

## Scope-aware dependency identity

Slice 3 closes the deliberate repetition boundary left by Slice 2.

Identical lexical names in different scopes remain distinct logical dependencies. ROOT.name and experience[].name are therefore different logical requirements.

The repetition-owned binding is no longer unresolved, and it cannot be captured by a same-name ROOT dependency.

Bindings following a closed foreach resume their parent scope.

Body, page-owned header, and page-owned footer evidence continue to share the same ROOT dependency when semantically appropriate.

## Classic control model

Classic controls are semantic objects distinct from individual marker occurrences.

Opening, branch, and closing markers remain source evidence and are referenced by the semantic control.

The implemented classic family covers IF, IFNOT, ELSEIF, ELSE, and FOREACH.

Conditional controls inherit the current data scope and do not create a child data scope.

Foreach controls reference a collection dependency in their parent scope and create a collection-item child scope.

## Condition grammar reconciliation

The Slice-3 condition-grammar gate required the unified contract not to remain narrower than runtime-supported classic condition semantics.

A shared ConditionExpression representation now supplies the condition parsing/evaluation semantics used by TemplateProcessor and the unified contract projection.

This preserves the existing runtime truth semantics while allowing inspectTemplate() to project dependencies from runtime-supported forms such as gender=="female", score>=10, and current.

Only the referenced application-data name becomes a logical dependency; comparison literals do not.

The existing focused inspectTemplateStructure() compatibility surface is intentionally unchanged. Its previously characterized classification of comparator expressions as UNSUPPORTED / UNSAFE therefore remains intact rather than being silently redefined during this slice.

## Compatibility findings

Known A1/runtime limitations around nested classic controls are represented as contract diagnostics rather than being used to falsify the intended scope graph.

The Slice-3 compatibility finding uses the machine-readable code classic_nested_control_runtime_limitation with warning severity.

This allows inspection to describe the intended authored scope while separately exposing a current runtime limitation.

Slice 3 does not silently fix legacy rendering behavior.

## Compatibility

The following existing public behavior remains semantically unchanged:

- inspect();
- inspectTemplateStructure();
- classic render();
- save();
- existing section/bookmark/table/frame APIs.

The condition-parser extraction preserves existing TemplateProcessor runtime evaluation semantics.

The original-source lifecycle established by Slice 1 remains unchanged.

No Phase C, D, or E execution semantics are introduced.

## Closeout verification

The local Slice-3 closeout gate was reported green.

Verified checks include:

- focused Slice 3 integration tests;
- combined Slice 0, Slice 1, Slice 2, and Slice 3 integration gates;
- relevant PHP syntax checks;
- git diff --check develop...HEAD.

The focused Slice-3 gate verifies, among other things:

- semantic classic control projection;
- marker/control evidence relationships;
- foreach collection dependencies;
- collection-item scopes;
- nested foreach scopes;
- condition dependencies in the current scope;
- ROOT versus item-scope dependency identity;
- same-name dependencies across different scopes;
- scope restoration after foreach;
- body/header/footer ROOT dependency sharing;
- comparator-condition dependency projection;
- preservation of existing inspectTemplateStructure() classification;
- shared runtime condition semantics;
- IFNOT projection;
- ELSEIF and ELSE marker relationships;
- nested-control compatibility findings;
- deterministic serialization.

## Exit criterion

Slice 3 is complete.

The implementation satisfies the Slice-3 boundary of the TEMPLATE-AUTHORING-01B change contract and the B1.1 graph/data-scope model:

- classic templates produce a truthful scoped dependency graph;
- nested repetition is representable;
- conditions inherit their current scope;
- runtime-supported condition semantics used by Phase B are no longer blocked by the narrower focused inspector grammar;
- known runtime compatibility limitations remain separate from intended semantic scope;
- existing compatibility surfaces are preserved.

Proceed to:

TEMPLATE-AUTHORING-01B Slice 4 — Native Ownership & Declarative Candidates

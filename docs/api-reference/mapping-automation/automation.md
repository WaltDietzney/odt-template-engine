# Automation

Automation is the **mutating end** of the optional Mapping & Automation pipeline.

Earlier stages describe and validate what should happen. `automate()` is where the validated invocation is finally applied to the current Working Document and package.

```text
inspect source → TemplateContract
mapping + data → MappingResolution
concrete preflight → READY
        ↓
automate()
        ↓
one atomic Working-Document/package mutation
        ↓
save()
```

Automation does not replace the ordinary imperative/classic API and does not implicitly call `render()` or `save()`.

## Common automation facade

**Recommended**

```php
public function automate(
    TemplateContract $contract,
    ConcretePreflightResult $preflight
): void
```

Typical use:

```php
$contract = $template->inspectTemplate();
$working = $template->inspect();

$preflight = (new ConcreteMappingPreflight())->preflight(
    $mapping,
    $contract,
    $applicationData,
    $working
);

if (!$preflight->ready()) {
    // handle diagnostics
    return;
}

$template->automate($contract, $preflight);
$template->save($outputPath);
```

The supplied preflight must be `READY`. A non-READY result is rejected with `InvalidArgumentException` before mutation.

The preflight already contains the resolved concrete application data. Automation consumes that validated projection; it does not perform a fresh unrestricted traversal of the application's data model.

## What one automation invocation can execute

The common facade combines the three established target families:

1. explicit native-object actions;
2. dependency automation;
3. document capabilities.

Current 1.0 examples are:

| Family | Examples |
| --- | --- |
| Native Object Actions | Section `replace-content`, Bookmark `replace-text`, Frame `replace-image` |
| Dependencies | classic scalar/filter/special bindings, Writer User Fields, recognized IF/IFNOT/FOREACH dependencies |
| Document Capabilities | mapped metadata targets |

No action is executed merely because the TemplateContract discovered a native object. Native actions and document capabilities remain explicitly mapped.

## Execution order

The common facade deliberately executes:

```text
1. Native Object Actions
2. Dependency Automation
3. Document Capabilities
```

This order is part of the current bounded orchestration contract.

It is not a generic scheduler or workflow language.

Native targets are localized against source-order/source-ownership evidence before dependency execution can materialize or remove structural Sections in the Working DOM. The ordering therefore protects already-established template semantics rather than expressing arbitrary user-configurable priorities.

## Invocation-wide atomicity

`automate()` places the complete three-family invocation inside one Working-State snapshot.

Conceptually:

```text
snapshot
   ↓
native actions
   ↓
dependencies
   ↓
document capabilities
   ↓
success ───────────────→ keep changes
   │
   └─ any failure ─────→ restore snapshot
```

If any family throws, the engine restores the package/document Working State and rethrows the original execution failure when rollback succeeds.

Characterized rollback coverage includes:

- `content.xml` Working DOM;
- `styles.xml` Working DOM;
- `meta.xml` Working DOM;
- package-local resources;
- semantic style definitions;
- font-face requirements;
- fill-image requirements;
- imperative state that existed before the automation invocation.

Pre-existing state is preserved. Transient changes made by the failed automation invocation are removed.

This is the important difference between the common `automate()` facade and manually calling individual family methods.

## Rollback failure

Rollback is itself an operation that can theoretically fail.

If execution fails **and** restoration also fails, the engine throws `PhaseEAutomationRollbackException`.

```php
executionFailure(): Throwable
rollbackFailure(): Throwable
```

The execution failure is also the exception's previous cause.

This is an **Advanced failure contract**: it preserves both pieces of evidence rather than hiding the original automation failure behind a rollback error.

Snapshot cleanup failure does not replace the execution result.

## Single successful invocation per Working Document lifecycle

A successful common `automate()` invocation is deliberately single-use for the current Working Document lifecycle.

A second successful common invocation without reset throws `LogicException`.

```php
$template->automate($contract, $preflight);

// Not supported in the same lifecycle:
$template->automate($contract, $anotherPreflight);
```

Calling `load()` resets the Working Document and the automation-success gate:

```php
$template->load();

// A new successful common invocation is now permitted.
```

A failed invocation that rolls back successfully does **not** consume the one-success allowance. It may be corrected and retried.

The single-success rule prevents the engine from pretending that arbitrary repeated structural automation is idempotent when that guarantee has not been established.

## Automation does not call `render()`

Mapped dependency automation is not implemented by filling the classic `setValues()` stack and then calling the ordinary public `render()` lifecycle.

Instead, it consumes the validated dependency projection and delegates each supported consumer to its established mutation semantics.

Consequently:

```php
$template->automate($contract, $preflight);
$template->save($outputPath);
```

is a valid Mapping & Automation lifecycle by itself.

Do not add `render()` merely because classic template processing traditionally uses it.

If application code deliberately combines automation with separate classic staged assignments, that is a mixed imperative lifecycle and must respect the semantics of both APIs rather than assuming that `automate()` is an alias for `render()`.

## Direct declarative execution

**Advanced**

```php
public function executeDeclarative(
    TemplateContract $contract,
    array $values
): void
```

`executeDeclarative()` is the directly exposed Phase-D facade for executing the inspected template's recognized declarative Writer Section controls with values already expressed in the template's own ROOT and collection-item vocabulary.

It is **independent of Phase-E Mapping & Automation**. It does not inspect the template, build or consume a `MappingDefinition`, run Concrete Preflight, call `render()`, or call `save()`.

Use it only when application code intentionally supplies values in the template contract's own semantic vocabulary and wants direct declarative structural execution. Normal mapped application-data workflows should use Concrete Preflight followed by the common `automate()` facade instead.

Successful repeated direct declarative execution is not generally guaranteed. This method is therefore not a shortcut around the common Phase-E lifecycle contract.

## Dependency automation

**Advanced orchestration facade**

```php
public function automateDependencies(
    TemplateContract $contract,
    ConcretePreflightResult $preflight
): void
```

The dependency family consumes the READY projection and reuses established owners for the actual work.

Current execution includes, where represented by the contract:

- Writer User Field binding through `setUserField()`;
- classic scalar/filter/special replacement through established TemplateProcessor behavior;
- recognized declarative/native Section IF/IFNOT/FOREACH structural execution.

Writer User Fields are bound before structural mutation where source evidence requires it.

Collection scopes use the already-resolved projected scope/index identity. Automation does not re-derive application paths at mutation time.

The underlying executor/projector classes are infrastructure, not an alternative public Mapping API.

## Native-object action automation

**Advanced orchestration facade**

```php
public function automateNativeObjectActions(
    TemplateContract $contract,
    ConcretePreflightResult $preflight
): void
```

It executes only explicit READY native actions represented in the preflight.

Current actions are exactly:

```text
Section   replace-content   OdtElement
Bookmark  replace-text      string
Frame     replace-image     bounded image payload
```

The executor verifies that the operation agrees with the supplied MappingResolution, explicit provenance, and uniquely identified TemplateContract evidence.

### Frame image sizing during execution

Mapped Phase-E Frame replacement intentionally has these sizing semantics:

| Supplied dimensions | Result |
| --- | --- |
| neither | preserve authored frame width and height |
| width only | set width; derive height from replacement image ratio |
| height only | set height; derive width from replacement image ratio |
| width + height | use both values exactly |

For one-dimensional sizing, the explicit dimension's unit is preserved for the derived dimension.

The ratio comes from the **replacement image**, not from the old frame.

For raster images it uses intrinsic pixel dimensions. For SVG it uses a valid positive `viewBox` where available, otherwise compatible positive width/height attributes with matching units.

If a required intrinsic ratio cannot be determined, execution fails. It does not silently fall back to the old frame ratio.

Unrelated frame state remains Writer-owned.

Image resources are copied into `Pictures/` and the direct `draw:image` reference is updated.

Selected image actions are checked for package-destination collisions before mutation. Reuse of the same destination is allowed only when content hashes agree; conflicting content fails instead of being silently ordered or overwritten.

## Document-capability automation

**Advanced orchestration facade**

```php
public function automateDocumentCapabilities(
    ConcretePreflightResult $preflight
): void
```

The current document-capability family is metadata only.

READY metadata operations are delegated to the existing metadata owner. The supported targets and concrete payload contracts are the same ones validated by [Concrete Preflight](preflight.md).

This is not an arbitrary “call a document method” facility.

## Why the family methods are Advanced

The three public family methods are real supported facade/extension seams, but they are **not equivalent to the common `automate()` call**.

Calling them individually:

- executes only that family;
- does not provide the common invocation-wide snapshot/rollback boundary;
- does not set the common single-success lifecycle gate.

They remain public because their dispatch/signatures matter for current orchestration and subclass compatibility. Normal application code should prefer `automate()`.

In particular, do not reproduce the three calls manually merely to imitate the common facade:

```php
// Advanced orchestration, not equivalent to automate():
$template->automateNativeObjectActions($contract, $preflight);
$template->automateDependencies($contract, $preflight);
$template->automateDocumentCapabilities($preflight);
```

The sequence lacks the common atomic transaction boundary.

## READY does not eliminate runtime failures

Concrete Preflight is a complete dry run for the bounded current state, but execution still reasserts critical invariants.

Runtime state can have changed after the supplied `DocumentInspection`, package-resource collisions can become relevant during execution, or forged/inconsistent operation evidence can violate executor invariants.

Automation therefore does not blindly trust arbitrary constructed result objects merely because their status says `READY`.

A READY preflight is the required execution gate, not a promise that runtime mutation can never throw.

## Failure and retry behavior

The common facade has three important cases:

| Outcome | Working state | Single-success gate |
| --- | --- | --- |
| success | automation changes retained | consumed |
| execution failure + successful rollback | pre-invocation state restored | not consumed |
| execution failure + rollback failure | rollback failure contract exposes both failures | not a normal reusable state guarantee |

After a successful rollback, application code can correct the cause, obtain an appropriate new inspection/preflight if state/data changed, and retry.

## Save remains explicit

Automation mutates the in-memory/current package Working State. It does not save implicitly.

```php
$template->automate($contract, $preflight);

// Further supported imperative changes may be made here if desired.

$template->save($outputPath);
```

The resulting ODT is persisted only when the normal save lifecycle is invoked.

## Relationship to the original convenience idea

At application level, the complete pipeline can now provide the convenience once associated with a “map and render variables” operation:

```text
inspect template
→ understand dependencies/native targets
→ map application data
→ validate concrete invocation
→ atomically execute
→ save
```

The difference is that the engine does not hide these decisions inside an opaque method.

Applications can inspect the contract, mapping resolution, operation readiness, and diagnostics before mutation. A future convenience facade can compose these established semantics without redefining them.

## See also

- [Template Contract](template-contract.md)
- [Mapping](mapping.md)
- [Concrete Preflight](preflight.md)

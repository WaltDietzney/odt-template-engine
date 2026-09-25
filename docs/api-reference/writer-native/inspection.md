# Inspection

ODT Template Engine provides three inspection views with deliberately different responsibilities. The two primary views answer different questions:

```text
inspect()          = what native structure exists in the current Working Document?
inspectTemplate()  = what semantic contract did the original Writer-authored template declare?
```

Neither operation exposes mutable DOM nodes.

## `inspect()`

**Recommended · Writer-native Objects**

```php
public function inspect(): DocumentInspection
```

Returns a fresh, read-only snapshot of named native structures in the **current Working Document**.

Use it when application logic or tooling needs to discover the native Sections, Bookmarks, tables, or frames that exist *now*, including the effects of earlier Working Document mutations.

```php
$inspection = $template->inspect();

$table = $inspection->table('ExperienceTable');

if ($table !== null) {
    echo $table->rowCount();
}
```

### Lifecycle

Inspection is non-mutating. Each call creates a new snapshot; it is not a live view.

Working Document mutations can therefore change the result of a later call:

```php
$before = $template->inspect();

$template->section('Profile')->replaceContent($content);

$after = $template->inspect();
```

Calling `load()` resets the Working Document to the original template and later `inspect()` calls reflect that reset state.

### Coverage

The current native-object inspector has intentionally different coverage by object type:

| Object | `content.xml` body | supported master-page header/footer content in `styles.xml` |
| --- | --- | --- |
| Sections | yes | no |
| Bookmarks | yes | no |
| Tables | yes | yes |
| Frames | yes | yes |

Do not infer package-wide discovery from `inspect()`.

### `DocumentInspection`

```php
sections(): array
bookmarks(): array
tables(): array
frames(): array
diagnostics(): array

section(string $name): ?SectionDescriptor
bookmark(string $name): ?BookmarkDescriptor
table(string $name): ?TableDescriptor
frame(string $name): ?FrameDescriptor

toArray(): array
```

The plural methods return descriptor lists. Singular lookup is a convenience lookup in the snapshot and returns `null` when no matching descriptor is present.

It is **not** the strict addressing API. Use `$template->section()`, `bookmark()`, `table()`, or `frame()` when you need a typed target for a Writer-native operation; those target resolvers have their own not-found/ambiguity contract.

`toArray()` returns a deterministic machine-readable projection of the four descriptor collections plus diagnostics.

## Native descriptors

Descriptors are immutable inspection values. They describe native Writer structure without exposing DOM nodes.

### `SectionDescriptor`

```php
name(): string
documentPart(): string
childSummary(): array
nestedNamedObjects(): array
diagnostics(): array
toArray(): array
```

`childSummary()` summarizes recognized child structure. `nestedNamedObjects()` returns compact `NamedObjectReference` values for named native objects contained by the Section.

### `BookmarkDescriptor`

```php
name(): string
documentPart(): string
hasStart(): bool
hasEnd(): bool
topology(): string
text(): ?string
diagnostics(): array
toArray(): array
```

Stable topology values are:

```text
collapsed
inline
paragraph_spanning
list_spanning
table_spanning
mixed_block
malformed
```

The descriptor can expose a deterministic plain-text projection where inspection can derive one. Topology classification does not by itself promise that the corresponding range is safely mutable; mutation has a narrower contract documented with Bookmarks.

### `TableDescriptor`

```php
name(): string
documentPart(): string
rowCount(): int
columnCount(): ?int
containingSection(): ?string
diagnostics(): array
toArray(): array
```

`columnCount()` can be `null` when a stable count cannot be projected.

### `FrameDescriptor`

```php
name(): string
documentPart(): string
payloadType(): string
width(): ?string
height(): ?string
containingSection(): ?string
diagnostics(): array
toArray(): array
```

The descriptor reports discovered frame characteristics; it does not imply that every payload type has a corresponding imperative mutation API.

### Diagnostics

`InspectionDiagnostic` exposes:

```php
code(): string
severity(): string
message(): string
targetType(): ?string
targetName(): ?string
toArray(): array
```

Stable severity values are `warning` and `error`.

Diagnostics allow inspection to report malformed or ambiguous authored structure without exposing implementation nodes.

## `inspectTemplate()`

**Recommended · Semantic template contract**

```php
public function inspectTemplate(): TemplateContract
```

Inspects the **original Writer-authored source** and returns its semantic template contract.

Use it when you need to understand what data, controls, native objects, capabilities, and dependencies the template declares. This is the source-oriented contract used by mapping and automation.

```php
$contract = $template->inspectTemplate();

foreach ($contract->dependencies() as $dependency) {
    echo $dependency->path() . PHP_EOL;
}
```

### Source versus Working Document

`inspectTemplate()` reads the original source DOMs. Rendering or imperatively mutating the Working Document does not redefine the authored contract.

Therefore:

```php
$sourceContract = $template->inspectTemplate();

$template->assign(['name' => 'Ada']);
$template->render();

$currentDocument = $template->inspect();
$stillSameSourceContract = $template->inspectTemplate();
```

The two APIs are complementary, not interchangeable.

### Contract coverage

Contract version 1 inspects:

- the document body in `content.xml`;
- direct header/header-* and footer/footer-* content under Writer master pages in `styles.xml`.

Coverage explicitly reports excluded package areas such as `meta.xml`, `settings.xml`, manifest data, and embedded objects.

### `TemplateContract`

```php
TemplateContract::CONTRACT_VERSION === 1

bindings(): array
controls(): array
nativeObjects(): array
dependencies(): array
diagnostics(): array
coverage(): TemplateContractCoverage
capabilities(): TemplateContractCapabilities
toArray(): array
```

The contract separates authored evidence from interpreted meaning and logical data dependencies. A named native Writer object is not automatically an application-data dependency.

The serialized top-level form is:

```text
contract_version
coverage
bindings
controls
native_objects
dependencies
capabilities
diagnostics
```

`toArray()` is deterministic for unchanged authored source and does not expose DOM/XPath handles or process-local object identities.

### Capabilities and diagnostics

`TemplateContractCapabilities` uses these readiness values:

```text
READY
LIMITED
BLOCKED
NOT_APPLICABLE
```

Consumers must evaluate the relevant capability rather than treating successful inspection as proof that every downstream operation is ready.

A contract may remain meaningfully inspectable while another capability, such as dependency mapping, is limited.

Detailed descriptor contracts for bindings, controls, dependencies, provenance, coverage, and mapping are documented with Mapping & Automation, where callers need those semantics operationally.

## `inspectTemplateStructure()`

**Advanced · Template-language diagnostics**

```php
public function inspectTemplateStructure(): TemplateStructureInspection
```

This is a focused source-oriented inspection of classic visible template-expression topology and normalization safety. It reads the original `content.xml`, not the current Working Document.

Use it for tooling or diagnostics concerned with placeholders, filters, conditions, foreach markers, split expressions, and normalization safety. It is **not** the unified semantic application-data contract.

A current limitation is important: this focused inspection does not inspect template expressions in `styles.xml`, including header/footer expressions.

`TemplateStructureInspection` exposes:

```php
expressions(): array
diagnostics(): array
valid(): bool
repairable(): array
unsafe(): array
expressionsByVariable(string $name): array
expressionsInScope(string $scope): array
toArray(): array
```

Use `inspectTemplate()` instead when you need the complete supported semantic source contract.

## Choosing the inspection view

| Question | API |
| --- | --- |
| What named native objects exist in the document right now? | `inspect()` |
| What did the original Writer template declare semantically? | `inspectTemplate()` |
| Is the physical classic template-expression topology safe/repairable? | `inspectTemplateStructure()` |

## See also

- [Writer-native Objects](index.md)
- [Structured Content](../structured-content/index.md)
- Template Inspection guide

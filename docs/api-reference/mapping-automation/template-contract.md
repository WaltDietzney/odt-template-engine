# Template Contract

`inspectTemplate()` turns the original Writer-authored template into a stable, machine-readable semantic contract.

Use it when application code, tooling, mapping, preflight, or automation needs to answer:

> What does this authored template contain and what application data does it require?

It is the source authority for the Mapping & Automation pipeline. It is **not** the current Working Document and it is not an ODF DOM/AST.

## Inspect the authored template

**Recommended**

```php
public function inspectTemplate(): TemplateContract
```

```php
$template = new OdtTemplate($templatePath);

$contract = $template->inspectTemplate();

foreach ($contract->dependencies() as $dependency) {
    echo $dependency->path() . PHP_EOL;
}
```

The contract is derived from the original authored source. Rendering or imperatively mutating the Working Document does not redefine it.

This distinction is fundamental:

```text
inspect()          = current native Working Document state
inspectTemplate()  = original authored semantic template contract
```

For physical classic-template expression diagnostics and normalization safety, the separate Advanced API `inspectTemplateStructure()` has a narrower responsibility.

## Contract version

The current stable serialized contract version is:

```php
TemplateContract::CONTRACT_VERSION === 1
```

Consumers that persist or exchange `toArray()` output should treat `contract_version` as the format version of that machine-readable projection.

## `TemplateContract`

```php
bindings(): array
controls(): array
nativeObjects(): array
dependencies(): array
diagnostics(): array
coverage(): TemplateContractCoverage
capabilities(): TemplateContractCapabilities
toArray(): array
```

Its serialized top-level shape is:

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

The contract deliberately separates three concepts:

```text
authored evidence
      ↓
interpreted template meaning
      ↓
logical data dependency
```

For example, the existence of a named Writer Section or frame is source evidence. Its name alone does **not** make it an application-data dependency.

## Bindings

`bindings()` returns `BindingDescriptor` values describing authored binding sites such as supported visible scalar/filter expressions and supported native-field evidence.

```php
kind(): string
rawText(): string
variableName(): ?string
filterName(): ?string
filterOption(): ?string
supportState(): string
provenance(): SourceProvenance
dependencyId(): ?string
toArray(): array
```

A binding can point to a logical dependency through `dependencyId()`. The dependency is the application-data requirement; the binding is the authored place/evidence that consumes it.

Do not use `rawText()` as a substitute for semantic dependency identity.

## Controls

`controls()` returns `ControlDescriptor` values.

```php
id(): string
kind(): string
representation(): string
supportState(): string
scope(): DataScopeDescriptor
markerEvidence(): array
dependencyIds(): array
createdScope(): ?DataScopeDescriptor
carrierNativeObjectId(): ?string
toArray(): array
```

Controls remain distinct from bindings. The contract can represent classic controls and recognized declarative/native Section controls without claiming identical execution semantics for every representation.

`dependencyIds()` identifies the logical requirements used by the control. A collection control can create a child data scope exposed through `createdScope()`.

## Dependencies

`dependencies()` is the primary template-side vocabulary for Mapping.

Each `DependencyDescriptor` describes one logical application-data requirement deduplicated within its semantic scope:

```php
id(): string
kind(): string
name(): string
scope(): DataScopeDescriptor
path(): string
evidenceIds(): array
toArray(): array
```

Current dependency kinds are:

```text
VALUE
COLLECTION
```

Representative paths are:

```text
name
experience[]
experience[].company
experience[].projects[]
experience[].projects[].title
```

These are **semantic data-scope paths**, not XPath expressions and not physical ODF locations.

Multiple authored evidence sites can consume one logical dependency. `evidenceIds()` links the requirement back to those source facts.

## Data scopes

`DataScopeDescriptor` models logical scope:

```php
DataScopeDescriptor::ROOT
DataScopeDescriptor::COLLECTION_ITEM
```

Read surface:

```php
id(): string
kind(): string
parentId(): ?string
collectionDependencyId(): ?string
pathPrefix(): string
toArray(): array
```

The class also exposes construction/path helpers such as `root()`, `collectionItem(...)`, and `dependencyPath(...)`. They support the semantic model; normal application code generally consumes scopes supplied by the contract rather than constructing a second template schema.

A collection establishes a scope boundary. That boundary becomes important during Mapping: collection relationships are not guessed by an unrestricted same-name search.

## Native objects

`nativeObjects()` returns source-oriented `NativeObjectDescriptor` evidence for the native object families recognized by the semantic inspector, including Sections, bookmarks, tables, and frames.

```php
kind(): string
name(): ?string
provenance(): SourceProvenance
id(): string
ownerIds(): array
toArray(): array
```

This inventory is the template-side authority used when Mapping explicitly targets a native-object action.

Native-object discovery does not itself authorize mutation. Automation capability and applicability are separate concerns.

## Source provenance

Bindings, native objects, controls, diagnostics, and dependencies can be traced back to authored evidence through `SourceProvenance`.

```php
evidenceId(): string
sourcePart(): string
regionKind(): string
regionOwner(): ?string
carrierKind(): string
representationKind(): string
sourceOrder(): int
physicalScope(): ?string
nativeOwnerChain(): array
toArray(): array
```

Provenance is explanation and traceability data. It deliberately exposes neither XPath handles nor mutable DOM nodes.

This makes it suitable for diagnostics and future authoring/tooling clients without coupling them to the engine's internal DOM implementation.

## Coverage

The semantic contract is deliberately bounded rather than package-wide.

`TemplateContractCoverage` exposes:

```php
inspectedRegions(): array
excludedParts(): array
toArray(): array
```

Contract version 1 inspects:

- `office:body/office:text` in `content.xml`;
- direct header/header-* and footer/footer-* content under each Writer `style:master-page` in `styles.xml`.

The contract explicitly reports these excluded areas:

```text
meta.xml
settings.xml
META-INF/manifest.xml
embedded_objects
```

A missing item outside the declared coverage is therefore not evidence that the ODT package itself does not contain it.

## Capability readiness

`TemplateContractCapabilities` describes source-contract readiness for named semantic capabilities.

```php
TemplateContractCapabilities::READY
TemplateContractCapabilities::LIMITED
TemplateContractCapabilities::BLOCKED
TemplateContractCapabilities::NOT_APPLICABLE

readiness(string $capability): ?string
toArray(): array
```

Current contract inspection projects readiness for capabilities used by the authoring/mapping pipeline, including inspection, dependency mapping, and native field binding where applicable.

Readiness must be interpreted literally:

- **READY** — the source contract is ready for that semantic capability;
- **LIMITED** — useful contract information exists, but a limitation applies;
- **BLOCKED** — the relevant source condition prevents the capability;
- **NOT_APPLICABLE** — the capability does not apply to this template evidence.

An unknown capability name returns `null`.

Successful `inspectTemplate()` therefore does not mean that every downstream operation is executable. Mapping and concrete application data still require their own validation and preflight.

## Diagnostics

`diagnostics()` returns `TemplateContractDiagnostic` values:

```php
code(): string
severity(): string
message(): string
subjectId(): ?string
provenance(): ?SourceProvenance
toArray(): array
```

These diagnostics describe the **authored source contract**.

They must not be confused with later Mapping or Concrete Preflight diagnostics, which answer different questions:

```text
TemplateContractDiagnostic
    = what is notable/problematic in the authored template?

Mapping diagnostic
    = is the application-to-template relationship valid?

Concrete preflight diagnostic
    = can this concrete data invocation execute safely?
```

## Non-mutating boundary

`inspectTemplate()` is non-mutating.

It does not:

- render the template;
- change the Working Document;
- resolve application data;
- validate a MappingDefinition against concrete data;
- execute native-object actions;
- bind metadata;
- save the document.

Those responsibilities belong to later Mapping, Preflight, Automation, or ordinary document lifecycle APIs.

## Relationship to Mapping & Automation

The Template Contract is the source-side foundation of the optional automation workflow:

```text
Original Writer Template
        ↓
inspectTemplate()
        ↓
TemplateContract
        ↓
Mapping
        ↓
Concrete Preflight
        ↓
Automation
        ↓
Working Document
```

The important architectural boundary is that each stage adds information without silently changing the preceding contract.

Mapping does not rewrite the `TemplateContract`. Preflight does not mutate the document. Automation is the later mutation boundary.

## Minimal inspection example

```php
$template = new OdtTemplate($templatePath);
$contract = $template->inspectTemplate();

foreach ($contract->dependencies() as $dependency) {
    printf(
        "%s (%s)%s",
        $dependency->path(),
        $dependency->kind(),
        PHP_EOL
    );
}

$mappingReadiness = $contract
    ->capabilities()
    ->readiness('dependency_mapping');

if ($mappingReadiness !== TemplateContractCapabilities::READY) {
    // inspect diagnostics before constructing/executing mapped automation
}
```

## See also

- [Writer-native Inspection](../writer-native/inspection.md)
- [Mapping](mapping.md)
- [Concrete Preflight](preflight.md)
- [Automation](automation.md)

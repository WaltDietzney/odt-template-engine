# Mapping

Mapping connects application data to the semantic requirements and supported action targets discovered in a `TemplateContract`.

It is **optional** and **non-mutating**. A mapping definition does not render the template, change the Working Document, or replace the contract.

The central idea is:

```text
application data path
        ↓
explicit mapping or bounded scoped convention
        ↓
TemplateContract dependency / supported action target
```

## Application paths

**Recommended**

Create an immutable application-data path with:

```php
ApplicationPath::parse(string $path): ApplicationPath
```

Examples:

```php
ApplicationPath::parse('person.name');
ApplicationPath::parse('jobs[]');
ApplicationPath::parse('jobs[].employer');
ApplicationPath::parse('jobs[].projects[].title');
```

The grammar is deliberately small:

```text
segment      := [A-Za-z_][A-Za-z0-9_-]*
collection   := segment[]
path         := segment(.segment)*
```

An empty or malformed path throws `InvalidArgumentException`.

Read surface:

```php
segments(): array
canonical(): string
terminalKind(): string
hasCollections(): bool
collectionPrefixes(): array
__toString(): string
```

`ApplicationPathSegment` is an Advanced supporting value with kinds `VALUE` and `COLLECTION`.

An `ApplicationPath` describes where data lives. It performs no data access itself.

## Mapping definition

**Recommended**

```php
new MappingDefinition(
    array $dependencies = [],
    array $nativeObjectActions = [],
    array $documentCapabilities = []
)
```

The three lists represent distinct target families:

```text
Dependency Target
Native Object Action Target
Document Capability Target
```

Each list is type-checked. Passing a value of the wrong mapping-rule type throws `InvalidArgumentException`.

Read surface:

```php
dependencies(): array
nativeObjectActions(): array
documentCapabilities(): array
```

The definition is immutable explicit configuration. It does not validate, resolve, mutate, render, or save anything.

## Dependency mapping

**Recommended**

Use `DependencyMapping` when an application path and template dependency do not have the same semantic path, or when a collection boundary must be established explicitly.

```php
new DependencyMapping(
    ApplicationPath $source,
    string $dependencyPath
)
```

Example:

```php
new DependencyMapping(
    ApplicationPath::parse('person.name'),
    'name'
);

new DependencyMapping(
    ApplicationPath::parse('jobs[]'),
    'experience[]'
);

new DependencyMapping(
    ApplicationPath::parse('jobs[].employer'),
    'experience[].company'
);
```

The dependency target is a semantic `TemplateContract` dependency path, not an XPath or placeholder string.

The target path must not be empty.

### VALUE and COLLECTION shape

Collection shape is part of the contract.

A `COLLECTION` dependency must be mapped from an application path whose terminal segment is also a collection. Likewise a `VALUE` dependency cannot be targeted by a terminal collection path.

Thus:

```text
jobs[]        → experience[]     valid shape
jobs          → experience[]     invalid shape
person.name   → name             valid shape
people[]      → name             invalid shape
```

Static validation reports a value/collection mismatch rather than coercing it.

## Scoped same-name resolution

Not every scalar child needs an explicit rule.

Dependency resolution precedence is:

```text
1. explicit DependencyMapping
2. scoped same-name resolution
3. unresolved
```

At ROOT scope, a VALUE dependency such as `name` may resolve from the root application key `name`.

It does **not** search unrelated descendants. Given:

```php
['person' => ['name' => 'Walter']]
```

a root template dependency `name` does not silently search inside `person`. Map `person.name → name` explicitly.

### Collection boundaries are always explicit

The engine never establishes a collection relationship by same-name convention.

If the template requires:

```text
experience[]
experience[].company
experience[].position
```

and the application model contains:

```text
jobs[]
jobs[].company
jobs[].position
```

this explicit rule establishes the collection scope:

```php
new DependencyMapping(
    ApplicationPath::parse('jobs[]'),
    'experience[]'
)
```

Inside that established scope, the immediate same-name VALUE dependencies can resolve as:

```text
jobs[].company   → experience[].company
jobs[].position  → experience[].position
```

That is the bounded convenience behind scoped same-name resolution.

### Nested collections

Every nested collection boundary must also be explicitly connected.

For:

```text
jobs[].projects[]
    → experience[].projects[]
```

add:

```php
new DependencyMapping(
    ApplicationPath::parse('jobs[].projects[]'),
    'experience[].projects[]'
)
```

Only then can a same-name child such as `title` resolve within that nested item scope.

There is no unrestricted recursive name search.

## Explicit mapping wins

An explicit dependency rule suppresses a same-name candidate for the same target.

For example:

```php
new DependencyMapping(
    ApplicationPath::parse('jobs[].projects[].clientName'),
    'experience[].projects[].customer'
)
```

uses `clientName` even if the application item also contains a `customer` key.

This makes mapping behavior deterministic and inspectable.

## Native-object action mapping

**Recommended for explicit Phase-E actions**

A native object discovered by the Template Contract is not automatically mutated.

An action must be requested explicitly:

```php
new NativeObjectActionMapping(
    ApplicationPath $source,
    string $targetKind,
    string $targetName,
    string $actionId
)
```

Example:

```php
new NativeObjectActionMapping(
    ApplicationPath::parse('branding.logo'),
    'frame',
    'CompanyLogo',
    'replace-image'
)
```

The rule identifies four things independently:

```text
application source
native object kind
native object name
registered action
```

All target strings must be non-empty.

Mapping does not invent action semantics. The target must exist uniquely in the source contract, and the action must be registered for that target kind and applicable to the source target.

Concrete payload validation belongs to Preflight.

## Document-capability mapping

**Recommended for supported bounded document capabilities**

```php
new DocumentCapabilityMapping(
    ApplicationPath $source,
    string $group,
    string $target
)
```

Example:

```php
new DocumentCapabilityMapping(
    ApplicationPath::parse('document.author'),
    'metadata',
    'creator'
)
```

Group and target must be non-empty.

This does not provide arbitrary access to document services. The current bounded Phase-E document-capability family is metadata; supported targets are verified by the engine capability catalog and later concrete preflight.

## A complete definition

A definition can combine all three target families:

```php
$mappings = new MappingDefinition(
    [
        new DependencyMapping(
            ApplicationPath::parse('customer.display_name'),
            'client'
        ),
        new DependencyMapping(
            ApplicationPath::parse('project.team[]'),
            'members[]'
        ),
        new DependencyMapping(
            ApplicationPath::parse('project.team[].full_name'),
            'members[].name'
        ),
    ],
    [
        new NativeObjectActionMapping(
            ApplicationPath::parse('branding.logo'),
            'frame',
            'CompanyLogo',
            'replace-image'
        ),
    ],
    [
        new DocumentCapabilityMapping(
            ApplicationPath::parse('document.author'),
            'metadata',
            'creator'
        ),
    ]
);
```

## Static validation

Static validation checks the relationship between a `MappingDefinition`, the source `TemplateContract`, and the engine's projected capabilities without mutating the document.

The underlying validator checks, where applicable:

- dependency target existence and uniqueness;
- VALUE/COLLECTION shape agreement;
- collection-scope relationships;
- duplicate mutation targets;
- native target existence, kind, and uniqueness;
- registered native-action support and source applicability;
- supported document-capability targets;
- contract/capability readiness.

Checks that require concrete application data are retained as deferred checks rather than guessed.

The validation result exposes:

```php
valid(): bool
diagnostics(): array
deferredChecks(): array
```

`MappingDiagnostic` and `DeferredMappingCheck` expose `code()`, `message()`, and `context()`.

These are **Advanced supporting contracts**. Normal end-to-end application code can use Concrete Preflight as the complete gate before Automation rather than manually orchestrating the lower-level validator/capability services.

## Mapping resolution

The non-mutating resolver materializes how the application data relates to the template:

```php
MappingResolutionResolver::resolve(
    MappingDefinition $definition,
    TemplateContract $contract,
    array $data
): MappingResolution
```

It performs static validation first. A statically invalid definition throws `MappingResolutionException`; `validationResult()` exposes the complete static validation result.

`MappingResolution` contains:

```php
dependencies(): array
nativeObjectActions(): array
documentCapabilities(): array
```

Resolution does not mutate the definition, application data, contract, or Working Document.

### Dependency resolution

`DependencyMappingResolution` exposes:

```php
target(): DependencyDescriptor
status(): string
source(): ?ApplicationPath
provenance(): ?string
dataResolution(): ?ApplicationDataResolution
```

Stable statuses/provenance:

```text
RESOLVED
UNRESOLVED

EXPLICIT
SCOPED_SAME_NAME
```

A dependency can be semantically **RESOLVED** even when the concrete data at its resolved source path is missing, null, or has the wrong shape. Those are data-resolution facts, not failures to determine the mapping relationship.

Concrete Preflight decides whether such facts make the invocation executable.

Native-object action and document-capability resolutions are explicit-only. Their provenance is `EXPLICIT`.

## Application-data resolution

`ApplicationDataResolution` is the read-only result of traversing a resolved application path.

Stable statuses are:

```text
MISSING
NULL
PRESENT
EMPTY_COLLECTION
WRONG_SHAPE
```

Read surface:

```php
status(): string
value(): mixed
itemIndex(): ?int
items(): array
```

Nested collection results retain their zero-based item indices, allowing later diagnostics/preflight to identify the concrete failing item without flattening the application model.

Mapping resolution deliberately does not coerce wrong shapes or decide action-specific payload validity.

## What Mapping does not do

Mapping is not:

- a second template schema;
- an ODF/DOM model;
- a render tree;
- a layout model;
- a universal action DSL;
- automatic mutation of discovered Writer objects;
- a render/save operation.

It establishes and exposes the application-to-template relationship.

## From Mapping to Preflight

At the end of Mapping, the engine can explain:

```text
template dependency/action target
        ↕
application source path
        ↕
concrete data-resolution status
```

But no document mutation has occurred.

The next stage, Concrete Preflight, answers the stronger question:

> Given this MappingDefinition, this TemplateContract, this concrete application data, and this current Working Document, can the complete automation invocation execute safely?

Only after that gate succeeds does Automation mutate the document.

## See also

- [Template Contract](template-contract.md)
- [Concrete Preflight](preflight.md)
- [Automation](automation.md)

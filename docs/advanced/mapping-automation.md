# Mapping, Preflight & Automation

Mapping & Automation is an **optional integration workflow** for applications whose data model does not simply mirror the Writer template. It is not a fourth document-authoring model and it is not required for normal `assign() → render() → save()` usage.

Use it when an application should inspect what a Writer-authored template requires, explicitly connect application data to that contract, validate one concrete invocation before mutation, and then execute the supported operations atomically.

## The complete workflow

```text
Writer-authored template
        ↓
inspectTemplate()
        ↓
TemplateContract
        ↓
MappingDefinition + application data
        ↓
Concrete Preflight
        ↓
READY
        ↓
automate()
        ↓
save()
```

Mutation starts only at `automate()`.

## 1. Inspect the template contract

```php
$template = new OdtTemplate($templatePath);
$contract = $template->inspectTemplate();
```

The TemplateContract describes the **original authored source**, not the current mutable Working Document. It exposes semantic dependencies such as `client`, `members[]`, or `members[].name`. These are logical data paths, not XPath expressions or ODF addresses.

## 2. Map application data to template meaning

If an application uses `customer.display_name` while the template expects `client`, make the translation explicit:

```php
$mappings = new MappingDefinition([
    new DependencyMapping(
        ApplicationPath::parse('customer.display_name'),
        'client'
    ),
]);
```

Collections are explicit. The mapper does not perform an unrestricted recursive same-name search through arbitrary application data. Within an explicitly established scope, supported same-name dependencies can resolve without repeating every scalar mapping.

Mapping is **non-mutating**.

## 3. Mapping can describe three operation families

A `MappingDefinition` can contain:

```text
Dependency mappings
Native-object action mappings
Document-capability mappings
```

For example, C05 maps application data to normal dependencies, a Section `replace-content` action, a frame `replace-image` action, and Metadata `creator`.

The current native-action family includes bounded Section content replacement, Bookmark text replacement, and named-frame image replacement. Document capabilities currently include Metadata targets.

These mappings describe intent; they do not mutate the ODT.

## 4. Concrete Preflight validates one real invocation

Mapping resolution alone cannot prove that the actual data and current document state are executable.

```php
$preflight = (new ConcreteMappingPreflight())->preflight(
    $mappings,
    $contract,
    $application,
    $template->inspect()
);

if (!$preflight->ready()) {
    foreach ($preflight->diagnostics() as $diagnostic) {
        // Present or log the concrete problem.
    }

    return;
}
```

Concrete Preflight combines the TemplateContract, MappingDefinition, concrete application data, and current `DocumentInspection`. It is **non-mutating**.

It checks concrete data state and operation-specific payloads, including missing/null values, collection shape, structured Section content, Bookmark strings, mapped image payloads, Metadata values, and current named-frame state.

A resolved mapping is therefore not the same as a READY invocation.

## 5. Automate atomically

```php
$template->automate($contract, $preflight);
$template->save($outputPath);
```

The common `automate()` call applies the supported families in this order:

```text
1. Native Object Actions
2. Dependency Automation
3. Document Capabilities
```

The complete invocation is protected by one Working-State snapshot. If execution fails and rollback succeeds, the pre-invocation Working State is restored.

`automate()` does **not** call `render()` and does **not** call `save()`.

## Lifecycle: one successful common automation per Working Document

The 1.0 common facade allows only **one successful `automate()` invocation per Working-Document lifecycle**. A second successful common invocation without a reset through `load()` is a lifecycle error.

A failed automation whose rollback succeeds does not consume that successful invocation allowance. If state or data changes before retrying, obtain the appropriate new inspection/preflight.

## Direct declarative execution is different

`executeDeclarative($contract, $values)` is an Advanced direct facade for supported declarative Writer Section controls when the caller already supplies data in the template contract's own vocabulary.

It does not perform application-data mapping or Concrete Preflight and does not call `render()` or `save()`.

[C04 — Declarative Structured Collections](../../samples/sample_C04_declarative_structured_collections.php) shows that template-shaped path. When the application has its own data vocabulary, use Mapping → Concrete Preflight → `automate()` instead.

## Relationship to Simple Template Processing

Mapping & Automation does not replace the classic lifecycle. For a straightforward `{{name}}` template, normal application code should usually remain:

```php
$template->assign(['name' => 'Ada']);
$template->render();
$template->save($outputPath);
```

Use Mapping & Automation when its explicit contract, translation, validation, native actions, capabilities, or atomic execution solve an actual integration problem.

If an application deliberately combines automation with separately staged classic assignments, it must respect both lifecycles. `automate()` is not an alias for `render()`.

## The former map-and-render convenience idea

At application level, this workflow fulfills the purpose once associated with a single map-and-render convenience operation:

```text
inspect → map → validate → execute → save
```

The 1.0 API keeps those decisions inspectable instead of hiding them inside one opaque method. A future convenience facade may compose these established semantics, but it must not redefine them.

## Learn from C05

[C05 — Mapping & Automation](../../samples/sample_C05_mapping_automation.php) is the canonical executable example. It combines application-to-template dependency mapping, a Section `replace-content` action, a named-frame `replace-image` action, Metadata creator mapping, Concrete Preflight, one atomic `automate()` invocation, and explicit `save()`.

## See also

- [Template Inspection](template-inspection.md)
- [Mapping & Automation API Reference](../api-reference/mapping-automation/index.md)
- [Template Contract](../api-reference/mapping-automation/template-contract.md)
- [Mapping](../api-reference/mapping-automation/mapping.md)
- [Concrete Preflight](../api-reference/mapping-automation/preflight.md)
- [Automation](../api-reference/mapping-automation/automation.md)

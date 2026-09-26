# TEMPLATE-AUTHORING-01B Slice 0 — Characterization / Compatibility Gate

Status: COMPLETE / GATE GREEN / NO PRODUCTION CHANGE

## Purpose

Freeze the existing inspection/runtime boundaries that Unified Template Inspection must preserve before any TemplateContract production code is introduced.

Slice 0 implements no new public behavior.

It exists to distinguish:

```text
intentional Phase-B additions
from
regressions against existing inspection/runtime semantics
```

## Characterized boundaries

### Existing public inspection result types

The gate freezes:

```php
$template->inspect(): DocumentInspection
$template->inspectTemplateStructure(): TemplateStructureInspection
```

and the current top-level toArray() shapes of both result types.

### Focused source inspection lifecycle

inspectTemplateStructure() continues to describe original source content.xml and remains semantically stable after render() and save() on the working document.

### Current source-part coverage gap

Visible template expressions in page-owned styles.xml content are currently invisible to inspectTemplateStructure().

This is characterized legacy behavior, not the Phase-B target.

### Current native-object part asymmetry

DocumentInspector currently discovers:

```text
Sections   content.xml only
Bookmarks  content.xml only
Tables     content.xml + styles.xml
Frames     content.xml + styles.xml
```

This asymmetry is frozen so Slice 1 can expand source-template coverage intentionally rather than accidentally changing inspect().

### Condition grammar divergence

The runtime condition evaluator currently understands comparison syntax such as:

```text
gender=="female"
```

while TemplateStructureInspector currently classifies the corresponding visible IF expression as UNSUPPORTED / UNSAFE.

This divergence is an explicit Slice-0 baseline and must be reconciled later without changing characterized runtime truth semantics.

### Diagnostic serialization

The current diagnostic serialization shapes are frozen as compatibility baselines:

```text
Document InspectionDiagnostic
    code
    severity
    message
    target_type
    target_name

TemplateStructureDiagnostic
    code
    severity
    message
    classification
    repairable
    expression
    scope
```

Unified TemplateContract diagnostics may compose/adapt these semantics later, but existing APIs must remain unchanged.

## Automated gate

The active test is:

```text
tests/Integration/TemplateAuthoring01BSlice0CompatibilityGateTest.php
```

It covers:

1. existing inspection facade return types;
2. existing top-level serialization shapes;
3. focused source inspection stability across render/save;
4. current styles.xml expression blind spot;
5. current native inspection part asymmetry;
6. runtime-vs-inspector condition grammar divergence;
7. current diagnostic serialization field baselines.

## Exit criterion

Slice 0 is complete when the focused gate is green without new warnings and git diff --check is clean.

No production source file is changed in Slice 0.

Once confirmed, proceed to:

```text
TEMPLATE-AUTHORING-01B Slice 1 — Contract Skeleton & Source-Region Discovery
```


## Closeout

The Slice 0 compatibility gate was executed successfully.

Result:

```text
GREEN
```

The characterization baseline is therefore accepted for Phase-B implementation.

No production source file was changed by Slice 0.

The next implementation slice is:

```text
TEMPLATE-AUTHORING-01B Slice 1 — Contract Skeleton & Source-Region Discovery
```

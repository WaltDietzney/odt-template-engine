# ARCH-06 — AbstractOdtTemplate Reassessment Closeout

## Status

ARCH-06 is complete as an architecture/reassessment milestone.

This closeout records the final interpretation of ARCH-06A through ARCH-06D and defines the transition to a final structural-foundation milestone before document-default, style-context, asset-context, and document-structure work begins.

ARCH-06 did not intentionally change production behavior.

## 1. Purpose of ARCH-06

ARCH-06 reassessed `AbstractOdtTemplate` after ARCH-02 through ARCH-05 extracted major responsibilities into explicit package, document, template-processing, and structured-document services.

The milestone asked whether `AbstractOdtTemplate` still represented a meaningful abstraction and whether the existing inheritance model should remain part of the long-term architecture.

The investigation deliberately separated two questions:

1. What is the compatibility contract of the class today?
2. What should the final structural foundation of the engine look like before the next document/style architecture phase begins?

ARCH-06 answers the first question. The second is assigned to ARCH-07.

## 2. Evidence produced

ARCH-06 produced the following records:

- `ARCH-06A_ABSTRACT_ODT_TEMPLATE_AUDIT.md` — responsibility and inheritance audit;
- `ARCH-06B_BASE_CLASS_CONTRACT.md` — compatibility/base-class contract analysis;
- `ARCH-06C_COMPATIBILITY_CHARACTERIZATION.md` — executable characterization of protected dispatch, lifecycle, state mirrors, and subclass behavior;
- `ARCH-06D_FACADE_STATE_ACCESS_BOUNDARY.md` — state-access boundary review.

ARCH-06C added focused tests proving important parts of the factual compatibility contract. The full suite remained green at 105 tests / 853 assertions at the ARCH-06D checkpoint.

## 3. Final findings

### 3.1 `AbstractOdtTemplate` is not a meaningful final abstract contract today

`AbstractOdtTemplate` is formally declared `abstract`, but it defines no abstract methods and current repository evidence does not identify a mandatory subclass-provided operation that would justify inventing new abstract methods.

Adding artificial abstract methods merely to make the class name appear correct was rejected.

### 3.2 The current class is a compatibility base

Today the class provides a mixture of:

- inherited public compatibility methods;
- protected extension/dispatch seams;
- ODF/style helpers;
- structured-insertion facade behavior;
- template inspection and residual placeholder helpers;
- debugging;
- historical document-state access.

That role is real and compatibility-sensitive, but it is not considered a satisfactory long-term structural explanation for a class named `AbstractOdtTemplate`.

### 3.3 State ownership is now clear

Authoritative state ownership remains:

```text
OdtPackage
    → package path, workspace, resources, manifest, persistence

OdtDocumentContext
    → content.xml, styles.xml, meta.xml DOMs

OdtTemplate
    → assignment/repeat state and render orchestration
```

Historical DOM/path properties in the template hierarchy are compatibility mirrors. They are not a second document owner.

`OdtTemplate::documentContext()` is already the appropriate protected boundary to the authoritative document context. ARCH-06D therefore correctly made no production change.

### 3.4 Protected polymorphism is factual compatibility behavior

ARCH-06C confirms that protected hooks are reached through real public execution flows. Repository-internal `PageLayoutOdtTemplate::adjustBulletIndentation()` is a concrete example.

Any structural consolidation must therefore distinguish between:

- intentional extension points;
- compatibility-sensitive protected seams;
- accidental inheritance coupling;
- ordinary shared implementation.

### 3.5 A compatibility base is a migration state, not the desired final structure

ARCH-06B recommended retaining `AbstractOdtTemplate` as a compatibility base in the near term and composition as the long-term direction.

This closeout clarifies that the compatibility-base model is **transitional**. It must not be read as the final architecture merely because it is the safest interpretation of the current code.

The project is still pre-1.0 and the `develop` branch is explicitly the integration line for architectural development. The next milestone may therefore make a deliberate structural change if it is well-characterized, documented, and validated.

## 4. Why ARCH-06 stops here

ARCH-06 has answered its reassessment question sufficiently:

- the class should not be given artificial abstract hooks;
- authoritative state ownership already exists outside the base class;
- the existing state-access boundary is adequate;
- compatibility and protected dispatch are characterized;
- the current inheritance arrangement is understood;
- further random responsibility extraction would risk turning ARCH-06 into open-ended cleanup.

ARCH-06 therefore closes without forcing a production refactor merely to produce code changes.

## 5. Remaining structural problem

One important problem intentionally remains open:

> The engine should enter the document-default/style/document-structure phase with a coherent and presentable facade/base structure.

The project should not permanently retain a confusing state in which `AbstractOdtTemplate` is nominally an abstract template base while semantically functioning mainly as a historical compatibility implementation container.

That problem deserves its own explicit milestone rather than being hidden inside another extraction slice.

## 6. ARCH-07 transition

The next architecture milestone is:

# ARCH-07 — Template Facade / Base Structure Consolidation

ARCH-07 is the final structural-foundation milestone before Phase B document-default, style-context, and asset-context work.

Its goal is to establish a coherent, explainable template facade/base structure.

ARCH-07 must decide and implement, in small characterized slices, the appropriate final direction for the current inheritance arrangement.

Primary questions include:

- Should `AbstractOdtTemplate` be eliminated from the active architecture?
- If retained, can it represent a genuine coherent type rather than a compatibility container?
- Which responsibilities belong directly to `OdtTemplate` as the public facade?
- Which responsibilities belong to composed services?
- Which inherited public methods must remain available?
- Which protected seams are intentional extension points and which are migration-only compatibility seams?
- Which historical state mirrors can be removed or reduced safely?
- What compatibility/deprecation policy is appropriate for the pre-1.0 development line?
- Which legacy helpers should be retired rather than carried into the style/document phase?

ARCH-07 must not preserve inheritance solely for backward compatibility if doing so leaves the structural foundation conceptually misleading. Conversely, it must not remove compatibility behavior casually; the ARCH-06C characterization is the evidence baseline for migration.

## 7. ARCH-07 target quality

The target is not a specific class diagram fixed in advance.

The target is a foundation that a PHP developer can inspect and understand without needing historical context.

A plausible composition-oriented shape is:

```text
OdtTemplate
    ├── OdtPackage
    ├── OdtDocumentContext
    ├── TemplateProcessor
    ├── StructuredElementMaterializer
    ├── TemplateTargetResolver
    ├── MetadataManager
    ├── PageLayoutManager
    └── other clearly bounded collaborators
```

This diagram is a direction, not a pre-approved implementation contract.

ARCH-07 must derive the final structure from actual code, compatibility evidence, and responsibility boundaries.

## 8. Explicit non-goals for the transition

ARCH-07 remains a structural-foundation milestone. It must not absorb the following future work merely because related code currently lives in the template hierarchy:

- `DOCUMENT-DEFAULTS-01`;
- `STYLE-CONTEXT-01`;
- `STYLE-API-02` redesign beyond what is strictly necessary for structural consolidation;
- `ASSET-CONTEXT` redesign;
- `TEMPLATE-FORMAT-PRESERVATION-01`;
- `TEMPLATE-AUTHORING-UX-01`;
- named text-box/table mutation;
- cloning / Template Instances;
- page/master-page redesign;
- header/footer architecture;
- table/frame/list layout feature work.

Those topics remain separate milestones.

## 9. Known deferred legacy finding

`ensureTableCellStyleNodesExist()` remains a known suspicious legacy/style path: its signature accepts `$styleNodes` while its implementation references an undefined `$styleMap`. Repository evidence shows no current call site or test.

ARCH-06 did not repair or characterize the broken behavior as desired semantics. Ownership and disposition should be decided separately, most naturally with style compatibility work unless ARCH-07 proves the method must be addressed to complete structural consolidation.

## 10. Validation rule going forward

The established validation workflow remains part of architectural work:

```text
automated tests
    ↓
ODT / ZIP / XML validation
    ↓
Sample Explorer
    ↓
LibreOffice headless
    ↓
PDF
    ↓
PNG
    ↓
visual review against the accepted baseline
```

Render-sensitive ARCH-07 changes must use the visual baseline where relevant.

## 11. Decision

ARCH-06 is complete.

Its durable conclusions are:

1. do not invent an abstract contract that current semantics do not support;
2. treat current `AbstractOdtTemplate` behavior as characterized compatibility, not ideal architecture;
3. keep `OdtPackage` and `OdtDocumentContext` as authoritative owners;
4. preserve protected dispatch until a deliberate migration decision is made;
5. do not add new responsibilities to `AbstractOdtTemplate`;
6. do not treat the compatibility-base state as the permanent solution;
7. perform one final structural consolidation milestone before starting Phase B.

The immediate next milestone is **ARCH-07 — Template Facade / Base Structure Consolidation**.

Semantics before implementation.

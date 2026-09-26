# STYLE-API-02A — P0 Characterization Closeout

Status: **CHARACTERIZATION COMPLETE — NO PRODUCTION CHANGE**  
Baseline: `develop` at `67507b663b55a0177b5c6e95ac6aead8e24f0134`  
Working branch: `architecture/style-api-02a-public-api-audit`

## 1. Purpose

STYLE-API-02A identified nine characterization gaps before public style API design. The priority pass separated three P0 questions that can change the boundary of STYLE-API-02B itself:

1. whether the top-level `HasStyles` compatibility phase actually dispatches;
2. which element and template subclass hooks are observable extension points today;
3. which style APIs are actually taught to library users through current documentation and samples.

This closeout records the evidence for those P0 questions. It does not repair behavior, redesign the style API, deprecate existing methods, or narrow compatibility.

## 2. P0 result summary

| Gap | Result | Consequence for STYLE-API-02B |
|---|---|---|
| GAP-01 — `HasStyles` dispatch | Current `OdtTemplate::registerStructuredHasStylesCompatibility()` does not match the actual `Contracts\HasStyles` interface because the class contains an unqualified `HasStyles` check without importing the contract. | 02B must not assume this top-level compatibility phase is active. Fixing the namespace would introduce observable behavior and must be a deliberate later change, not a refactor cleanup. |
| GAP-08 — subclass / override surface | Both semantic and legacy element hooks remain dynamically dispatched. Existing and new characterization also confirms `OdtTemplate` facade callbacks remain subclass-observable. | 02B may choose a preferred extension contract, but compatibility wrappers must preserve evidence-backed override dispatch unless a separate breaking/deprecation decision is made. |
| GAP-09 — documented / sample usage | Normal documentation recommends structured element styling. `StyleMapper::registerParagraphStyle()` is nevertheless explicitly taught as an advanced reusable named-style API and is used in the CV benchmark sample. Direct `StyleWriter` use is not taught as normal application styling. | 02B can distinguish recommended authoring, advanced named-style registration, and low-level compatibility instead of treating every public method equally. |

With these three questions characterized, no remaining P0 uncertainty blocks semantic design of STYLE-API-02B. P1/P2 characterization remains required before concrete deprecation, visibility, or registry-removal decisions.

## 3. GAP-01 — `HasStyles` dispatch

### 3.1 Source evidence

The actual public interface is:

```text
OdtTemplateEngine\Contracts\HasStyles
```

`OdtElement` imports and implements that interface.

The current `OdtTemplate` source contains:

```php
private function registerStructuredHasStylesCompatibility(OdtElement $element): void
{
    if ($element instanceof HasStyles) {
        $this->registerStyles($element->getStyleDefinitions());
    }
}
```

`OdtTemplate` is in namespace `OdtTemplateEngine` and does not import `OdtTemplateEngine\Contracts\HasStyles`. Therefore the unqualified name resolves to `OdtTemplateEngine\HasStyles`, for which no interface exists in the inspected current source tree.

### 3.2 Characterization coverage

`StyleApi02AHasStylesDispatchCharacterizationTest` records three observations:

1. a normal `Paragraph` subclass is an instance of `Contracts\HasStyles`, but `setElement()` does not call its overridden `getStyleDefinitions()` through the top-level compatibility phase;
2. an external `OdtElement` probe has the same behavior;
3. `OdtTemplateEngine\HasStyles` does not exist while `OdtTemplateEngine\Contracts\HasStyles` does.

The paragraph probe additionally saves a document and asserts that its semantic inline style still appears in `styles.xml`. This separates the dead top-level compatibility dispatch from the functioning semantic style pipeline.

### 3.3 Documentation contradiction

`STYLE_CONTEXT_01_FINAL_AUDIT.md` described `HasStyles` compatibility registration in `OdtTemplate` as active. That statement does not match the current source behavior characterized here.

This contradiction is resolved in favor of executable source plus characterization:

> The `HasStyles` interface remains a real public/source-level compatibility contract, but the specific top-level `OdtTemplate::setElement()` compatibility branch does not currently dispatch the intended `Contracts\HasStyles` implementations.

This does **not** imply that `HasStyles`, `getStyleDefinitions()`, or `registerStyles()` are globally dead. Composite elements and direct callers still expose/use portions of that surface. It only characterizes this specific facade phase.

### 3.4 Design consequence

Adding the missing import in a later slice would not be a behavior-neutral correction. It would cause `getStyleDefinitions()` to be consulted during normal `setElement()` where it currently is not. STYLE-API-02B must first decide whether that compatibility behavior is desirable, redundant, or should be represented differently.

## 4. GAP-08 — subclass and override surface

### 4.1 Existing facade evidence

The existing `AbstractOdtTemplateCompatibilityArch06CTest` already proves that subclass overrides are part of observable engine behavior. It covers protected facade dispatch through:

- `fixBrokenVariables()` and `setValuesInDom()` during `render()`;
- `replacePlaceholderWithDom()` during `setElement()`;
- `adjustBulletIndentation()` during `save()` on `PageLayoutOdtTemplate`.

Therefore protected facade dispatch cannot be treated as incidental implementation detail merely because newer services now perform more of the work.

### 4.2 Element style hooks

`StyleRequirementCollector` dynamically invokes the following self-owned hooks on every `OdtElement` in an ownership tree:

Modern semantic path:

- `getOwnStyleRequirements()`;
- `ownedElements()` for traversal.

Legacy compatibility projection:

- `getOwnRequiredParagraphStyles()`;
- `getOwnRequiredStyles()`;
- `getOwnFrameStyleRequirements()`;
- `getOwnImageStyleRequirements()`;
- `getOwnFillImageRequirements()`;
- `ownedElements()` for traversal.

The collector calls these methods polymorphically. External subclasses can therefore still affect current `setElement()` behavior by overriding them.

### 4.3 Focused STYLE-API-02A characterization

`StyleApi02AP0SubclassCompatibilityCharacterizationTest` adds an external-style probe that overrides both semantic and legacy style hooks. During normal `setElement()` it verifies that these overrides are reached.

The same test uses an `OdtTemplate` subclass that overrides:

- public `ensureParagraphStylesExist()`;
- protected `ensureTextStylesExist()`.

It returns legacy paragraph/text requirements that are not semantic-owned, then verifies that current compatibility processing dispatches through those facade methods.

This closes the specific style-related override gap without duplicating the broader ARCH-06C compatibility tests.

### 4.4 Extension classification for 02B

The evidence supports this pre-design classification:

| Surface | Current status |
|---|---|
| `getOwnStyleRequirements()` | active modern extension hook |
| `ownedElements()` | active modern + compatibility traversal hook |
| legacy `getOwnRequired*()` style getters | active public override-sensitive compatibility hooks |
| frame/image/fill own getters | active public override-sensitive compatibility hooks |
| `ensureParagraphStylesExist()` | public facade behavior, subclass-observable |
| `ensureTextStylesExist()` | protected facade behavior, subclass-observable |
| `replacePlaceholderWithDom()` and related structured callbacks | already characterized protected compatibility surface |
| top-level `HasStyles` compatibility dispatch | currently non-dispatching due GAP-01 |

02B may define semantic requirements as the recommended custom-element API, but it cannot simply make the legacy getters non-polymorphic or bypass facade callbacks without an explicit compatibility decision.

## 5. GAP-09 — documented and sample usage

### 5.1 Recommended application-facing layer

The current README and styling guides consistently teach structured elements as the normal authoring surface:

- `Paragraph` text and paragraph style arrays;
- `RichText` composition;
- `RichTable` / `RichTableCell` style options;
- image/frame element options;
- `OdtTemplate::setElement()` for insertion.

The table styling guide explicitly says application code should treat `RichTable`, `RichTableCell`, and their documented style options as the public abstraction rather than depending on XML placement.

This is strong evidence for the **recommended application authoring layer**.

### 5.2 Advanced named paragraph registration is intentionally taught

The documentation also deliberately presents a second application-visible layer.

`docs/styling/style-model.md` demonstrates:

```php
StyleMapper::registerParagraphStyle('CVEntryTitle', [
    'margin-top' => '0.1cm',
    'margin-bottom' => '0.03cm',
]);

$paragraph = new Paragraph('CVEntryTitle');
```

`docs/styling/text-and-paragraph-styles.md` repeats this pattern for semantic reusable paragraph styles and explains the process-scoped registry caveat.

The professional CV sample `sample_21_cvProfile.php` uses the same API extensively: it registers named reusable paragraph styles through `StyleMapper::registerParagraphStyle()` and then references those names from `Paragraph` instances.

Therefore:

> `StyleMapper::registerParagraphStyle()` has strong documentation and real-sample evidence as an advanced public API. It must not be classified as merely accidental public implementation detail.

The target API may later move this capability behind a clearer document-scoped or named-style facade, but compatibility/migration must be intentional.

### 5.3 Explicit text-style registration

The text/paragraph guide mentions explicit `StyleMapper` text-style registration but states that most application code does not need it. This is evidence for an **advanced/compatibility surface**, weaker than the named paragraph-style contract but still not invisible.

Exact registry-method consolidation remains P1 because `setTextStyle()` versus `registerTextStyle()` and the parallel getters need behavioral characterization before any canonical/deprecated method decision.

### 5.4 `StyleWriter`

The public style model documentation describes `StyleWriter` as an implementation utility and explicitly says it is not the recommended application-facing styling API.

A legacy sample is named `sample_test_stylewriter.php`, but its actual code does not call `StyleWriter` directly; it builds styled `RichText`, `Paragraph`, and `RichTableCell` objects and inserts them through `setElement()`.

Consequently the current public teaching evidence does **not** elevate direct `StyleWriter` calls to normal application authoring. Its exact direct-call compatibility audience remains a P1 question because existing tests and lower-level callers may still rely on it.

### 5.5 Observable diagnostics are not automatically recommended API

`samples/test_paragraph_methods.php` prints `getStyleDefinitions()` results as diagnostic output. This demonstrates observability and reinforces compatibility sensitivity, but it does not teach `getStyleDefinitions()` as the normal way to author styles.

This distinction matters for 02B:

```text
publicly observable / useful for diagnostics
!=
recommended primary authoring API
```

### 5.6 Documentation drift

`docs/styling/style-model.md` currently says that a future `StyleContext`-style architecture is tracked in `FUTURE_DEVELOPMENT.md`.

That statement is stale. STYLE-CONTEXT-01 has already established document-local `StyleContext` as semantic authority for the modern structured path.

P0 records the drift but does not rewrite the public guide before STYLE-API-02B defines the target public terminology. Documentation correction should accompany the eventual API contract so the guide describes the chosen layers rather than another transitional state.

## 6. Public-layer evidence after P0

The current evidence supports four distinct audiences/layers for 02B design:

### A. Recommended application authoring

- element constructors/fluent APIs;
- friendly text/paragraph/table-cell/image/frame style options;
- `setElement()` structured insertion.

### B. Advanced named-style authoring

- especially `StyleMapper::registerParagraphStyle()` plus `Paragraph($styleName)`;
- process-scoped behavior is currently documented;
- target API may improve scope/naming, but migration compatibility is required.

### C. Custom element / subclass extension

- modern semantic `getOwnStyleRequirements()` and ownership/resource hooks;
- legacy `getOwnRequired*()` getters remain active compatibility dispatch;
- protected/public OdtTemplate facade callbacks remain override-sensitive.

### D. Low-level compatibility / implementation

- process-global StyleMapper registries beyond the specifically taught named-style use cases;
- direct `StyleWriter` calls;
- legacy registration/writer mechanics.

P1 must determine the exact supported boundary inside layer D before visibility or deprecation changes.

## 7. What P0 does not decide

P0 does not decide:

- whether the broken top-level `HasStyles` dispatch should be restored;
- whether `HasStyles` should remain permanent, become a facade, or be deprecated;
- whether named styles should remain on `StyleMapper` or move to a new/document-scoped public facade;
- whether public static registries can be hidden;
- which `StyleWriter` helpers are intentional low-level API;
- which overlapping mapper/registry method names become canonical;
- whether legacy getters can later become thin wrappers over semantic requirements.

Those are STYLE-API-02B design questions or P1/P2 implementation prerequisites.

## 8. Gate for STYLE-API-02B

The P0 evidence is sufficient to begin semantic API design once the focused characterization tests are green.

The design phase may now assume:

1. normal application styling is element-centric;
2. reusable named paragraph styles are a real advanced public use case;
3. semantic `getOwn...` hooks are the natural modern custom-element direction;
4. legacy element getters and OdtTemplate facade callbacks are observable override-sensitive compatibility;
5. the current top-level `HasStyles` phase is non-dispatching and must not be silently reactivated;
6. `StyleWriter` is not the documented normal authoring API, while its exact compatibility contract remains P1.

If the focused tests fail, the failing observation must be corrected in this closeout rather than changing production code to make the characterization expectation pass.

## 9. P0 conclusion

P0 narrows STYLE-API-02B from a broad method cleanup into an API-boundary problem with evidence-backed audiences:

```text
normal application code
        ↓
structured element style options
        ↓
advanced named styles where needed
        ↓
semantic document-local requirements

custom element authors
        ↓
semantic ownership hooks
        +
retained compatibility facades

legacy / low-level callers
        ↓
explicit compatibility surface
```

The remaining design work is to make these layers intentional and understandable without pretending the historical public surface does not exist.

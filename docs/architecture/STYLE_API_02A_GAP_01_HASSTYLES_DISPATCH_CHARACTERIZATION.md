# STYLE-API-02A GAP-01 — HasStyles Dispatch Characterization

Status: **CHARACTERIZATION — NO FIX / NO API REDESIGN**  
Baseline under test: `develop` at `67507b663b55a0177b5c6e95ac6aead8e24f0134`  
Working branch: `architecture/style-api-02a-public-api-audit`

## 1. Purpose

STYLE-API-02A identified a contradiction between the documented compatibility
architecture and the current `OdtTemplate::setElement()` implementation.

The public interface lives at:

```php
OdtTemplateEngine\Contracts\HasStyles
```

Concrete structured elements import and implement that contract. However,
`OdtTemplate::registerStructuredHasStylesCompatibility()` currently performs:

```php
if ($element instanceof HasStyles) {
    $this->registerStyles($element->getStyleDefinitions());
}
```

inside namespace `OdtTemplateEngine`, without importing
`OdtTemplateEngine\Contracts\HasStyles`.

The purpose of GAP-01 is to characterize the effective behavior before
STYLE-API-02B reasons about `HasStyles` as an active extension or compatibility
contract.

This slice deliberately does not repair the namespace reference.

## 2. Source evidence

### 2.1 Public contract

`src/Contracts/HasStyles.php` defines the public interface in namespace
`OdtTemplateEngine\Contracts` and requires:

- `registerStyles(): void`;
- `getStyleDefinitions(): array`.

Its documentation still describes registration into a style manager such as
`StyleMapper`, reflecting the historical global-registration model.

### 2.2 Base structured element

`OdtElement` imports `OdtTemplateEngine\Contracts\HasStyles` and declares:

```php
abstract class OdtElement implements HasStyles
```

Therefore every concrete `OdtElement` must satisfy the contracts namespace
interface, either directly or through inherited implementations.

### 2.3 OdtTemplate dispatch site

`OdtTemplate` is in namespace `OdtTemplateEngine`. Its inspected imports include
`OdtElement`, `StyleWriter`, `StyleMapper`, and the current document services,
but not `OdtTemplateEngine\Contracts\HasStyles`.

The private compatibility method uses the unqualified name `HasStyles`.
Absent an import or a root-namespace `OdtTemplateEngine\HasStyles` declaration,
that name does not refer to the public contracts interface implemented by the
structured elements.

No root-namespace `OdtTemplateEngine\HasStyles` contract was found in the
inspected source tree.

## 3. Characterization test

The slice adds:

`tests/Integration/StyleApi02AHasStylesDispatchCharacterizationTest.php`

The test intentionally observes dispatch rather than relying only on static
namespace reasoning.

### Case A — normal structured element subclass

A test-only subclass of `Paragraph` overrides `getStyleDefinitions()` and
increments a public counter whenever that method is called.

The test:

1. confirms the paragraph is an instance of
   `OdtTemplateEngine\Contracts\HasStyles`;
2. applies bold text through the normal public Paragraph style API;
3. inserts it with `setElement()`;
4. asserts that `getStyleDefinitions()` was called zero times;
5. saves the document and verifies that the bold style is nevertheless present
   in `styles.xml`.

The fifth assertion is important. It distinguishes the missing HasStyles
compatibility dispatch from a failure of the modern semantic style path.

### Case B — external custom element

A test-only external-style `OdtElement` subclass implements the required
`registerStyles()` method and overrides `getStyleDefinitions()` with the same
counter instrumentation.

The test:

1. confirms it is an instance of the public contracts interface;
2. inserts it with `setElement()`;
3. asserts that `getStyleDefinitions()` was called zero times.

This represents the extension-author case directly rather than relying only on
built-in elements.

### Case C — namespace identity

The test also records the namespace fact explicitly:

```php
interface_exists('OdtTemplateEngine\\HasStyles') === false
interface_exists(OdtTemplateEngine\Contracts\HasStyles::class) === true
```

## 4. Expected characterization

On the current baseline, the characterization is expected to establish:

> `registerStructuredHasStylesCompatibility()` does not dispatch for elements
> implementing the public `OdtTemplateEngine\Contracts\HasStyles` contract.

The modern semantic Paragraph style path remains functional independently of
that compatibility branch.

This means the current source behavior differs from the earlier
STYLE-CONTEXT-01 final-audit description that treated the HasStyles
compatibility registration phase as active.

The contradiction must be preserved as evidence until an explicit later slice
decides whether the intended contract is:

1. restore the intended compatibility dispatch;
2. accept the current non-dispatch behavior and reclassify the branch/interface;
3. replace the role through a staged API migration.

GAP-01 does not choose among those options.

## 5. Architectural consequence for STYLE-API-02B

The key consequence is not merely a missing import. It changes what 02B may
assume about the existing extension contract.

02B must not reason from architecture documentation alone that
`HasStyles::getStyleDefinitions()` is actively consumed by the modern
`setElement()` facade.

Instead, the layers should be treated provisionally as follows:

| Surface | Current characterization consequence |
|---|---|
| `Contracts\HasStyles` type identity | public and implemented by structured elements |
| `registerStyles()` on concrete elements | observable compatibility/public method; separate call sites may remain active |
| `getStyleDefinitions()` | public/overrideable and used in element/composite/direct contexts, but not assumed to be dispatched by `OdtTemplate::setElement()` |
| `registerStructuredHasStylesCompatibility()` | intended compatibility branch whose current contracts-interface dispatch is not effective |
| semantic `getOwnStyleRequirements()` pipeline | remains the active modern style-authority path |

Therefore `HasStyles` remains compatibility-sensitive, but its exact long-term
status must be decided from actual call-site and subclass evidence rather than
from the stale assumption that `setElement()` currently invokes it.

## 6. Non-goals

This characterization does not:

- add a missing `use OdtTemplateEngine\Contracts\HasStyles;` statement;
- change `instanceof` behavior;
- alter `HasStyles` itself;
- update its stale DocBlock;
- deprecate `registerStyles()` or `getStyleDefinitions()`;
- change `StyleMapper` state;
- change semantic requirements;
- change `setElement()` lifecycle ordering;
- reinterpret earlier tests to make the discrepancy disappear.

Any fix belongs in a separately approved implementation slice after the API
contract question is understood.

## 7. Exit criterion

GAP-01 is closed for design purposes when the focused characterization test has
been executed successfully on the current branch and the result is accepted as
the baseline for STYLE-API-02B.

The resulting baseline should be expressed precisely as:

> The public `Contracts\HasStyles` interface exists and is implemented by
> structured elements, but the current private `OdtTemplate` compatibility
> check does not dispatch that interface. Modern semantic style processing is
> independently active.

That statement is evidence for 02B, not approval to repair or remove the
compatibility surface.

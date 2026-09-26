# ARCH-07H — Base-Class Resolution

## 1. Status

ARCH-07H resolves the historical `AbstractOdtTemplate → OdtTemplate`
implementation inheritance. `OdtTemplate` is now a directly instantiable,
composition-first public facade. `AbstractOdtTemplate` is removed from the
active source tree.

This is a deliberate pre-1.0 structural break. No artificial abstract
contract or compatibility shell was introduced.

## 2. Pre-change remainder inventory

Before this slice, the remaining base-class contents were:

| Responsibility | Previous owner | Final owner/action |
|---|---|---|
| namespace registration and XML namespace preparation | `AbstractOdtTemplate` | `OdtTemplate`, temporary technical facade helper |
| image-style injection and finalization | `AbstractOdtTemplate` | `OdtTemplate`, temporary implementation until future style architecture |
| bullet indentation finalization | `AbstractOdtTemplate` | `OdtTemplate`, existing finalization behavior retained |
| text-style helper | `AbstractOdtTemplate` | `OdtTemplate`, public/protected compatibility helper |
| paragraph-style helper | `AbstractOdtTemplate` | `OdtTemplate`, public compatibility helper |
| default list/paragraph styles | `AbstractOdtTemplate` | `OdtTemplate`, temporary document-preparation implementation |
| generic style registration | `AbstractOdtTemplate` | `OdtTemplate`, temporary implementation using `StyleWriter`/`StyleMapper` |
| template variable inspection | `AbstractOdtTemplate` | `OdtTemplate`, public facade API; parser remains private/protected helper |
| debug state and accessors | `AbstractOdtTemplate` | `OdtTemplate`, facade instance state |
| structured/processing callbacks | `AbstractOdtTemplate` | `OdtTemplate`, migrated in ARCH-07E |
| package/document state | historical base mirrors | `OdtPackage`/`OdtDocumentContext`; no template mirrors remain |
| `ensureTableCellStyleNodesExist()` | `AbstractOdtTemplate` | removed as unused and internally defective legacy helper |

The classification is based on repository call-site, subclass, test, and
public-sample searches. No repository code used `AbstractOdtTemplate` as a
type or extended it directly.

## 3. End-state decision

### Chosen model: complete removal

```text
OdtTemplate
├── OdtPackage
│   └── OdtDocumentContext
├── TemplateProcessor
├── StructuredElementMaterializer
├── TemplateTargetResolver
├── MetadataManager
├── PageLayoutManager
└── temporary style/finalization helpers

PageLayoutOdtTemplate extends OdtTemplate
    ├── setPageMargins()
    └── setPageLayout()
```

`AbstractOdtTemplate` did not express a meaningful abstract contract, had no
abstract methods, and had no repository-internal consumers requiring its type
identity. A thin bridge would have preserved the misleading class and would
have required either broad duplicated state/implementation or a new
compatibility abstraction.

### Rejected alternatives

- **Real abstract base:** rejected because there is no fachlich required
  subclass contract and artificial abstract methods would not improve the
  model.
- **Thin deprecated bridge:** rejected for this release slice because normal
  usage has no repository evidence of direct base subclassing or type hints,
  while the bridge would preserve the historical architecture without a
  concrete responsibility.
- **Renamed broad base:** rejected because renaming implementation inheritance
  would not reduce the mixed responsibility surface.

## 4. Type identity decision

Before ARCH-07H:

```php
$template instanceof AbstractOdtTemplate === true;
```

After ARCH-07H, `AbstractOdtTemplate` is not an active class and normal
`OdtTemplate` instances no longer have that identity. This is an intentional,
documented pre-1.0 breaking change.

Repository evidence found no production type hint, reflection assertion,
sample, or direct subclass depending on that identity. Existing compatibility
tests were updated from mirror/inheritance assumptions to authoritative
context and observable lifecycle behavior.

Consumers must migrate type hints and `instanceof` checks to `OdtTemplate`.
Direct subclasses should extend `OdtTemplate` or `PageLayoutOdtTemplate`.

## 5. Public API migration

The following previously inherited public methods are now declared directly
by `OdtTemplate` and retain their behavior:

- `ensureParagraphStylesExist()`;
- `ensureDefaultListStylesForContentXml()`;
- `extractTemplateVariables()`;
- `enableDebugMode()`;
- `getDebugLog()`;
- all existing assignment, rendering, persistence, metadata, image, and
  structured insertion methods.

No core public workflow was removed. Public method signatures and normal
template processing semantics remain compatible.

## 6. Protected API migration

The following protected helpers are now on `OdtTemplate` and retain dynamic
dispatch where existing tests or active facade paths require it:

- `setValuesInDom()`;
- `fixBrokenVariables()`;
- `replacePlaceholderWithDom()`;
- `replacePlaceholdersInNode()`;
- `replaceInText()`;
- `hasPlaceholder()`;
- `prepareNamespaces()`;
- `ensureXmlnsAttributes()`;
- `injectImageStyles()`;
- `adjustBulletIndentation()`;
- `ensureTextStylesExist()`;
- `ensureDefaultListStyles()`;
- `ensureDefaultParagraphStyles()`;
- `registerStyles()`;
- `log()`;
- `documentContext()`.

`insertAutomaticStyle()` and `loadXmlFile()` remain protected technical
helpers on the concrete facade. `ensureTableCellStyleNodesExist()` was
removed: it had no repository call site, was not part of a public method, and
used the inconsistent `$styleMap` variable instead of its `$styleNodes`
parameter. Its removal is a deliberate pre-1.0 cleanup, not a bugfix.

## 7. Style and finalization ownership

Style and finalization code now lives temporarily on `OdtTemplate` because
the public facade still must expose existing style helpers and preserve save
ordering. It continues to delegate to `StyleMapper` and `StyleWriter` where
those collaborators already exist.

This is not `STYLE-CONTEXT-01`: no `StyleContext`, document-default model,
new style API, static-state redesign, or style ownership model was introduced.
The style helper block is explicitly transitional future work.

## 8. State mirrors

The historical template properties were removed from the active facade:

- `domContent`;
- `domStyles`;
- `domMeta`;
- `templatePath`;
- `tempDir`.

`OdtTemplate` now reads document DOMs through `documentContext()` and package
paths through `OdtPackage::path()`. There is no `synchronizePackageState()`
method and no duplicate mutable DOM/path state in the template facade.

The authoritative ownership is therefore direct:

```text
OdtPackage          package/workspace/resources/persistence
OdtDocumentContext  content.xml/styles.xml/meta.xml DOM state
OdtTemplate         assignment/render-session state and orchestration
```

## 9. Debug and inspection

Debug state (`log`, `debugMode`) is now ordinary `OdtTemplate` instance state.
`enableDebugMode()`, `log()`, and `getDebugLog()` retain their prior behavior.

`extractTemplateVariables()` remains a public facade operation. Its parsing
helper remains local because ARCH-07A/B found no second consumer or stable
contract justifying a `TemplateInspector` service.

## 10. PageLayout result

`PageLayoutOdtTemplate` remains a thin subtype of `OdtTemplate` with only:

- `setPageMargins()`;
- `setPageLayout()`.

The unrelated `adjustBulletIndentation()` override was already removed in
ARCH-07G. PageLayout operations continue to delegate to `PageLayoutManager`
through `documentContext()`.

## 11. Breaking and pre-1.0 impact

Deliberate changes are limited to structural/protected compatibility:

- `AbstractOdtTemplate` no longer exists;
- `OdtTemplate instanceof AbstractOdtTemplate` is no longer available;
- direct subclasses of `AbstractOdtTemplate` must migrate;
- protected historical DOM/path properties no longer exist;
- `ensureTableCellStyleNodesExist()` is removed.

Core public construction, assignment, rendering, saving, loading, refreshing,
metadata, image, structured insertion, and PageLayout workflows remain.

## 12. Tests and validation

Existing probes that directly depended on historical mirrors were updated to
use the protected `documentContext()` boundary or observable lifecycle
behavior. No test preserves the removed base-class identity or legacy helper
existence.

Focused and full test suites cover processing, control structures, structured
insertion, images, metadata, lifecycle, finalization, styles, PageLayout, API
contracts, and public samples.

## 13. Remaining work before final review

ARCH-07I must perform the final structural review and documentation/preflight
check. The remaining architectural work is intentionally outside this
milestone's base-class resolution:

- `DOCUMENT-DEFAULTS-01`;
- `STYLE-CONTEXT-01`;
- `ASSET-CONTEXT`;
- later Style API and document-model work;
- final review of transitional style/finalization helpers.

The structural foundation now has no necessary abstract implementation base.

Semantics before implementation.

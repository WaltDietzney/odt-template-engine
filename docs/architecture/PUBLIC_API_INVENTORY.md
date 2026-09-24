# Public API Inventory

## Status and purpose

This is the discovery ledger for the TEMPLATE-AUTHORING-01F public API audit.

A method appearing here is **not** automatically a supported or recommended
1.0 end-programmer API. PHP visibility alone is insufficient evidence. The
next audit stage must verify implementation, tests, semantics, lifecycle, and
actual intended audience.

The inventory deliberately records questionable and compatibility-looking
surfaces instead of hiding them.

## Audit states

Each API may progress through:

- **DISCOVERED** - public source surface identified;
- **CHARACTERIZED** - behavior and options established from code/tests;
- **VERIFIED** - current behavior has adequate evidence;
- **CLASSIFIED** - 1.0 audience/status decided;
- **DOCUMENTED** - included appropriately in public documentation.

Problem annotations may include **QUESTION**, **LEGACY**, **DEFECT**, and
**POST-1.0**.

Final audience classification is pending and may distinguish Recommended,
Advanced, Compatibility, Extension, and Infrastructure API.

### 1.0 API closure policy

For the remainder of the 01F audit, architectural redesign is explicitly out
of scope for 1.0. The audit classifies the current surface rather than repairing
it opportunistically.

Use this disposition:

- **Recommended Public API** — document prominently for 1.0.
- **Advanced Public API** — document when needed, with its narrower lifecycle
  and preconditions.
- **Compatibility API** — expose only when existing users need it; mark the
  preferred replacement and its limitations.
- **Deprecated / poor historical API** — do not teach as normal 1.0 API. Retain
  only where backward compatibility requires it.
- **Infrastructure / public-for-technical-reasons** — omit from the normal
  end-programmer reference unless extension authors genuinely need it.
- **Missing capability requiring architecture** — do not invent a 1.0 API.
  Record it in FUTURE_DEVELOPMENT for post-1.0 design.

The objective is therefore a small, trustworthy 1.0 public story over the
existing implementation, while the audit findings become concrete input for
the next architecture milestone (for example 1.1).

## Verification methodology

The verification audit uses an evidence stack rather than treating current
source code in isolation. For every discovered public surface, inspect the
following sources before assigning a 1.0 classification:

1. **Current implementation** - establishes what the current branch actually
   executes, including validation, defaults, side effects, and lifecycle.
2. **Current tests** - establish characterized and regression-protected
   behavior. Test names alone are insufficient; relevant assertions must be
   inspected.
3. **Architecture and development documentation** - inspect change contracts,
   architecture findings, milestone documents, roadmap/future notes, and
   feature documentation for the decision that introduced or constrained the
   API.
4. **Canonical and historical usage** - inspect canonical samples first, then
   historical samples and other repository call sites to understand intended
   and compatibility usage.
5. **Public documentation** - compare README, guides, and reference-like
   documentation against the established current contract and record drift.

The evidence hierarchy is intentional. Historical documentation explains
design intent but does not override current implementation and tests. When
sources disagree, record the contradiction explicitly instead of silently
reconciling it.

For each method or coherent API group, record:

- current signature and implementation path;
- relevant tests and what they actually prove;
- originating/relevant architecture decisions and historical constraints;
- canonical and legacy call sites;
- exact behavior, defaults, accepted option keys, validation, exceptions, and
  side effects;
- lifecycle/preconditions and repeat-call behavior where relevant;
- Writer/PHP ownership implications where relevant;
- contradictions or documentation drift;
- verification confidence and missing characterization;
- proposed 1.0 audience classification only after the evidence review.

The working flow is therefore:

```text
DISCOVERED
    -> implementation inspected
    -> tests/assertions inspected
    -> architecture/history inspected
    -> canonical + historical usage inspected
    -> public docs compared
    -> CHARACTERIZED
    -> VERIFIED
    -> CLASSIFIED
    -> DOCUMENTED
```

A missing test does not automatically mean an API is broken, and a historical
sample does not automatically make an API recommended. A public PHP method may
ultimately be classified as Recommended, Advanced, Compatibility, Extension,
or Infrastructure surface.

## 1. OdtTemplate facade

Discovered public surface:

```php
__construct(string $templatePath)
load(): void
styles(): DocumentStyles
setDocumentDefaults(array $settings): void

inspect(): DocumentInspection
inspectTemplateStructure(): TemplateStructureInspection
inspectTemplate(): TemplateContract

executeDeclarative(TemplateContract $contract, array $values): void
setUserField(string $name, string $value): void
automateDependencies(TemplateContract $contract, ConcretePreflightResult $preflight): void
automateNativeObjectActions(TemplateContract $contract, ConcretePreflightResult $preflight): void
automateDocumentCapabilities(ConcretePreflightResult $preflight): void
automate(TemplateContract $contract, ConcretePreflightResult $preflight): void

bookmark(string $name): BookmarkTarget
section(string $name): SectionTarget
table(string $name): TableTarget
frame(string $name): FrameTarget

setValues(array $values): void
setElement(string $placeholder, OdtElement $element): void
setRepeating(string $key, array $rows): void
setRepeatingData(array $data): void
setMeta(array $meta): void
getMeta(): array
setImage(string $key, string $imagePath, array $options = []): void
replaceImageByName(string $name, string $imagePath, array $options = []): void

save(string $outputPath): void
refresh()
cleanup(): void
render(): void
assign(array $values): void
assignRepeating(string $key, array $rows): void

ensureParagraphStylesExist(array $styleMap): void
ensureDefaultListStylesForContentXml(DOMDocument $contentDom): void
extractTemplateVariables(): array
enableDebugMode(): void
getDebugLog(): array
```

Questions include:

- assign() versus setValues();
- assignRepeating() versus setRepeating()/setRepeatingData();
- repeated render()/save() lifecycle;
- whether ensure* methods are end-programmer, extension, or compatibility
  surface;
- which automation layers are intended as direct end-programmer API.

## OdtTemplate verification log — tranche 1

This tranche reviewed the current facade implementation together with
ARCH-07 facade closeout evidence, D5F/D5G lifecycle evidence, current package
lifecycle tests, integration usage, and Phase-F public-surface history. It
does not yet close the complete OdtTemplate audit.

### Facade and package lifecycle

**CHARACTERIZED / strong evidence**

- Construction creates an OdtPackage, prepares the loaded template, and
  registers cleanup for shutdown.
- load() is a reset operation: it resets the working package from the original
  template, resets legacy structured lifecycle state and successful Phase-E
  invocation state, and prepares the template again.
- save() finalizes current document state and writes a package. Current
  lifecycle tests prove assign -> render -> save -> cleanup -> reopen.
- cleanup() delegates package workspace cleanup. Independent OdtTemplate
  instances have isolated workspaces in current integration coverage.
- refresh() is not a generic "reload current mutations" operation. It persists
  current core documents and then calls load(); current characterization
  explicitly proves its legacy observable result is a reset to the original
  template state.
- ARCH-07 final review records construction, render, save, load, refresh, and
  repeated operations as compatibility-covered public workflows.

**Documentation risk:** refresh() is easy to misread from its name. The public
reference must document its established reset behavior rather than imply an
in-memory refresh preserving rendered values.

### Scalar assignment aliases

Current implementation shows:

```php
setValues(array $values): void
assign(array $values): void
```

Both currently perform the same operation: merge values into valueStack.
Neither method itself renders.

Repository public guidance and current samples favor assign(), while older
samples use setValues(). ARCH-07 explicitly preserves both recommended and
legacy assignment paths as public compatibility behavior.

**Status:** CHARACTERIZED implementation; classification pending final
historical/API-policy review. Working hypothesis: assign() is the recommended
surface and setValues() is a compatibility alias. Do not deprecate/remove from
this finding alone.

### Repeating assignment

```php
assignRepeating(string $key, array $rows): void
setRepeating(string $key, array $rows): void
setRepeatingData(array $data): void
```

assignRepeating() and setRepeating() both currently store rows in repeatStack
for later render(). Public guides/tests favor assignRepeating().

setRepeatingData() is materially different: it immediately normalizes and
mutates both content.xml and styles.xml by applying all supplied repeating
blocks. It does not merely stage data for render().

The source around setRepeating() contains legacy/deprecation commentary, but
the duplicated DocBlock placement is ambiguous enough that classification
must be based on broader evidence rather than that comment alone.

**Status:** assignRepeating/setRepeating CHARACTERIZED at implementation level;
setRepeatingData marked QUESTION pending historical characterization and
tests.

### render() and structured compatibility

render() consumes the staged valueStack and repeatStack and mutates both
content.xml and styles.xml. It performs placeholder repair, nl2br, list/scalar
replacement, text-box handling, repeating blocks, and conditionals.

D5G documents a historically important distinction: assigning an OdtElement
through assign()/render() is a legacy structured lifecycle, while
setElement() is the authoritative semantic structured-element lifecycle.
Compatibility work deliberately preserved the observable legacy path instead
of silently treating both as identical.

**Status:** render() is established public behavior. Exact repeat-call
semantics and the remaining legacy structured-value contract must still be
read from the later D5G characterization/closeout before final classification
text is written.

### setElement()

setElement() is explicitly identified by ARCH-07 as a genuine public
structured-content facade operation. Current implementation performs semantic
state preparation, resource preparation, structured materialization, and
bounded compatibility finalization.

D5F/D5G identify this as the authoritative structured-element lifecycle,
distinct from legacy OdtElement values passed through assign()/render().

**Status:** VERIFIED as intended public facade capability; complete option/
element-specific behavior belongs to the structured-content audit.

### Inspection views

Current facade semantics distinguish three views:

- inspect() snapshots named native structures from the **current working
  document** and does not expose mutable DOM nodes;
- inspectTemplateStructure() inspects the **original content.xml source**;
- inspectTemplate() builds a unified TemplateContract from the **original
  authored content.xml and styles.xml source**, independent of current
  working-document mutations.

This distinction is architecturally significant and must be explicit in the
public reference.

**Status:** implementation CHARACTERIZED; detailed contract/DTO verification
remains in the Inspection audit.

### Phase-D / Phase-E execution

executeDeclarative() directly executes recognized declarative Section controls
against the working document. Its own source contract states that successful
repeated execution is not generally guaranteed by Phase D.

automate() is the atomic Phase-E facade. It requires a READY
ConcretePreflightResult and permits only one successful Phase-E invocation per
document lifecycle. A successful invocation runs native-object actions,
dependency consumers, and document capabilities inside the Phase-E executor.
load() resets the successful-invocation guard.

automateDependencies(), automateNativeObjectActions(), and
automateDocumentCapabilities() are also public and are used as the component
stages of automate(). Whether these lower-level stages are intended direct
end-programmer Advanced API or exposed orchestration seams remains a
classification question.

**Status:** high-level semantics CHARACTERIZED; detailed Mapping/Automation
audit still required before CLASSIFIED.

### Public style/finalization helpers

ARCH-07 explicitly records retained public style/default helpers as part of
the migrated facade API, while also describing style registration/default
style/finalization code as transitional technical facade implementation at
that milestone.

This is important evidence against treating
ensureParagraphStylesExist()/ensureDefaultListStylesForContentXml() as
ordinary recommended user API merely because they are public. Later
STYLE-API-02 and STYLE-CONTEXT work must be inspected before their final 1.0
classification.

### Debug and variable extraction

extractTemplateVariables(), enableDebugMode(), and getDebugLog() remain public
facade methods. ARCH-07 treats template inspection/debug APIs as retained
public behavior, but this tranche has not yet established whether
extractTemplateVariables() is recommended alongside the newer TemplateContract
inspection model or is a compatibility inspection surface.

Current extractTemplateVariables() parses the current working content/styles
DOMs and returns variables, loops, conditions, negated_conditions, filters,
and filter_options.

**Status:** CHARACTERIZED implementation; classification pending inspection of
the Template Authoring inspection history and current docs.


## OdtTemplate verification log — tranche 2: historical contracts and classification

This tranche resolves several questions left open by tranche 1 by following the
accepted D5G compatibility work, STYLE-API-02 closeout, and
TEMPLATE-AUTHORING-01B/D/E architecture history.

### Classification baseline

The evidence now supports this provisional 1.0 classification for the
OdtTemplate facade itself. "Provisional" means the method's own architectural
role is established; detailed parameter/option contracts may still be audited
in their capability-specific sections.

| Surface | 1.0 classification | Evidence-based reason |
| --- | --- | --- |
| __construct() | Recommended Public API | normal facade entry point; loads/prepares immediately |
| assign() | Recommended Public API | canonical classic-template assignment in current quick start and guides |
| assignRepeating() | Recommended Public API | canonical classic foreach data assignment in current quick start |
| render() | Recommended Public API | established classic template-language execution; compatibility-sensitive lifecycle |
| save() | Recommended Public API | explicit persistence/finalization boundary |
| setElement() | Recommended Public API | authoritative semantic structured-content insertion facade |
| styles() | Recommended Public API | STYLE-API-02 canonical document-style facade |
| setDocumentDefaults() | Recommended Public API | canonical convenience over document-scoped style/default authoring; detailed options audited separately |
| setUserField() | Recommended Public API | explicit Writer User Field binding; intentionally independent from classic assign/render |
| bookmark()/section()/table()/frame() | Recommended Public API | typed current-document native target facades |
| inspect() | Recommended Public API | current working-document native inspection |
| inspectTemplate() | Recommended Public API | unified original-source semantic TemplateContract; explicitly designed for 1.0 |
| inspectTemplateStructure() | Advanced Public API | retained focused low-level original-source expression/topology inspection |
| automate() | Advanced Public API | canonical optional Phase-E atomic automation invocation |
| executeDeclarative() | Advanced Public API | direct Phase-D declarative structural execution; additive and independent of Phase-E |
| automateDependencies() | Advanced / specialized Public API | E3 specialized execution facade intentionally remains independently callable |
| automateNativeObjectActions() | Advanced / specialized Public API | E4 specialized execution facade intentionally remains independently callable |
| automateDocumentCapabilities() | Advanced / specialized Public API | E5 specialized execution facade intentionally remains independently callable |
| setValues() | Compatibility API | same staging behavior as assign(); current public guidance favors assign(); retained historical surface |
| setRepeating() | Compatibility API | same repeatStack staging role as assignRepeating(); canonical guidance favors assignRepeating() |
| setRepeatingData() | Compatibility / historical API — detailed lifecycle still to document | immediate DOM mutation differs from canonical staged repeating workflow |
| load() | Advanced lifecycle / Compatibility API | explicit reset-to-original-template boundary; constructor already loads for normal workflow |
| refresh() | Compatibility lifecycle API | characterized legacy reset behavior; name is potentially misleading |
| cleanup() | Advanced lifecycle API | explicit workspace cleanup; shutdown cleanup is also registered |
| extractTemplateVariables() | Compatibility inspection API | older current-working-DOM extraction surface overlaps conceptually with newer source TemplateContract inspection but is not silently removed |
| enableDebugMode()/getDebugLog() | Advanced diagnostic API | retained facade diagnostics, not primary authoring workflow |
| ensureParagraphStylesExist() | Infrastructure/compatibility public surface | STYLE-API-02H explicitly retains it as lifecycle/template-preparation and sample compatibility helper, not canonical style authoring |
| ensureDefaultListStylesForContentXml() | Infrastructure public surface | template preparation/finalization helper; not part of the canonical STYLE-API-02 application authoring model |

This table does not classify capability-specific methods such as setMeta(),
getMeta(), setImage(), or replaceImageByName() beyond their obvious public
facade presence; their exact contracts are handled in their dedicated audits.

### Legacy structured values through assign()/render()

D5G-B proves that the following remains an observable public compatibility
lifecycle:

```php
$template->assign(['placeholder' => $odtElement]);
$template->render();
$template->save($path);
```

It is **not** semantically equivalent to setElement(). Historically it can
materialize the same element separately against content.xml and styles.xml,
uses compatibility registration/finalization rather than the authoritative
semantic pre-materialization lifecycle, and has producer-specific differences.

The 1.0 API reference must therefore do both:

1. teach setElement() as the recommended structured-content path; and
2. document assign(OdtElement) only as retained compatibility behavior, with a
   warning not to infer setElement() semantics from it.

D5G deliberately preserved this compatibility path. It must not be silently
"fixed" during documentation.

### Style API question resolved

STYLE-API-02 provides unusually strong historical evidence and changes the
classification from the earlier first-pass uncertainty.

The current canonical style story is:

```text
normal application authoring
    -> friendly element style options

reusable generated paragraph style
    -> $template->styles()->defineParagraph(...)

custom structured extension
    -> semantic StyleRequirement / ownership hooks

serialization
    -> internal/narrow StyleWriter boundary
```

STYLE-API-02I explicitly states that StyleMapper is now a stateless
mapping/identity utility and StyleWriter is a serialization helper rather than
normal application authoring API. Historical registries and HasStyles were
retired.

Crucially, STYLE-API-02H explicitly retained
ensureParagraphStylesExist() as a lifecycle/template-preparation and sample
compatibility helper. Public visibility therefore does not make it a peer of
$template->styles()->defineParagraph().

### Inspection question resolved

TEMPLATE-AUTHORING-01B deliberately designed the three inspection APIs as
different first-class views, not accidental aliases:

```text
inspect()
    current/live Working Document

inspectTemplateStructure()
    focused original-source classic-expression topology

inspectTemplate()
    unified original-source semantic TemplateContract
```

inspectTemplate() is the primary integration contract for generic
applications. TemplateContract::toArray() is also a versioned tooling contract;
its machine-readable keys/codes have compatibility implications independent of
the Composer package version.

extractTemplateVariables() predates this model and inspects the current working
content/styles DOMs with a narrower regex-based projection. No reviewed
architecture document promotes it as the modern replacement for
inspectTemplate(). It is therefore classified as compatibility inspection,
not removed.

### Phase D and Phase E question resolved

Phase D explicitly defines declarative execution as an additional capability,
not a replacement for imperative APIs.

Phase E explicitly defines automation as optional. Normal library usage must
not require a mandatory global automation lifecycle; imperative operations may
occur before and after automation, and automation neither renders nor saves.

The current common advanced facade is:

```php
$template->automate($contract, $preflight);
```

Its accepted E6 semantics are:

- consumes an already-inspected TemplateContract and READY concrete preflight;
- does not reinspect or remap application data;
- executes E4 native actions -> E3 dependency automation -> E5 document
  capabilities under one rollback boundary;
- permits one successful common invocation per load lifecycle;
- failed invocation with successful rollback does not consume that lifecycle;
- load() resets the success marker;
- does not call render(), save(), refresh(), finalization, or close;
- later imperative mutation and explicit save remain supported.

The specialized E3/E4/E5 facade methods intentionally remain independently
callable. They are therefore Advanced/specialized public API, not merely
private implementation leakage. The common automate() method is the preferred
high-level Phase-E entry point when invocation-wide atomicity is desired.

### Important image-semantics separation discovered in Phase E

The Phase-E Frame image-replacement path must not be documented as equivalent
to legacy replaceImageByName().

E4 deliberately established different semantics:

- no dimensional options: preserve both existing dimensions;
- one dimension: derive the other from intrinsic image ratio where geometry is
  determinable;
- two dimensions: apply both explicitly;
- no legacy 5cm x 3cm default.

The imperative replaceImageByName() compatibility behavior remains unchanged.
This is a concrete example of why the final API reference must document methods
by actual execution path rather than merging similarly named image operations
into one conceptual contract.

### Remaining OdtTemplate questions after tranche 2

The facade-level architecture is now largely classifiable. The remaining
questions are primarily exact behavior contracts:

1. setRepeatingData(): historical intended audience, exact failure/repeat
   semantics, and whether current tests still deliberately preserve it.
2. render(): exact repeated-render behavior after the later D5G narrowing and
   compatibility closeout, especially structured legacy values.
3. load()/refresh()/cleanup(): exact exception/return contracts and whether
   refresh() has any current recommended use case.
4. debug APIs: what messages are actually emitted and whether debug state is
   reset across load()/refresh().
5. extractTemplateVariables(): exact parser limits versus TemplateContract
   inspection.
6. ensureDefaultListStylesForContentXml(): direct caller evidence and final
   public-audience classification.
7. capability-specific facade methods: metadata, image replacement, and their
   complete option/failure contracts.

These questions should be answered from current tests and final closeout
documents before the OdtTemplate section is marked VERIFIED.


## OdtTemplate verification log — tranche 3: behavior forensics

This tranche checks the remaining facade-lifecycle questions against direct
call-site evidence and focused characterization tests. Where no direct test or
call site exists, the absence is recorded rather than filled by inference.

### Repeating APIs: one deprecated alias and one orphaned direct mutator

Current source contains an explicit deprecation annotation on setRepeating():

```php
/** @deprecated Use assignRepeating() and render() instead. */
public function setRepeating(string $key, array $rows): void
```

Its implementation only stages rows in repeatStack, exactly like
assignRepeating(). The canonical documentation and quick start use
assignRepeating().

**Classification strengthened:** setRepeating() is a deprecated Compatibility
alias; assignRepeating() is the Recommended API.

setRepeatingData() is different. Repository search currently finds no caller
outside its own definition and no focused test. It:

1. repairs broken variables in current content.xml and styles.xml;
2. immediately calls applyAllRepeatingBlocksInDom() on both DOMs;
3. does not populate repeatStack;
4. does not require render().

The direct implementation is an older sibling-walking foreach algorithm. It
finds paragraph markers with XPath, searches a following sibling end marker,
removes the marker pair and template nodes, clones the captured nodes per row,
and replaces row placeholders immediately. If no matching end marker is found,
the loop simply breaks rather than reporting a deterministic malformed-template
error.

No reviewed current architecture document promotes this path and no repository
call site demonstrates a current intended audience.

**Status:** CHARACTERIZED from implementation but **not VERIFIED as a supported
behavioral contract** because it lacks current characterization tests. For 1.0
it is a strong legacy/deprecation candidate, but removal or semantic cleanup
requires an explicit compatibility decision.

### load() and refresh(): reset semantics are directly protected

OdtTemplatePackageLifecycleTest proves that load() discards unsaved working
mutation and restores the original template source.

The same test explicitly names and protects
testRefreshKeepsItsLegacyResetBehavior(): after assign + render + refresh, the
rendered value is absent and the original placeholder is present again.

D5G legacy lifecycle characterization independently proves the same reset for a
structured assigned Paragraph and confirms that refresh resets the
legacyStructuredValuesMaterialized flag.

Implementation explains why:

```text
refresh()
    -> graphic/font finalization
    -> persistCoreDocuments()
    -> load()
         -> OdtPackage::resetFromTemplate()
         -> prepareLoadedTemplate()
```

Therefore persistCoreDocuments() inside refresh() must not be interpreted as
"make current mutations the new loaded state"; the subsequent load boundary
resets from the package's original template source.

**VERIFIED contract:** load() and refresh() both return the working document to
the original template state. refresh() additionally performs its historical
pre-reset persistence/finalization sequence. The public reference must warn
that refresh() is not a preserve-current-state reload operation.

### cleanup(): what is and is not proved

Current implementation delegates directly to OdtPackage::cleanup().
Package-lifecycle integration coverage proves two template instances have
independent workspaces and can be cleaned independently. Normal tests also call
cleanup() after save/reopen workflows.

The constructor registers cleanup() as a shutdown function, so explicit cleanup
is not required solely to obtain shutdown cleanup.

This tranche found no evidence establishing that arbitrary authoring calls
after explicit cleanup() are supported. The public reference should therefore
describe cleanup() as releasing the temporary package workspace and should not
promise object reuse after cleanup without additional characterization.

### Repeated render(): scalar versus structured evidence

For the legacy structured lifecycle, D5G characterization is strong. Current
tests explicitly execute:

```text
assign(OdtElement)
render()
save(A)
render()
save(B)
```

for representative structured producers. After D5G-C narrowing, the focused
tests require stable content.xml and styles.xml for the characterized producer
set. ImageElement additionally has explicit repeated-state assertions. This is
compatibility protection, not evidence that assign(OdtElement) is the
recommended structured lifecycle.

For ordinary scalar/classic render(), this tranche found the normal
assign -> render -> save coverage and extensive public sample use, but no
equally explicit general contract saying that calling render() repeatedly with
new scalar assignments reconstructs consumed placeholders. Since render()
mutates the working DOM, a placeholder removed by the first render is not
implicitly restored by a second render. load() is the explicit reset boundary.

**Documentation rule:** do not advertise render() as a re-render-from-source
operation. The safe canonical lifecycle is stage assignments/repeaters, call
render(), then save. Re-render guarantees should only be stated for scenarios
covered by characterization.

### extractTemplateVariables(): exact current limits

Current implementation scans the **current working content.xml and styles.xml**
serialization, not the immutable original source contract.

It returns exactly these top-level keys:

- variables
- loops
- conditions
- negated_conditions
- filters
- filter_options

Its parser uses regular expressions with word-character restrictions for
ordinary variable/filter names and foreach/ifnot names. It separately captures
if/elseif expression text. It is therefore a lexical convenience extractor,
not a substitute for TemplateContract scope, provenance, native-object,
capability, coverage, or diagnostic semantics.

The Sample Explorer currently calls this API to display variables from sample
templates. That is real repository usage, but it is tooling usage rather than
evidence that this should be the primary modern application inspection model.

**Status:** VERIFIED as a narrow compatibility/tooling API. Prefer
inspectTemplate() for semantic integration work.

### Debug API: exposed but weakly evidenced

enableDebugMode() only sets the debugMode flag to true. getDebugLog() returns
the accumulated log array. The protected log() helper appends only while debug
mode is enabled.

Repository search finds a historical test_fonts.php sample enabling debug mode,
but no current focused test establishing a stable set of emitted messages, no
disable method, and no evidence in this tranche of a documented reset contract
across load()/refresh().

**Status:** public diagnostic compatibility surface, but **message contents and
lifecycle are not a stable documented contract on current evidence**. Do not
publish individual debug strings as machine-readable API.

### Behavioral-forensics decision

The facade lifecycle can now be documented conservatively without pretending
that all public methods have equal maturity:

- canonical classic lifecycle: assign/assignRepeating -> render -> save;
- canonical structured insertion: setElement -> save (render only when classic
  staged work is also present);
- load is an explicit reset to original template;
- refresh is a legacy reset operation with pre-reset finalization/persistence,
  not a normal refresh abstraction;
- repeated legacy structured render has focused compatibility tests;
- generic "rerender from source" semantics do not exist;
- setRepeating is explicitly deprecated;
- setRepeatingData is currently an untested/orphaned historical direct mutator;
- extractTemplateVariables is useful compatibility/tooling inspection but
  inspectTemplate is the semantic integration API;
- debug facilities are best treated as diagnostic compatibility surface until
  stronger contracts exist.

The remaining OdtTemplate facade work is now capability-specific rather than
generic lifecycle forensics: metadata, images, document defaults/style options,
and exact exception/validation behavior. Those should be verified in their own
API-family audits.


## 2. Native Writer targets

### BookmarkTarget

```php
type(): string
descriptor(): BookmarkDescriptor
replaceText(string $value): self
```

### SectionTarget

```php
type(): string
descriptor(): SectionDescriptor
text(): string
nestedNamedObjects(): array
replaceContent(OdtElement $content): self
clone(): self
instantiate(array $values): self
instantiateMany(array $items): array
section(string $name): self
```

Known architectural constraint: clone identity rewriting does not provide
instance-relative logical Bookmark addressing. That gap is post-1.0
SECTION-INSTANCE-NATIVE-ADDRESSING-01.

### TableTarget

```php
type(): string
descriptor(): TableDescriptor
populate(array $rows, array $options = []): self
```

Known option contract:

```php
['keepRows' => [0, 4]]
```

keepRows addresses zero-based ordinary source rows before mutation. Unknown
option keys and invalid indices are rejected. Full scalar-carrier and topology
rules remain to be recorded during verification.

### FrameTarget

```php
type(): string
descriptor(): FrameDescriptor
```

Currently discovered as read-only target surface.

## 3. Structured content

### Paragraph

```php
__construct(?string $paragraphStyle = null, array $paragraphStyleOptions = [])
addText(string $text, array $style = []): self
applyTextStyle(array $style): self
addLineBreak(int $count = 1): self
addTab(): self
addTabStop(float $position, string $alignment = 'left', ?string $text = null, array $style = []): self
addTabularLines(array $lines, array $tabDefs, array $headerStyle = []): self
addPHyperLink(string $text, string $href, array $style = []): self
addElement(OdtElement $element): self
setParagraphStyle(string $styleName): self
setParagraphStyleOptions(array $options): self
addTabStopDefinition(float $position, string $alignment = 'left'): self
addKeyValueLine(string $key, string $value, float $tabPosition = 10.0, array $style = null): self
addTabsWithTexts(array $tabs): self
addHyperlink(string $text, string $href, array $style = []): self
setBulleted(): self
setNumbered(): self
isList(): bool
```

QUESTION: addPHyperLink() versus addHyperlink() requires characterization.

### RichText

```php
addParagraph(string|Paragraph $text = '', ?string $styleName = null, array $styleOptions = []): self
addTable(RichTable $table): self
addImage(ImageElement $image): self
addParagraphBreak(int $count = 1): self
addMultiParagraph(array $lines, ?array $style = null, bool $firstBold = false): self
addText(string $text, array $style = []): self
addLineBreak(): self
addTab(): self
addBulletList(array $items, array $style = []): self
addNumberedList(array $items, array $style = []): self
addElement(OdtElement $element): self
applyParagraphStyleOptions(array $options): self
applyTextStyle(array $style): self
popLastElementIfList(): ?ListElement
```

Resource/style discovery methods also exist publicly and require audience
classification.

### ListElement

```php
__construct(string $type = 'bullet', string $styleName = null)
addItem(Paragraph|self $item): self
setLevel(int $level): self
addSubList(ListElement $list): self
```

Programmatic list construction is distinct from future
LIST-ITEM-POPULATION-01, which concerns population of an existing
Writer-authored list collection.

### RichTable

```php
__construct()
addRow(array $cells, array $style = []): self
setHeaderRowCount(int $count): self
setTableStyleName(string $styleName): self
setStyle(array $style): self
setTableStyle(array $options): self
setTableWidth(string $width): self
setTableRelativeWidth(string $width): self
setTableAlignment(string $alignment): self
setTableName(string $name): self
buildTableFromArray(array $tableData, string $styleName = 'default'): self
addCustomStyle(string $name, array $styleDefinition): self
getCustomStyle(string $name): ?array
setSummaryKeywords(array $keywords): self
setColumnWidths(array $widths): void
getColumnWidths(): array
getTableStyleName(): ?string
setColumnWidthRatios(array $ratios): void
```

Known current friendly table-style rules include absolute width versus
relative-width exclusivity and alignment values left, center, right, margins.
Row style recognizes row-height and min-row-height, which are mutually
exclusive. Full contract remains to be verified.

QUESTION: older convenience/style APIs versus the newer semantic table layout
surface require classification.

### RichTableCell

```php
__construct(string|Paragraph|RichText $content, array $style = [])
setContent(mixed $content): self
getContent(): mixed
setStyle(array $style): self
setColspan(int $colspan): self
setRowspan(int $rowspan): self
forceParagraphAlignment(bool $force = true): self
alignCenter(): self
alignLeft(): self
alignRight(): self
setBackground(string $color): self
setBorder(string $border): self
setBorderTop(string $border): self
setBorderBottom(string $border): self
setBorderLeft(string $border): self
setBorderRight(string $border): self
setPadding(string $padding): self
setPaddingTop(string $padding): self
setPaddingBottom(string $padding): self
setPaddingLeft(string $padding): self
setPaddingRight(string $padding): self
setWidth(string $width): self
registerStylesAndRefresh(): self
create(string|Paragraph|RichText $content, array $style = []): self
style(string $styleNameOrDefinition): self
colspan(int $count): self
rowspan(int $count): self
```

QUESTION: duplicate fluent/convenience forms and registerStylesAndRefresh()
need classification.

## API-family verification — Metadata and Writer User Fields

This audit combines current implementation, focused integration tests, accepted
architecture contracts/completion records, canonical samples, and current
public documentation.

### Metadata — VERIFIED

The public imperative facade is intentionally bounded:

```php
$template->setMeta(array $meta): void;
$template->getMeta(): array;
```

MetadataManager is the sole document-local mutation owner. This is not an
arbitrary meta.xml or Dublin Core authoring API.

#### Supported imperative metadata keys

| Canonical key | ODF carrier | Imperative setMeta behavior |
| --- | --- | --- |
| title | dc:title | singular value cast to string |
| subject | dc:subject | singular value cast to string |
| description | dc:description | singular value cast to string |
| coverage | dc:coverage | singular value cast to string; bounded extended metadata |
| keywords | repeated meta:keyword | string = one keyword; list<string> = complete replacement collection |
| initial_creator | meta:initial-creator | singular value cast to string |
| creator | dc:creator | singular value cast to string |
| language | dc:language | singular value cast to string |
| creation_date | meta:creation-date | singular value cast to string |
| date | dc:date | singular value cast to string |
| editing_cycles | meta:editing-cycles | singular value cast to string |
| editing_duration | meta:editing-duration | singular value cast to string |
| generator | meta:generator | singular value cast to string |

Imperative compatibility aliases:

- author -> creator
- initial_author -> initial_creator

Unknown imperative keys are silently ignored by design for compatibility.

getMeta() returns only supported fields that are present. creator and
initial_creator additionally expose the compatibility read aliases author and
initial_author with the same values. keywords is always returned as a list of
all present meta:keyword values.

For keywords:

- string input is accepted as exactly one keyword and is never delimiter-split;
- list input must be a PHP list and every item must be a string;
- invalid keyword input throws InvalidArgumentException;
- an empty list removes all existing keyword elements;
- setting keywords replaces the complete keyword collection while preserving
  unrelated metadata.

For all non-keyword imperative fields, MetadataManager currently casts the
supplied value to string. The stricter semantic types documented for metadata
belong to Phase-E concrete preflight, not to imperative setMeta() validation.

#### Phase-E metadata semantics are intentionally stricter

The accepted canonical semantic model is:

- STRING: title, subject, description, creator, initial_creator, generator,
  coverage;
- LIST<STRING>: keywords;
- DATETIME: creation_date, date;
- LANGUAGE: language;
- NON_NEGATIVE_INTEGER: editing_cycles;
- DURATION: editing_duration.

MetadataPayloadValidator enforces these bounded forms during concrete Phase-E
preflight. This does not retroactively tighten the compatibility-sensitive
imperative setMeta() facade.

#### Lifecycle and ownership

setMeta() mutates the current document-local meta.xml DOM. It does not insert
visible document content and save() does not automatically invent/update
metadata. save/reopen behavior is covered by integration tests.

METADATA-SEMANTICS-01 records focused XML verification, full preflight,
LibreOffice headless round-trip, and manual LibreOffice save/close/reopen as
GREEN. LibreOffice may update the generator field during its own round trip;
that is editor behavior, not an engine auto-update contract.

**1.0 classification:** setMeta() and getMeta() are Recommended Public API.
The canonical names are creator/initial_creator; author/initial_author are
Compatibility aliases.

### Writer User Fields — VERIFIED

The recommended imperative mutation API is:

```php
$template->setUserField(string $name, string $value): void;
```

The operation targets native Writer User Fields, not classic template
placeholders. assign(), setValues(), and render() deliberately do not bind a
same-named User Field.

#### Supported v1 field model

Phase-C v1 supports only Writer User Fields whose authoritative declarations
use:

```xml
office:value-type="string"
```

Set/Get Variable and non-string User Fields are outside the supported binding
surface.

Logical User Field identity is ROOT + field name. A User Field reference
physically located inside a native foreach Section remains ROOT-scoped.

The binder analyzes bounded working-document regions:

- BODY in content.xml;
- header/footer content beneath master pages in styles.xml.

For a supported logical field, all matching authoritative declarations across
those bounded regions are updated atomically by changing
office:string-value.

text:user-field-get display text is intentionally **not** rewritten. Writer /
LibreOffice reevaluation is responsible for refreshing the displayed native
field value.

#### Failure contract

setUserField() validates the complete logical field before the first mutation
and throws the public UserFieldBindingException on failure.

Stable reason codes:

| Reason | Meaning |
| --- | --- |
| MALFORMED | empty name, orphan/malformed logical field, or no authoritative declaration |
| NOT_FOUND | no field with the requested name |
| UNSUPPORTED_TYPE | declaration is outside the supported string User Field type |
| AMBIGUOUS | conflicting/ambiguous declarations, values, or types prevent safe binding |

The exception exposes:

```php
$exception->fieldName(): string;
$exception->reason(): string;
```

Focused tests prove no mutation on the characterized failure paths.

#### Lifecycle and inspection interaction

The established lifecycle is:

```text
inspectTemplate()
    original authored source contract

setUserField()
    current working content.xml/styles.xml declarations

save()
    persists declaration mutation

load()
    resets to the original template source

new OdtTemplate(saved-output.odt)
    saved output becomes that instance's original source
```

Repeated binding is supported. Binding coexists with classic assign/render.
inspectTemplate() on the same instance remains source-oriented and therefore
does not change after setUserField() mutates the working document.

LibreOffice-authored fixtures verify BODY/header cross-part binding and native
field behavior. The canonical public learning sample is L10 Writer User Fields.

**1.0 classification:** setUserField() is Recommended Public API.
UserFieldBindingException and its reason accessors/constants are part of the
public failure contract. UserFieldAnalyzer and UserFieldBinder are internal
services; their public PHP visibility, where present, does not make them
end-programmer APIs.

### Important distinction for the final public reference

Metadata and User Fields both store document-level/native information, but
their user semantics must not be conflated:

```text
setMeta()
    -> meta.xml document properties
    -> generally not visible body content

setUserField()
    -> Writer native field declaration
    -> reusable Writer-authored field references
    -> display refresh delegated to Writer/LibreOffice

assign()/render()
    -> classic visible template expressions
    -> independent of native User Fields
```

This distinction is already supported by architecture contracts and focused
tests and should be taught explicitly in the 1.0 documentation.


## 4. Images, frames, and drawing layout

### ImageElement

```php
__construct(string $imagePath, array $options = [])
setFrameLayout(array $layout): self
setFrameAnchor(string $anchor): self
setFrameHorizontalAlignment(string $alignment, ?string $relativeTo = null): self
setFrameVerticalAlignment(string $alignment, ?string $relativeTo = null): self
setFrameHorizontalOffset(string $offset, ?string $relativeTo = null): self
setFrameVerticalOffset(string $offset, ?string $relativeTo = null): self
setFrameWrap(string $wrap): self
getImagePath(): string
getImageOptions(): array
setStyle(array $options): self
```

ImageElement derives a missing width/height from source aspect ratio and uses
5cm x 3cm when neither is supplied. Full style/layout option contract remains
to be characterized.

### CircularImageElement

```php
__construct(string $imagePath, array $options = [])
```

Additional public resource/style materialization methods exist and require
audience classification.

### DrawTextBox

```php
__construct(string $name, array $options = [])
addElement(OdtElement $element): self
setFrameLayout(array $layout): self
setFrameAnchor(string $anchor): self
setFrameHorizontalAlignment(string $alignment, ?string $relativeTo = null): self
setFrameVerticalAlignment(string $alignment, ?string $relativeTo = null): self
setFrameHorizontalOffset(string $offset, ?string $relativeTo = null): self
setFrameVerticalOffset(string $offset, ?string $relativeTo = null): self
setFrameWrap(string $wrap): self
setBackground(string $color): self
setFill(string $fill): self
setFillColor(string $color): self
setAllowOverlap(bool $allow = true): self
setVerticalPos(string $pos, string $rel = 'baseline'): self
setHorizontalPos(string $pos, string $rel = 'char'): self
setHorizontalPosition(string $pos, string $rel = 'page'): self
setVerticalPosition(string $pos, string $rel = 'page'): self
flowWithText(bool $enable = true): self
```

QUESTION: older position helpers versus the semantic frame-layout API require
characterization and classification.

### DrawingLayout

```php
empty(): self
fromArray(array $layout): self
anchor(): ?string
width(): ?string
height(): ?string
horizontalMode(): ?string
horizontalAlignment(): ?string
horizontalRelation(): ?string
horizontalOffset(): ?string
verticalMode(): ?string
verticalAlignment(): ?string
verticalRelation(): ?string
verticalOffset(): ?string
wrap(): ?string
withAnchor(string $anchor): self
withHorizontalAlignment(string $alignment, ?string $relativeTo = null): self
withHorizontalOffset(string $offset, ?string $relativeTo = null): self
withVerticalAlignment(string $alignment, ?string $relativeTo = null): self
withVerticalOffset(string $offset, ?string $relativeTo = null): self
withWrap(string $wrap): self
toArray(): array
```

## 5. Styles and page layout

### DocumentStyles

Obtained through OdtTemplate::styles().

```php
defineParagraph(string $name, array $options): void
setDocumentDefaults(array $settings): void
```

OdtTemplate also exposes setDocumentDefaults(). The known top-level defaults
shape is text and paragraph option groups. Complete StyleMapper-backed option
contracts remain to be inventoried.

### PageLayoutOdtTemplate

```php
setPageMargins(string $top, string $right, string $bottom, string $left, string $masterPage = 'Standard'): static
setPageLayout(array $options, string $masterPage = 'Standard'): static
```

Currently documented layout keys include margin-top, margin-right,
margin-bottom, margin-left, page-width, and page-height. Verification must
establish the complete accepted contract and failure behavior.

## 6. HTML import

### HtmlImporter

```php
fromHtml(string $html, array $options = []): RichText
```

Known import option:

```php
['allow_remote_images' => false]
```

Remote images are disabled by default. Supported HTML/CSS conversion behavior
must be characterized from implementation/tests rather than inferred from HTML
generally.

parseStyleAttribute() is also public and requires audience classification.

## 7. Document inspection

### DocumentInspection

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

Descriptor public data includes:

- BookmarkDescriptor: name, documentPart, start/end presence, topology, text,
  diagnostics;
- SectionDescriptor: name, documentPart, child summary, nested named objects,
  diagnostics;
- TableDescriptor: name, documentPart, row/column counts, containing Section,
  diagnostics;
- FrameDescriptor: name, documentPart, payload type, width/height, containing
  Section, diagnostics.

## 8. Template contract inspection

### TemplateContract

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

Public descriptor/value objects discovered include BindingDescriptor,
ControlDescriptor, DependencyDescriptor, NativeObjectDescriptor,
DataScopeDescriptor, SourceProvenance, coverage/capability/diagnostic objects,
and structure-inspection objects.

Their public methods must be inventoried fully during the verification pass,
but their mere public visibility does not yet establish them as direct
end-programmer construction APIs.

## 9. Mapping, preflight, and automation

Discovered public families include:

- MappingDefinition;
- ApplicationPath and ApplicationPathSegment;
- ApplicationDataResolver/Resolution;
- DependencyMapping and resolution/projection objects;
- NativeObjectActionMapping and resolution/projection objects;
- DocumentCapabilityMapping and resolution/projection objects;
- StaticMappingValidator;
- ConcreteMappingPreflight;
- ConcretePreflightResult, operation, and diagnostics;
- EngineCapabilityCatalog;
- mapping capability/value objects.

The high-level execution surface on OdtTemplate includes executeDeclarative()
and automate(), plus lower-level automation stages.

This area requires a separate detailed verification pass because public DTOs,
validators, projectors, capability catalogs, and execution services may belong
to different audiences.

## 10. Metadata, debugging, and template discovery

Discovered OdtTemplate surface:

```php
setMeta(array $meta): void
getMeta(): array
extractTemplateVariables(): array
enableDebugMode(): void
getDebugLog(): array
```

The metadata key contract still needs extraction from MetadataManager.

extractTemplateVariables() currently exposes categories including variables,
loops, conditions, negated_conditions, filters, and filter_options. Exact
contract and compatibility status remain to be verified.

## 11. Infrastructure-looking public methods

OdtElement and several concrete elements expose public methods used for
materialization, ownership traversal, style/resource discovery, DOM creation,
and insertion mode selection. Examples include:

```php
toDomNode(...)
toStyleDomNode(...)
ownedElements()
getOwnStyleRequirements()
getOwnFrameStyleRequirements()
getOwnImageStyleRequirements()
getOwnFillImageRequirements()
getImageAssets()
structuredInsertionMode()
```

These are intentionally retained in the inventory. The audit must determine
whether they are Extension API or internal infrastructure exposed by PHP
visibility. They must not be advertised as normal end-programmer API without
that decision.

## 12. Known questions for verification

The verification audit must explicitly answer at least:

1. Which OdtTemplate binding methods are canonical versus compatibility?
2. What are render(), save(), refresh(), and cleanup() lifecycle contracts?
3. Which public ensure*/DOM/materialization methods are extension surface?
4. addPHyperLink() versus addHyperlink(): alias, historical duplicate, or
   semantic difference?
5. Which RichTable and RichTableCell convenience/style APIs remain supported?
6. Which DrawTextBox position APIs are current versus legacy?
7. What are every accepted StyleMapper option key and its exact semantics?
8. What are complete ImageElement, DrawTextBox, DrawingLayout, and image
   replacement option contracts?
9. What metadata keys are supported?
10. What page-layout options and master-page failure semantics apply?
11. What HTML elements/CSS/options are intentionally supported?
12. Which inspection/contract DTOs are intended for direct user consumption?
13. Which Mapping/Preflight classes form the recommended automation API?
14. Which public APIs are currently untested or only historically tested?
15. Are any public methods broken, obsolete, misleadingly documented, or
    inconsistent with current architecture?

## Next step

Do not turn this inventory directly into website reference material.

First perform the Public API Verification Audit using the evidence stack defined above. Architecture/history and repository usage are mandatory evidence sources, not optional background reading.

The resulting classification is the basis for the 1.0 end-programmer API
reference.


## API-family verification — DrawTextBox / generated frames

Status: **VERIFIED + DOCUMENTED-COMPLETE for current DrawTextBox source**.

This family is the generated/programmatic text-box producer. It must not be
confused with `OdtTemplate::frame($name)`, which addresses an existing
Writer-authored frame.

### Recommended semantic layout API

```php
$box = (new DrawTextBox('Sidebar'))
    ->addElement($content)
    ->setFrameLayout([
        'anchor' => 'paragraph',
        'width' => '6cm',
        'height' => '4cm',
        'horizontal' => [
            'alignment' => 'right',
            'relative-to' => 'page-content',
        ],
        'vertical' => [
            'alignment' => 'top',
            'relative-to' => 'paragraph',
        ],
        'wrap' => 'parallel',
    ]);
```

The complete friendly `setFrameLayout()` top-level vocabulary is exactly:

| key | accepted values / structure |
|---|---|
| `anchor` | `paragraph|char|as-char|page` |
| `width` | positive absolute length, units `cm|mm|in|pt|pc` |
| `height` | positive absolute length, same units |
| `horizontal` | exactly one of `alignment` or `offset`, plus optional `relative-to` |
| `vertical` | exactly one of `alignment` or `offset`, plus optional `relative-to` |
| `wrap` | `none|left|right|parallel|dynamic|run-through` |

Unknown keys throw InvalidArgumentException. Percent sizes are rejected.
Offsets accept signed absolute lengths in `cm|mm|in|pt|pc`; percentages are
not offsets in the semantic API.

Horizontal alignments: `left|center|right`.
Vertical alignments: `top|middle|bottom`.

Relation matrix:

| anchor | horizontal relative-to | vertical relative-to |
|---|---|---|
| paragraph | paragraph, paragraph-content, page, page-content | paragraph, paragraph-content, page, page-content |
| char | char, paragraph, paragraph-content, page, page-content | char, paragraph, paragraph-content, page, page-content, baseline |
| page | page, page-content | page, page-content |
| as-char | no horizontal placement | baseline |

When no relation is supplied by a convenience method, page anchor defaults to
`page`; paragraph/char default to `paragraph`; as-char vertical defaults to
`baseline`. As-char rejects horizontal friendly placement and vertical
offset. Alignment and offset are mutually exclusive per axis; setting one later
replaces the previous mode on that axis. Changing anchor revalidates retained
axis state and can therefore throw.

`setFrameLayout([])` deliberately clears semantic layout state and returns
rendering to legacy constructor-option behavior.

Semantic convenience methods, all fluent:

```php
setFrameAnchor(string $anchor): self
setFrameHorizontalAlignment(string $alignment, ?string $relativeTo = null): self
setFrameVerticalAlignment(string $alignment, ?string $relativeTo = null): self
setFrameHorizontalOffset(string $offset, ?string $relativeTo = null): self
setFrameVerticalOffset(string $offset, ?string $relativeTo = null): self
setFrameWrap(string $wrap): self
```

They share one immutable DrawingLayout state and the same validation above.

### Constructor and legacy frame options

```php
new DrawTextBox(string $name, array $options = [])
```

The name becomes `draw:name`. Without semantic DrawingLayout, anchor defaults
to `paragraph`.

The constructor/options path is older and deliberately permissive. The complete
friendly keys interpreted by `StyleMapper::mapFrameStyleOptions()` are:

| friendly key | native result / behavior |
|---|---|
| `background-color` | `fo:background-color`; also defaults `draw:fill=solid` and `draw:fill-color` to same value unless already mapped |
| `border`, `border-top/right/bottom/left` | corresponding `fo:border*` |
| `corner-radius-x` or `rx` | `svg:rx` |
| `corner-radius-y` or `ry` | `svg:ry` |
| `padding`, `padding-top/right/bottom/left` | corresponding `fo:padding*` |
| `fill` | `draw:fill` |
| `fill-color` | `draw:fill-color` |
| `wrap-influence` | `draw:wrap-influence-on-position` |
| `allow-overlap` | `loext:allow-overlap` |
| `vertical-pos`, `vertical-rel` | corresponding `style:*` position properties |
| `horizontal-pos`, `horizontal-rel` | corresponding `style:*` position properties |
| `width`, `height` | used directly as `svg:width/svg:height` object attributes by DrawTextBox |
| `anchor` | used directly as `text:anchor-type`; default paragraph |
| any other key | mapper passes it through verbatim; this is compatibility behavior, not a validated semantic option |

No general validation is performed on legacy option values. In particular the
historical position path can preserve values such as `50%`, whereas the
semantic DrawingLayout rejects percentage pseudo-positioning.

When semantic layout is active, semantic anchor/width/height/axis placement
wins over conflicting legacy geometry. Legacy non-layout graphic policy/style
properties can coexist with semantic layout.

### DrawTextBox public method reference

| Method | Contract | Disposition |
|---|---|---|
| `__construct(string $name, array $options = [])` | Creates named generated draw:frame/draw:text-box; legacy options above. | Recommended constructor; options path Compatibility where semantic replacement exists |
| `addElement(OdtElement $element): self` | Appends child in order inside draw:text-box. Overrides base storage with DrawTextBox-owned paragraph/element list. | Recommended |
| `setFrameLayout(array $layout): self` | Complete semantic layout master API above; empty array resets to legacy layout. | Recommended |
| six `setFrame*()` semantic convenience methods | Incrementally mutate shared semantic layout with same validation. | Recommended |
| `setBackground(string $color): self` | Sets legacy `background-color`; mapper also derives solid fill + fill-color. No color validation. | Compatibility convenience; useful but semantically overlapping fill API |
| `setFill(string $fill): self` | Sets draw:fill verbatim; no enum validation. | Advanced/Compatibility |
| `setFillColor(string $color): self` | Sets draw:fill-color verbatim. | Advanced/Compatibility |
| `setAllowOverlap(bool $allow = true): self` | Emits `loext:allow-overlap` string true/false. | Advanced/Compatibility policy |
| `flowWithText(bool $enable = true): self` | Emits `style:flow-with-text` string true/false. | Advanced/Compatibility policy |
| `setVerticalPos(string $pos, string $rel = 'baseline'): self` | Legacy pass-through position pair; no validation. | Compatibility / deprecation candidate |
| `setHorizontalPos(string $pos, string $rel = 'char'): self` | Legacy pass-through position pair; no validation. | Compatibility / deprecation candidate |
| `setHorizontalPosition(string $pos, string $rel = 'page'): self` | Alias-like wrapper around setHorizontalPos but with **different default relation** (`page` vs `char`). | Compatibility / deprecation candidate |
| `setVerticalPosition(string $pos, string $rel = 'page'): self` | Alias-like wrapper around setVerticalPos but with **different default relation** (`page` vs `baseline`). | Compatibility / deprecation candidate |
| `ownedElements(): iterable` | Returns DrawTextBox child list. | Extension/infrastructure |
| `getOwnStyleRequirements(): iterable` | Emits semantic graphic style requirement when effective semantic graphic properties exist. | Extension/infrastructure |
| `getFrameStyleRequirements(): array` | Legacy frame-style requirement map; refreshes legacy style first. | Compatibility/infrastructure |
| `getOwnFrameStyleRequirements(): array` | Exact wrapper around getFrameStyleRequirements(). | Compatibility/infrastructure |
| `structuredInsertionMode(): StructuredInsertionMode` | as-char => INLINE_TEXT_FLOW; otherwise BLOCK. | Infrastructure |
| `toDomNode(DOMDocument $dom): DOMNode` | Materializes frame/text-box; as-char returns frame directly, other anchors wrap frame in text:p. | Infrastructure |
| `toStyleDomNode(DOMDocument $dom): ?DOMElement` | Materializes legacy graphic style node. | Compatibility/infrastructure |

### Compatibility-policy interaction

The following can coexist with semantic frame layout and are retained when a
later semantic wrap value changes:

```text
style:flow-with-text
draw:wrap-influence-on-position
loext:allow-overlap
```

Characterization tests deliberately preserve historical wrap-influence values
without interpreting them. Equivalent effective policy/layout state produces a
stable semantic style identity independent of setter order.

### DrawingLayout and DrawingLayoutProjector classification

Both classes are public PHP classes and fully characterize the semantic frame
layout machinery, but normal end-programmer authoring does not require direct
use: DrawTextBox and ImageElement expose the semantic layout API. For the 1.0
documentation they belong under **Advanced / extension infrastructure**, not
the primary tutorial surface.

DrawingLayout's public constructors/factories/getters/with-methods/toArray are
therefore retained in the complete inventory, but the recommended end-user path
is through the frame-backed element methods rather than manually projecting
native carriers.



## API-family verification — ImageElement

Status: **VERIFIED + DOCUMENTED-COMPLETE for current ImageElement source**.

This section covers PHP-generated image frames. Template-level
`setImage()`, `replaceImageByName()`, and Phase-E mapped `replace-image`
remain separate APIs with different ownership and failure semantics.

### Constructor and complete legacy option vocabulary

```php
new ImageElement(string $imagePath, array $options = [])
```

When `enabled` is omitted it defaults to `true`. If enabled, the source must
exist and be readable or the constructor throws generic `Exception` with the
current "Image not found" message. The constructor then calls `getimagesize()`
and uses the intrinsic pixel aspect ratio for one-dimensional autosizing.

Sizing behavior:

| width | height | result |
|---|---|---|
| omitted | omitted | `5cm × 3cm` |
| supplied | omitted | supplied width; height derived from intrinsic ratio |
| omitted | supplied | width derived from intrinsic ratio; supplied height |
| supplied | supplied | exact supplied pair |

**Compatibility caveat:** the autoscaling implementation converts the supplied
dimension with `(float) rtrim($value, 'cm')` and always writes the derived
dimension in `cm`. It therefore does not implement unit-aware conversion.
The safe documented autoscaling input is a numeric centimetre value such as
`4cm`. Other units can be numerically misinterpreted. This behavior must not
be silently "fixed" during the 1.0 documentation audit.

If `enabled => false`, source existence/readability and intrinsic sizing are
skipped. Materialization returns an empty `text:p` and no draw:image. The
element still reports its image asset through `getImageAssets()`; this is
observable compatibility behavior and should not be presented as a general
conditional-resource guarantee.

The complete constructor/`setStyle()` friendly vocabulary interpreted by
`StyleMapper::mapImageStyleOptions()` is:

| key | accepted/effective values | behavior |
|---|---|---|
| `width` | non-empty value; no mapper validation | `svg:width` |
| `height` | non-empty value; no mapper validation | `svg:height` |
| `wrap` | exactly `none|left|right|run-through` | `style:wrap`; invalid values are silently ignored |
| `align` | exactly `left|right|center|absolute` | stored compatibility control; invalid values silently ignored |
| `anchor` | exactly `paragraph|page|char|as-char` | `text:anchor-type`; invalid values silently ignored |
| `horizontal-pos` | any non-empty value | `style:horizontal-pos`, no validation |
| `horizontal-rel` | any non-empty value | `style:horizontal-rel`, no validation |
| `vertical-pos` | any non-empty value | `style:vertical-pos`, no validation |
| `vertical-rel` | any non-empty value | `style:vertical-rel`, no validation |
| `enabled` | constructor reads truthily/falsily; mapper itself ignores it | controls constructor validation/materialization |

Unknown keys are ignored by the image mapper. There is **no native-prefixed
general escape hatch** in `mapImageStyleOptions()`.

Legacy `align` has additional frame-materialization semantics:

| align | emitted frame compatibility attributes |
|---|---|
| `left` | wrap right; horizontal-pos left; horizontal-rel paragraph |
| `right` | wrap left; horizontal-pos right; horizontal-rel paragraph |
| `center` | wrap none; horizontal-pos center; horizontal-rel paragraph |
| `absolute` | wrap none; horizontal-pos from-left; horizontal-rel page-content |

Without `align`, mapped wrap/horizontal/vertical values are copied directly
onto the frame. A supplied vertical-pos defaults vertical-rel to `paragraph`
when vertical-rel is absent.

### Recommended semantic frame layout

`ImageElement` exposes the same semantic DrawingLayout API as DrawTextBox:

```php
setFrameLayout(array $layout): self
setFrameAnchor(string $anchor): self
setFrameHorizontalAlignment(string $alignment, ?string $relativeTo = null): self
setFrameVerticalAlignment(string $alignment, ?string $relativeTo = null): self
setFrameHorizontalOffset(string $offset, ?string $relativeTo = null): self
setFrameVerticalOffset(string $offset, ?string $relativeTo = null): self
setFrameWrap(string $wrap): self
```

The complete keys, value domains, relation matrix, signed-offset rules,
alignment/offset mutual exclusion and as-char restrictions are the
DrawingLayout contract documented in the DrawTextBox audit.

When semantic layout is active:

- semantic anchor/size/position/wrap override conflicting legacy frame geometry;
- omitted semantic width/height retain the constructor-established dimensions,
  including autoscaled dimensions;
- layout graphic properties become a semantic `StyleRequirement` rather than
  direct frame style attributes;
- `toDomNode()` updates observable `getImageOptions()` compatibility state
  with projected wrap/horizontal/vertical graphic properties;
- repeated materialization is stable;
- `setFrameLayout([])` clears semantic layout and restores legacy alignment/
  positioning behavior.

### ImageElement public method reference

| Method | Contract | Disposition |
|---|---|---|
| `__construct(string $imagePath, array $options = [])` | Local generated image; source validation/autosizing/options above. | Recommended |
| `setFrameLayout(array $layout): self` | Semantic layout master API; empty resets semantic layout. | Recommended |
| six `setFrame*()` methods | Incremental semantic frame-layout API with DrawingLayout validation. | Recommended |
| `setStyle(array $options): self` | **Replaces**, rather than merges, raw/mapped legacy image option state and regenerates compatibility style-name. It does not recompute intrinsic autosizing, update protected width/height/anchor/wrap fields, or alter `enabled`. Calling it after construction can therefore replace constructor-established mapped dimensions unless width/height are supplied again. | Compatibility/legacy style mutator; document carefully |
| `getImagePath(): string` | Returns original source path. | Secondary accessor |
| `getImageOptions(): array` | Returns current mapped/observable compatibility option state, not necessarily original constructor input. Semantic materialization can add projected style properties. | Secondary/diagnostic accessor |
| `getImageAssets(): array` | Returns one `['id' => basename(path), 'path' => path]` asset entry, including for disabled element. | Compatibility/resource accessor |
| `getOwnImageAssets(): array` | Exact wrapper around getImageAssets(). | Infrastructure/resource ownership |
| `getOwnStyleRequirements(): iterable` | With semantic layout, emits common graphic StyleRequirement in styles.xml for projected graphic properties; otherwise empty. | Extension/infrastructure |
| `getImageStyleRequirements(): array` | Returns legacy style-name => imageOptions map. | Compatibility/infrastructure |
| `getOwnImageStyleRequirements(): array` | Exact wrapper around getImageStyleRequirements(). | Compatibility/infrastructure |
| `structuredInsertionMode(): StructuredInsertionMode` | Effective anchor `as-char` => INLINE_TEXT_FLOW; otherwise PRESERVE_TEXT_CONTAINER. Semantic anchor wins over mapped legacy anchor. | Infrastructure, but lifecycle-relevant |
| `toDomNode(DOMDocument $dom): DOMNode` | Disabled => empty text:p. Enabled => draw:frame + embedded draw:image href `Pictures/<basename>`. | Infrastructure/materialization |

### Style-name compatibility caveat

Legacy `setStyle()` generates a style name from mapped image options, excluding
`align` and `style-name` from the hash. `toDomNode()` can attach that
generated name even though `getOwnStyleRequirements()` intentionally returns
no semantic StyleRequirement when DrawingLayout is absent. The separate legacy
`getImageStyleRequirements()` API exposes the historical style requirement
map. This is compatibility architecture; do not describe it as equivalent to
the semantic requirement pipeline.

### Resource identity caveat

The embedded package href and reported asset id use `basename($imagePath)`.
ImageElement itself does not create a collision-safe logical asset name. Any
collision handling/ownership belongs to the surrounding document resource
pipeline and must be documented from that pipeline rather than inferred from
ImageElement.

### 1.0 disposition

```text
RECOMMENDED
  ImageElement constructor
  semantic setFrameLayout()
  semantic setFrame*() convenience methods

SECONDARY / DIAGNOSTIC
  getImagePath()
  getImageOptions()

COMPATIBILITY / LEGACY
  constructor align/wrap/low-level position options where semantic layout supersedes them
  setStyle()
  getImageStyleRequirements()

INFRASTRUCTURE / RESOURCE PIPELINE
  getOwnStyleRequirements()
  getImageAssets()/getOwnImageAssets()
  getOwnImageStyleRequirements()
  structuredInsertionMode()
  toDomNode()

FUTURE / SEPARATE
  custom-shape bitmap-fill replacement belongs to
  CUSTOM-SHAPE-FILL-IMAGE-REPLACEMENT-01, not ImageElement 1.0 redesign
```



## API-family verification — CircularImageElement

Status: **VERIFIED + DOCUMENTED-COMPLETE for the methods CircularImageElement
actually implements/overrides; inherited ImageElement surface is explicitly
bounded below.**

CircularImageElement is not a differently styled normal image frame. It is a
specialized producer for LibreOffice-style circular graphics:

```text
draw:custom-shape (ellipse)
    -> graphic style with draw:fill=bitmap
    -> named draw:fill-image declaration
    -> Pictures/<source basename>
```

That distinction is architecturally important because inheritance exposes
ImageElement methods whose semantics do not project into this producer.

### Constructor and effective options

```php
new CircularImageElement(string $imagePath, array $options = [])
```

The constructor first executes the complete ImageElement constructor, including
`enabled`, source readability checking, getimagesize(), legacy mapping and
one-dimensional autoscaling. It then **overwrites its protected geometry** as:

```php
$this->width  = $options['width']  ?? '3.4cm';
$this->height = $options['height'] ?? '3.4cm';
```

Therefore the effective custom-shape geometry is:

| width | height | custom-shape result |
|---|---|---|
| omitted | omitted | `3.4cm × 3.4cm` |
| supplied | omitted | supplied width × `3.4cm` |
| omitted | supplied | `3.4cm` × supplied height |
| supplied | supplied | exact supplied pair |

This deliberately means the parent ImageElement one-dimensional aspect-ratio
autoscaling does **not** determine CircularImageElement shape geometry. A
single supplied dimension does not make a proportional circle automatically.

The effective anchor used by `toDomNode()` is the inherited protected
`$anchor`, initialized by the parent from `options['anchor'] ?? 'paragraph'`.
The custom-shape renderer does not consume the parent's mapped align/wrap/
horizontal/vertical placement options.

### Materialized ODF shape

`toDomNode()` always creates a `draw:custom-shape`, never
`draw:frame/draw:image`. It writes:

- `text:anchor-type` from inherited constructor anchor;
- `svg:width` and `svg:height` from CircularImageElement geometry;
- `text:animation="none"`;
- semantic generated `draw:style-name`;
- `draw:z-index="0"`;
- one `draw:enhanced-geometry` with `draw:type="ellipse"`,
  viewBox `0 0 21600 21600`, full-circle enhanced path, glue points,
  text area, disabled text-path/concentric-gradient fill, and parallel 3D
  projection.

The semantic graphic style is exactly:

```text
draw:fill              = bitmap
draw:fill-image-name   = cv_photo_<source-stem>
draw:fill-image-width  = 100%
draw:fill-image-height = 100%
style:repeat           = stretch
draw:stroke            = none
```

Shape geometry/placement is intentionally absent from this style identity.
Tests verify that two circular images using the same source but different
width/height/anchor/alignment produce the same semantic graphic style.

### Fill-image dependency

`getOwnFillImageDependencies()` always exposes one typed
`FillImageRequirement`:

```text
document part = styles.xml
name          = cv_photo_<basename-without-extension>
href          = Pictures/<basename-with-extension>
```

The normal setElement() dependency collector traverses this transitively,
including when CircularImageElement is nested in DrawTextBox. Existing authored
`draw:fill-image` declarations with the same name remain authoritative; the
setElement path does not overwrite their href merely because this producer
requests the same symbolic fill-image identity.

This is the current SR-06 semantic contract. Replacement of the bitmap behind
an already authored custom-shape/fill-image remains
`CUSTOM-SHAPE-FILL-IMAGE-REPLACEMENT-01` future work.

### CircularImageElement-owned public method reference

| Method | Contract | Disposition |
|---|---|---|
| `__construct(string $imagePath, array $options = [])` | Parent validation/options run first; effective shape geometry then uses independent 3.4cm defaults as described above. | Specialized public producer |
| `getOwnStyleRequirements(): iterable` | Always emits one common graphic definition in styles.xml for bitmap-fill properties. Available before DOM materialization. | Infrastructure/semantic producer |
| `getOwnFillImageDependencies(): iterable` | Always emits one typed styles.xml fill-image declaration requirement. | Infrastructure/semantic dependency |
| `toDomNode(DOMDocument $dom): DOMNode` | Creates ellipse draw:custom-shape with bitmap-fill style. Also populates legacy compatibility caches. | Infrastructure/materialization |
| `getImageStyleRequirements(): array` | Before toDomNode(): empty. After toDomNode(): legacy map of generated graphic style name to bitmap-fill properties. | Compatibility/infrastructure |
| `getFillImageRequirements(): array` | Before toDomNode(): empty. After toDomNode(): legacy map containing name/path/filename. | Compatibility/infrastructure |
| `getOwnFillImageRequirements(): array` | Exact wrapper around getFillImageRequirements(). | Compatibility/infrastructure |
| `toStyleDomNode(DOMDocument $dom): ?DOMElement` | Always null; semantic/compatibility finalization owns graphic/fill-image styles. | Infrastructure |

### Inherited ImageElement methods: effective vs misleading

Because CircularImageElement extends ImageElement, all public ImageElement
methods are callable in PHP. They are **not all valid CircularImageElement
authoring controls**.

| inherited method/family | effective CircularImageElement behavior |
|---|---|
| `getImagePath()` | Effective: returns physical source path. |
| `getImageAssets()/getOwnImageAssets()` | Effective resource reporting inherited from ImageElement; bitmap source still needs package resource. |
| `getImageOptions()` | Compatibility/diagnostic only. Returns parent's mapped image-option state, which is not a faithful description of custom-shape geometry/materialization. |
| `setStyle(array)` | **Misleading for CircularImageElement.** Mutates parent's raw/mapped imageOptions but custom-shape toDomNode() does not use those options for width/height/anchor/alignment/wrap or bitmap-fill style. Do not teach as CircularImageElement styling API. |
| `setFrameLayout(array)` and all six `setFrame*()` methods | **Currently ineffective for CircularImageElement materialization.** They mutate the inherited private DrawingLayout state used by ImageElement methods, but CircularImageElement::toDomNode() never projects it. Do not advertise these inherited methods for circular/custom-shape positioning in 1.0. |
| `structuredInsertionMode()` | Potentially inconsistent inherited behavior: it can observe inherited DrawingLayout state even though custom-shape toDomNode() ignores that layout. Without such calls it reflects mapped/constructor anchor. Treat as infrastructure and do not use inherited frame-layout calls on CircularImageElement. |
| parent `enabled => false` behavior | **Not preserved by the override.** Parent constructor skips source validation/sizing, but CircularImageElement::toDomNode() does not check inherited enabled and still emits the custom shape/fill dependency/resource references. Therefore `enabled` must not be documented as a supported CircularImageElement option. |

This inheritance mismatch is an important 1.0 documentation finding. Fixing it
would be behavior/API architecture work and is therefore outside the 1.0 audit.
The reference must document the bounded working surface rather than imply that
all ImageElement fluent layout methods work polymorphically.

### Naming/collision caveat

The fill-image symbolic name is derived only from the source filename stem:

```text
/path/a/photo.png -> cv_photo_photo
/path/b/photo.jpg -> cv_photo_photo
```

The element itself does not provide caller-controlled fill-image naming or
collision disambiguation. Registry/materializer conflict behavior is a
document-level concern. Do not promise filename-stem uniqueness at the element
API level.

### 1.0 disposition

```text
SPECIALIZED PUBLIC PRODUCER
  CircularImageElement constructor

EFFECTIVE SECONDARY API
  getImagePath()
  inherited image-resource reporting

SEMANTIC / RESOURCE INFRASTRUCTURE
  getOwnStyleRequirements()
  getOwnFillImageDependencies()
  toDomNode()
  toStyleDomNode()

COMPATIBILITY INFRASTRUCTURE
  getImageStyleRequirements()
  getFillImageRequirements()
  getOwnFillImageRequirements()
  getImageOptions()

DO NOT TEACH FOR CircularImageElement
  inherited setStyle()
  inherited setFrameLayout()
  inherited setFrame*() family
  inherited enabled option

FUTURE
  coherent custom-shape layout/editing API, if needed
  CUSTOM-SHAPE-FILL-IMAGE-REPLACEMENT-01
```

No new 1.0 API is introduced by this finding.



## API-family verification — OdtElement base contract

Status: **VERIFIED + DOCUMENTED-COMPLETE for current OdtElement source**.

OdtElement is the abstract base contract behind structured generated content.
Most methods are extension/document-pipeline contracts rather than ordinary
template-author calls.

### Ownership contract

There are two related concepts: embeddedElements is the historical/default
storage supplied by OdtElement; ownedElements() is the semantic ownership view
used by modern recursive collectors. The default ownedElements() returns
getEmbeddedElements(), but composite subclasses may override it.

Modern StyleRequirementCollector, FillImageRequirementCollector, and
StructuredResourceCollector recurse through ownedElements(). A custom element
that stores children elsewhere must therefore override ownedElements() for
transitive style/dependency/resource discovery.

### Complete public method reference

| Method | Exact base behavior | Disposition |
|---|---|---|
| addElement(OdtElement $element): self | Appends to protected embeddedElements; fluent. Subclasses may override. | Extension API; use where concrete element documents it |
| getEmbeddedElements(): array | Returns historical embedded-elements array. | Compatibility/secondary extension API |
| ownedElements(): iterable | Default returns getEmbeddedElements(); semantic ownership hook. | Advanced extension contract |
| toDomNode(DOMDocument $dom): DOMNode | Abstract; concrete element must materialize itself. | Required extension/materialization contract |
| structuredInsertionMode(): StructuredInsertionMode | Default BLOCK. | Advanced extension contract |
| toStyleDomNode(DOMDocument $dom): ?DOMElement | Default null; historical optional style-node producer. | Compatibility/infrastructure |
| getOwnStyleRequirements(): iterable | Default empty; current element only, no child recursion. | Advanced semantic producer contract |
| getOwnFillImageDependencies(): iterable | Default empty; current element typed FillImageRequirement dependencies only. | Advanced semantic producer contract |
| getOwnFrameStyleRequirements(): array | Default empty; historical current-element frame-style map. | Compatibility/infrastructure |
| getOwnImageStyleRequirements(): array | Default empty; historical current-element image-style map. | Compatibility/infrastructure |
| getOwnFillImageRequirements(): array | Default empty; historical current-element fill-image map. | Compatibility/infrastructure |
| getFrameStyleRequirements(): array | Recursively merges descendant same-method results through ownedElements(). | Historical Compatibility collector |
| getImageStyleRequirements(): array | Same recursive compatibility collector for image styles. | Historical Compatibility collector |
| getFillImageRequirements(): array | Same recursive compatibility collector for fill-image requirements. | Historical Compatibility collector |
| getPlaceholderName(): ?string | Always null in base; no current subclass override/call site found in audited branch. | Deprecated candidate / likely dead |
| getOwnImageAssets(): array | Default empty; current-element physical-resource hook. | Advanced resource producer contract |
| getImageAssets(): array | Recursively concatenates descendant getImageAssets() results through ownedElements(). | Historical Compatibility collector |

### Modern versus historical recursion

The modern pattern is: getOwn...() describes only the current element,
ownedElements() describes children, and a document collector performs recursion
exactly once. New semantic producers must not recursively collect children
inside getOwn...(), or the collector would double-count them.

The historical getFrameStyleRequirements(), getImageStyleRequirements(),
getFillImageRequirements(), and getImageAssets() instead put recursion on
OdtElement itself.

### Structured insertion modes

The base default is BLOCK. Current StructuredElementMaterializer behavior:

- BLOCK normally replaces the containing text:p; historical inline nodes
  text:span, text:s, and text:line-break have special inline replacement.
- INLINE_TEXT_FLOW preserves insertion inside text:p/text:h text flow.
- PRESERVE_TEXT_CONTAINER currently uses the same replace-inside-text-container
  path as INLINE_TEXT_FLOW.
- If insertion splits an inline wrapper with text on both sides, the
  materializer clones/splits that wrapper to preserve surrounding formatting.

This is extension behavior, not a normal document-authoring option.

### Compatibility collector behavior

The compatibility style/fill collectors merge arrays repeatedly. Equal string
keys from later owned children therefore overwrite earlier keys; the base
collector performs no semantic conflict detection. Base getImageAssets()
concatenates arrays and performs no deduplication.

### getPlaceholderName()

Its docblock describes a historical model in which an element could declare
the placeholder it should replace. Current structured insertion is externally
addressed (for example setElement(key, element)); no subclass override or
current call site was found. It should not be taught as normal 1.0 API and is a
strong DEPRECATE candidate, subject to final compatibility classification.

### 1.0 disposition

NORMAL END-PROGRAMMER SURFACE: none at OdtElement level by itself; use concrete
element APIs.

ADVANCED EXTENSION CONTRACT: addElement() where meaningful on the concrete
subclass, ownedElements(), toDomNode(), structuredInsertionMode(),
getOwnStyleRequirements(), getOwnFillImageDependencies(), getOwnImageAssets().

COMPATIBILITY / INFRASTRUCTURE: getEmbeddedElements(), toStyleDomNode(),
getOwnFrameStyleRequirements(), getOwnImageStyleRequirements(),
getOwnFillImageRequirements(), getFrameStyleRequirements(),
getImageStyleRequirements(), getFillImageRequirements(), getImageAssets().

DEPRECATED CANDIDATE: getPlaceholderName().

No 1.0 API change is required by this audit.


## API-family verification — TableExampleGenerator

Status: **VERIFIED + DOCUMENTED-COMPLETE for current source; classified as
non-product example/demo helper.**

TableExampleGenerator is a public class only because it currently lives under
src/Elements. It is not an OdtElement, is not referenced anywhere else in the
audited repository, has no tests/call sites, and contains fixed German demo
data. It therefore must not be presented as part of the 1.0 end-programmer
document-generation API.

The class has one unused private property,
`$summaryKeywords = ['summe', 'total', 'gesamt']`; no method reads it.

### Complete public method reference

| Method | Exact current behavior | Classification |
|---|---|---|
| `createStatusBadge(string $status): Paragraph` | Creates a Paragraph containing the original status text in bold. Case-insensitive exact German status mapping: `in arbeit` => #ffcc00, `abgeschlossen` => #00cc66, `geplant` => #3399ff, otherwise #cccccc. Also supplies `padding => '2px 4px'` and `border-radius => '4px'` in the text style array. | Demo helper; hide from public API |
| `createCourseParagraph(string $title, string $subtitle): Paragraph` | Adds title bold, then literal `' - '` + subtitle italic with `font-size => 'smaller'`. | Demo helper; hide |
| `generateSimpleTable(): array` | Returns fixed six-row German Position/Beschreibung/Betrag example data ending in Summe. | Demo fixture/helper; hide |
| `generateCourseTable(): array` | Returns fixed four-row German course table; cells include Paragraphs produced by the two helper methods. | Demo fixture/helper; hide |
| `generateFinancialSummary(): array` | Returns fixed five-row German Kategorie/Monat/Betrag example data for April. | Demo fixture/helper; hide |

### Styling caveat in createStatusBadge()

The method appears intended to produce a badge, but not every supplied
CSS-like key is part of the current Paragraph text-style vocabulary.

The supplied style array is:

```php
[
    'background-color' => <status color>,
    'font-weight' => 'bold',
    'padding' => '2px 4px',
    'border-radius' => '4px',
]
```

Under the current Paragraph/StyleOptionSplitter path:

- `font-weight` is a text property;
- `padding` is classified as a paragraph property rather than a text-run
  badge property;
- `background-color` is not in StyleOptionSplitter's text-key list and falls
  through to the paragraph/native side in paragraph context;
- `border-radius` is likewise not a recognized text key and falls through.

Consequently the method name/implementation should not be treated as evidence
of a supported generic CSS badge API. This is another reason not to promote the
class as product API.

### 1.0 disposition

```text
HIDE FROM USER DOCUMENTATION
  TableExampleGenerator
  createStatusBadge()
  createCourseParagraph()
  generateSimpleTable()
  generateCourseTable()
  generateFinancialSummary()

RATIONALE
  demo-specific fixed data
  no repository call sites
  no tests
  not an OdtElement
  misleading placement under src/Elements
  no reusable document-model responsibility
```

Whether the class should later be moved out of src/ or removed is cleanup/API
surface work, not required for the 1.0 documentation audit. No replacement API
is needed.



## API-family verification — HtmlImporter

Status: **VERIFIED + DOCUMENTED-COMPLETE for current HtmlImporter public
surface and supported import options.**

HtmlImporter is an important end-programmer adapter. It converts controlled
HTML fragments into native RichText/Paragraph/ListElement/RichTable/
RichTableCell/ImageElement structures. It is not a browser renderer and does
not preserve an HTML DOM as such.

### Public API

```php
HtmlImporter::fromHtml(string $html, array $options = []): RichText
HtmlImporter::parseStyleAttribute(DOMElement $node): array
```

`fromHtml()` is the Recommended public entry point.

`parseStyleAttribute()` is public but is an image-layout compatibility parser
used internally by the importer. It should be classified Advanced/
Compatibility rather than taught as the normal import API.

All other HtmlImporter methods are protected/private implementation hooks.

### fromHtml() options

The complete current option vocabulary contains one option:

| option | default | behavior |
|---|---|---|
| `allow_remote_images` | `false` | truthy value enables HTTP/HTTPS image retrieval through HtmlImageResolver |

Unknown options are currently ignored. The option is cast to bool rather than
strictly type-validated.

The HTML fragment is wrapped in a body and parsed with DOMDocument::loadHTML()
using internal libxml errors. Parser warnings/errors are cleared rather than
surfaced as importer exceptions. The resulting body children are translated
into a new RichText.

### Supported element translation

| HTML | Current translation / exact boundary |
|---|---|
| text node | Added when trim(text) is non-empty; original wholeText is normally retained. A Paragraph is created if needed. |
| p, div, article, section, header, footer, main | Flattened to a Paragraph via block-style splitting; original container semantics are not retained. |
| br | addLineBreak() only when a current Paragraph exists; otherwise no output. |
| strong, b | bold text style |
| em, i | italic text style |
| u | underline |
| mark | background #ffff99 |
| del | line-through |
| sub/sup | style:text-position sub/super |
| code, tt, kbd, samp, pre | monospace + #f4f4f4 background; pre is not browser preformatted layout |
| span | inline style parsed and mapped as text style; nested nodes processed through styled-node path |
| a | Native Paragraph hyperlink; label is trim(textContent), so child markup is flattened. Defaults color #0000ff and underline=true unless importer style array already supplies them. |
| h1-h6 | New Paragraph referencing `Heading N`; trim(textContent) imported as one run with mapped inline style. Child markup is flattened. |
| ul/ol | ListElement bullet/numbered. Flat lists are reliable; nested extraction is partial and uses RichText popLastElementIfList() compatibility behavior. |
| li | Consumed only by parent list logic; not a standalone switch case. |
| blockquote | New Paragraph referencing Quote; trim(textContent), child markup flattened. |
| table | RichTable built from all descendant tr nodes. thead/tbody/tfoot structure is not retained. |
| td/th | RichTableCell; th has no semantic header-row behavior. |
| img | ImageElement inside Paragraph when source resolves; invalid/missing source is silently skipped. |
| unknown element | Element semantics ignored; children recursively traversed. |
| non-element/non-text nodes | Ignored. |

### Block CSS subset

For p/div/article/section/header/footer/main, StyleMapper::splitCssProperties()
recognizes this exact current subset.

Text:
- color
- background-color
- font-weight
- font-style
- text-decoration
- font-size
- font-family

Paragraph:
- margin, margin-top, margin-bottom, margin-left, margin-right
- padding, padding-top, padding-bottom, padding-left, padding-right
- align, text-align
- line-height
- border, border-left, border-top, border-right, border-bottom

Other declarations parsed by parseInlineStyle() are discarded by the split.

A block with paragraph CSS gets a generated paragraph style. Text CSS is
applied to direct text nodes. Nested semantic nodes are processed by their own
logic, so CSS inheritance/composition is not browser-equivalent.

The block handler has historical whitespace behavior: for a direct non-empty
text node, when previousWasText is false and the source text does not start
with a space, it prepends one space. Do not promise browser whitespace
equivalence.

### Inline semantic/style behavior

Semantic wrappers use getRawStyleForTag() then mapTextStyleOptions().
processStyledNode() recursively applies that one supplied style array to text
descendants; it does not compose a new style when it encounters nested
semantic elements. Therefore nested emphasis/style composition is limited.

Span style input goes through parseInlineStyle() then mapTextStyleOptions().
The effective text mapper supports the normal mapped text vocabulary, but
arbitrary CSS is not preserved.

Hyperlinks use the style array produced by parseStyleAttribute(), not the
general inline-CSS text mapper. parseStyleAttribute() is primarily an
image-layout parser, so hyperlink inline CSS should not be documented as a
complete styling surface. The importer supplies blue/underline defaults.

### HTML tables

A table is built from every descendant tr returned by getElementsByTagName().
For each direct td/th child of each tr:

1. table inline CSS is parsed and used as default cell-style input;
2. cell inline CSS is merged over it;
3. paragraph/text style is independently built from the cell;
4. cell decoration is filtered to this exact allow-list:
   background, background-color, border, padding, padding-left,
   padding-right, padding-top, padding-bottom, border-left, border-right,
   border-top, border-bottom;
5. colspan/rowspan are integer-cast and passed to RichTableCell aliases, whose
   setters clamp values below 1 to 1.

Table-level CSS therefore does **not** become RichTable geometry/style. It is
used only as inherited/default cell-style input and only the allow-listed cell
properties survive that path.

Cell text/paragraph CSS uses the same splitCssProperties() vocabulary above.
For text-align center/right, buildStyledParagraphFromCellNode() additionally
references hard-coded named styles CenterPara/RightPara. Left has no analogous
forced named style in this helper.

Nested supported content in a cell is processed into the cell Paragraph.
Table header semantics, browser layout, covered-cell normalization and exact
geometry are not supplied by the HTML importer.

### Image sources and security boundary

HtmlImageResolver accepts:

- readable local filesystem files, returned as realpath;
- valid data:image/<extension>;base64,... payloads recognized by
  getimagesizefromstring(), materialized to a temporary asset;
- HTTP/HTTPS only when allow_remote_images is truthy.

Remote behavior is exact:
- 5 second stream timeout;
- redirects disabled;
- maximum accepted body 5,000,000 bytes (reads limit + 1 to detect excess);
- response must be recognized as image data;
- failure returns null and the img is skipped.

Temporary extensions are normalized to png/jpg/jpeg/gif/bmp; unknown
extensions become png. Temporary files are registered with
TemporaryAssetRegistry.

### img attributes and parseStyleAttribute()

For a resolved img:
- width attribute default: 5cm
- height attribute default: 3cm
- parseStyleAttribute() options are merged **after** attribute defaults, so
  CSS width/height override width/height attributes.
- the resulting options are passed to ImageElement.

parseStyleAttribute() exact recognized CSS:

| CSS | output |
|---|---|
| no style attribute | anchor=as-char |
| display:none | ignore=true and immediate return |
| width / height | same friendly option |
| float:right | anchor=paragraph, wrap=none, align=right |
| float:left | anchor=paragraph, wrap=none, align=left |
| float:none | anchor=as-char |
| position:absolute | anchor=paragraph plus keys style:horizontal-pos=from-left and style:horizontal-rel=page-content |
| left | svg:x |
| top | svg:y |
| margin-left | svg:x, overriding earlier left |
| margin-top | svg:y, overriding earlier top |
| display:block | anchor=paragraph |
| display:inline | anchor=as-char |

If no rule established anchor, it falls back to as-char.

Important compatibility limitations:
- display processing occurs after float/position and can overwrite their anchor;
- display:none produces ignore=true, but the img import path never checks
  ignore. It still constructs ImageElement if the source resolves. Therefore
  display:none is **not currently a reliable hide-image feature**.
- style:horizontal-pos/style:horizontal-rel and svg:x/svg:y are not part of
  ImageElement's friendly mapper keys (which expects horizontal-pos/
  horizontal-rel without style: prefix and has no svg:x/svg:y handling).
  These parseStyleAttribute outputs therefore must not be advertised as a
  verified effective absolute-positioning API.
- the historical sample_html_images.php is experimental evidence, not a
  guarantee of these layout mappings.

### Failure/fallback semantics

HtmlImporter generally favors omission/degradation over exceptions:
- malformed HTML is left to DOMDocument recovery;
- invalid/missing/disallowed image sources are skipped;
- unsupported elements lose their semantics but recurse into children;
- unsupported CSS is ignored by bounded mapping/splitting;
- remote failures do not fail the import.

Exceptions can still originate from downstream element/style construction for
inputs that reach stricter APIs; fromHtml() does not establish a transactional
validation boundary.

### 1.0 disposition

RECOMMENDED:
- HtmlImporter::fromHtml() for controlled HTML-to-native-ODT adaptation
- option allow_remote_images only with explicit security/network caveat

ADVANCED / COMPATIBILITY:
- HtmlImporter::parseStyleAttribute(), primarily historical image-layout parser

IMPLEMENTATION, NOT PUBLIC AUTHORING API:
- processNode()
- processStyledNode()
- parseInlineCss()
- getRawStyleForTag()
- handleStyledBlockElement()
- buildStyledParagraphFromCellNode()
- HtmlImageResolver as importer infrastructure unless direct resolver use is
  intentionally promoted separately

DOCUMENTATION BOUNDARY:
- no browser-equivalent HTML/CSS promise
- no external stylesheet/selectors/cascade/layout engine
- nested list behavior partial
- HTML table header/geometry semantics partial
- historical HTML image layout mappings must not be overstated


## API-family verification — Structured content completion pass

Status: **VERIFIED + DOCUMENTED-COMPLETE for Paragraph, RichText, ListElement,
DrawingLayout, and DrawingLayoutProjector on the audited branch**.

This pass mechanically compared every public method in `src/Elements/*.php`
and `src/Import/HtmlImporter.php` with this inventory. Previously completed
OdtElement, ImageElement, CircularImageElement, RichTable, RichTableCell,
DrawTextBox, TableExampleGenerator, and HtmlImporter audits were not reopened.
No missing public method was found in Paragraph, RichText, ListElement, or
DrawingLayout. The three public DrawingLayoutProjector methods were the only
surface missing from the earlier inventory and are recorded below.

### Paragraph — complete public contract

All mutators below return `self` unless a different return type is shown.

```php
__construct(?string $paragraphStyle = null, array $paragraphStyleOptions = [])
addText(string $text, array $style = []): self
applyTextStyle(array $style): self
addLineBreak(int $count = 1): self
addTab(): self
addTabStop(float $position, string $alignment = 'left', ?string $text = null, array $style = []): self
addTabularLines(array $lines, array $tabDefs, array $headerStyle = []): self
addPHyperLink(string $text, string $href, array $style = []): self
addElement(OdtElement $element): self
getEmbeddedElements(): array
setParagraphStyle(string $styleName): self
setParagraphStyleOptions(array $options): self
addTabStopDefinition(float $position, string $alignment = 'left'): self
addKeyValueLine(string $key, string $value, float $tabPosition = 10.0, ?array $style = null): self
addTabsWithTexts(array $tabs): self
addHyperlink(string $text, string $href, array $style = []): self
setBulleted(): self
setNumbered(): self
isList(): bool
getOwnStyleRequirements(): iterable
toDomNode(DOMDocument $dom, bool $insideTextBox = false): DOMNode
```

Construction semantics:

- A non-null paragraph style name is referenced directly.
- Paragraph options without an explicit style name create a generated paragraph
  style name.
- `setParagraphStyleOptions()` replaces, rather than merges, the stored option
  array and creates a generated paragraph style name when needed.
- A named paragraph style with no options is emitted as an unresolved style
  reference; a named/generated style with options is emitted as a common
  paragraph style definition with parent `Standard`.

Paragraph option splitting is performed by StyleOptionSplitter in paragraph
context. Friendly paragraph keys are:

`align` (normalized to `text-align`), `text-align`, `text-indent`,
`line-height`, `margin`, `margin-top`, `margin-right`,
`margin-bottom`, `margin-left`, `padding`, `padding-top`,
`padding-right`, `padding-bottom`, `padding-left`, `border`,
`border-top`, `border-right`, `border-bottom`, `border-left`,
`keep-with-next`, `break-before`, `break-after`, `writing-mode`,
`number-lines`, `line-number`, and `tab-stops`.

The underlying paragraph mapper additionally recognizes
`background-color`, `keep-together`, `widows`, and `orphans`.
Because StyleOptionSplitter does not classify those four as friendly paragraph
keys, they are preserved through its compatibility fallback rather than through
the bounded friendly-key list. Native-prefixed `fo:`, `style:`, `draw:`,
`svg:`, and `loext:` keys are also preserved to the paragraph/native side.
Unknown keys likewise currently fall through to that side. This permissiveness
is a Compatibility escape hatch, not evidence that arbitrary keys are a
recommended style API.

Direct `Paragraph::addText()`, hyperlink style arrays, tab-stop text style
arrays, and `applyTextStyle()` use the text mapper directly. Effective text
keys are `bold`, `italic`, `font-weight`, `font-style`, `underline`,
`text-decoration`, `color`, `background-color`, `font-size`,
`font-family`, `text-line-through`, `style:text-position`,
`font-variant=small-caps`, and `monospace=true`, plus native `fo:` and
`style:` properties. Friendly font-size names map as follows:
`xx-small=6pt`, `x-small=7pt`, `small=9pt`, `medium=11pt`,
`large=13pt`, `x-large=15pt`, `xx-large=17pt`; other non-empty values are
passed through. `monospace=true` maps to Courier New.

`addLineBreak($count)` appends exactly `$count` line-break parts when count
is positive; zero/negative counts append none. `addTab()` emits one
`text:tab`.

Tab helper behavior is historical and must be documented literally:

- `addTabStopDefinition($position, $alignment)` appends a paragraph
  `tab-stops` definition. Position is later serialized as
  `$position . 'cm'`; alignment defaults to `left`. There is no validation
  of the alignment string.
- `addTabStop($position, $alignment, $text, $style)` stores position and
  alignment on a content part, but rendering emits only a `text:tab` and the
  optional following text. It does **not** itself create the paragraph tab-stop
  definition. Its position/alignment fields therefore have no direct rendering
  effect unless a definition is supplied separately.
- `addTabularLines()` first creates definitions from each tab definition
  (`position` required, `alignment` default `left`), then emits rows using
  tabs and line breaks. It emits an initial tab before each row. Header style is
  applied only to row index 0.
- `addKeyValueLine()` creates a right-aligned tab definition (default 10.0cm),
  then key + tab + value with the same optional text style.
- `addTabsWithTexts()` requires `position` per entry, defaults alignment to
  `left`, defaults text to empty and style to empty; each entry creates both a
  definition and a tab followed by text.

Hyperlinks have an important 1.0 compatibility distinction:

- `addHyperlink()` is the normal spelling and correctly renders an unstyled
  ODF hyperlink with `xlink:href`, `xlink:type="simple"`, and
  `xlink:show="new"`.
- Both hyperlink methods render a supplied style by wrapping the label in a
  generated `text:span`.
- `addPHyperLink()` additionally registers that generated text style in the
  paragraph's semantic style requirements.
- `addHyperlink()` currently does **not** register the supplied style in
  `textStyleMap`. Consequently a styled `addHyperlink()` can reference a
  generated style name without ensuring its definition is materialized by the
  current semantic style collector.

Disposition: `addHyperlink()` is Recommended for unstyled links.
`addPHyperLink()` is retained as Compatibility for styled-link persistence.
It is **not** deprecated in 1.0 while it remains the working styled-link path.
The missing style registration in styled `addHyperlink()` is a defect/
compatibility finding, not a reason to redesign the API during 01F.

`applyTextStyle()` merges the supplied style into existing `text`,
`hyperlink`, and `tab-stop` parts only, regenerating and registering their
text style names. Line breaks/tabs and embedded elements are unaffected.

`setBulleted()` and `setNumbered()` set both paragraph style and list style
to `Bullet_20_Symbol` / `Numbering_20_Symbol`. `isList()` reports whether
that internal list style is non-empty. Outside text boxes, materialization then
wraps the paragraph in `text:list/text:list-item`; inside a text box the list
wrapper and paragraph style attribute are suppressed.

`addElement()`, `getEmbeddedElements()`, `getOwnStyleRequirements()`, and
`toDomNode()` are Advanced/Extension or materialization surface rather than
normal authoring helpers. Embedded elements are appended after paragraph parts.

**Paragraph disposition:** Recommended authoring API for paragraph/text,
breaks, tabs, named/options styles, unstyled hyperlinks, and legacy paragraph
list convenience; Compatibility for `addPHyperLink()` and the historical tab
helpers where their literal behavior is required; Advanced/Infrastructure for
ownership/style/materialization methods.

### RichText — complete public contract

```php
addParagraph(string|Paragraph $text = '', ?string $styleName = null, array $styleOptions = []): self
addTable(RichTable $table): self
addImage(ImageElement $image): self
addParagraphBreak(int $count = 1): self
addMultiParagraph(array $lines, ?array $style = null, bool $firstBold = false): self
addText(string $text, array $style = []): self
addLineBreak(): self
addTab(): self
addBulletList(array $items, array $style = []): self
addNumberedList(array $items, array $style = []): self
toDomNode(DOMDocument $dom, bool $insideTextBox = false): DOMNode
addElement(OdtElement $element): self
ownedElements(): iterable
applyParagraphStyleOptions(array $options): self
applyTextStyle(array $style): self
getImageAssets(): array
popLastElementIfList(): ?ListElement
```

RichText is an ordered composite. `addParagraph(Paragraph)` appends the
object unchanged and ignores the separate styleName/styleOptions arguments.
For string input it creates a Paragraph, optionally references `$styleName`,
splits `$styleOptions` in paragraph context, applies the paragraph portion to
the paragraph and the text portion to the new text run.

`addText()` operates on the last Paragraph or creates one. Its mixed style
array is split in paragraph context. This means `background-color`, although
supported by Paragraph::addText() as a direct text key, is not classified as a
RichText text convenience key by StyleOptionSplitter and falls to the paragraph
side. Users requiring direct run background styling should construct/use a
Paragraph rather than infer browser/CSS semantics from RichText convenience
arrays.

`addLineBreak()` and `addTab()` target the last Paragraph or create one.
`addParagraphBreak($count)` appends empty Paragraph objects for positive
counts and none for zero/negative counts.

`addMultiParagraph()` creates one Paragraph per supplied line. `$style`
defaults to empty; when `$firstBold` is true, `bold => true` is merged over
the first line's supplied style.

`addBulletList()` / `addNumberedList()` accept arrays of values that are
passed to `Paragraph::addText(string $text,...)`; under PHP's declared string
parameter contract non-string values are therefore not a documented input.
The supplied style is a direct Paragraph text style for every item.

`addImage()` creates a new Paragraph containing the ImageElement.
`addTable()` and generic `addElement()` append directly.

`applyParagraphStyleOptions()` and `applyTextStyle()` affect **direct
Paragraph children only**. They do not recursively style Paragraphs inside
lists, tables, frames, or other composites. They are Compatibility helpers for
mixed convenience style arrays, not general recursive styling APIs.

`ownedElements()` exposes the complete ordered child set and is the modern
ownership hook used by recursive document collectors.

`toDomNode()` returns a DocumentFragment and materializes each child in order.
Its `$insideTextBox` parameter is currently not forwarded to child
`toDomNode()` calls; it therefore has no observable effect in RichText itself
on the audited source.

`getImageAssets()` is a historical Compatibility collector with deliberately
recorded limitations: it inspects only direct Paragraph children and only
direct embedded ImageElement children of those paragraphs. It does not provide
the modern transitive ownership/resource traversal guarantee and must not be
taught as the preferred resource-discovery API.

`popLastElementIfList()` removes and returns the final child only when it is a
ListElement, otherwise returns null without mutation. Its current repository
purpose is HtmlImporter nested-list compatibility and it should be classified
Compatibility/Importer helper rather than normal document-authoring API.

**RichText disposition:** Recommended composite authoring API for adding
paragraphs, text, breaks, tables, images, lists, and generic structured
elements; Advanced for `ownedElements()`/materialization; Compatibility for
the apply-style helpers, historical `getImageAssets()`, and
`popLastElementIfList()`.

### ListElement — complete public contract

```php
__construct(string $type = 'bullet', ?string $styleName = null)
addItem(Paragraph|self $item): self
setLevel(int $level): self
addSubList(ListElement $list): self
ownedElements(): iterable
toDomNode(DOMDocument $dom): DOMNode
getImageAssets(): array
```

The constructor stores `$type` without validation. Exactly `numbered`
selects default style `Numbering_20_Symbol`; every other type value falls back
to `Bullet_20_Symbol`. A non-null `$styleName` overrides that default.

`addItem()` accepts only Paragraph or ListElement. `addSubList()` sets the
child list's internal level to parent level + 1 and appends it as an item.
`setLevel()` clamps to the inclusive range 1..10.

Important current limitation: the stored level is not read by
`toDomNode()`. Nesting is represented structurally by nested `text:list`
nodes, but changing `setLevel()` by itself has no rendering effect. The level
value is therefore Compatibility state, not a supported visual indentation/
numbering control.

Materialization creates one `text:list` with `text:style-name` and one
`text:list-item` per item. Paragraph items materialize as paragraphs; nested
ListElement items materialize as nested lists inside their list item.

`ownedElements()` returns all items and is the modern recursive ownership
hook. `getImageAssets()` recursively calls item `getImageAssets()` when
available and concatenates results without deduplication; it is historical
Compatibility resource collection.

Programmatic ListElement construction remains distinct from
LIST-ITEM-POPULATION-01, which concerns population of Writer-authored lists.

**ListElement disposition:** Recommended for simple programmatic bullet/
numbered list construction and explicit nesting; `setLevel()` Compatibility
because it currently does not affect output; ownership/materialization/resource
methods Advanced/Compatibility as described above.

### DrawingLayout — complete semantic contract

DrawingLayout is immutable semantic layout state. `empty()` returns an
all-null layout. `fromArray()` accepts exactly these top-level keys:
`anchor`, `width`, `height`, `horizontal`, `vertical`, `wrap`.
Unknown keys throw InvalidArgumentException.

Accepted values are:

- anchor: `paragraph|char|as-char|page`;
- width/height: positive absolute ODF lengths in `cm|mm|in|pt|pc`;
- horizontal alignment: `left|center|right`;
- vertical alignment: `top|middle|bottom`;
- offsets: signed absolute lengths in the same units;
- wrap: `none|left|right|parallel|dynamic|run-through`;
- each axis group must define exactly one of `alignment` or `offset`, plus
  optional `relative-to`.

Relation matrix:

| anchor | horizontal relative-to | vertical relative-to |
|---|---|---|
| paragraph | paragraph, paragraph-content, page, page-content | paragraph, paragraph-content, page, page-content |
| char | char, paragraph, paragraph-content, page, page-content | char, paragraph, paragraph-content, page, page-content, baseline |
| page | page, page-content | page, page-content |
| as-char | none | baseline |

A null anchor is validated as paragraph for relation purposes. Horizontal
placement is rejected for `as-char`; vertical offsets are also rejected for
`as-char`. Default relation for convenience setters is page for page anchor,
baseline for as-char vertical placement, otherwise paragraph.

`withAnchor()`, `withHorizontalAlignment()`, `withHorizontalOffset()`,
`withVerticalAlignment()`, `withVerticalOffset()`, and `withWrap()`
return new validated instances. Changing one axis from alignment to offset (or
vice versa) replaces the previous mode on that axis. Because setters rebuild
through `fromArray()`, changing an anchor can fail when retained axis state is
invalid for the new anchor.

The scalar getters expose the normalized semantic state. `toArray()` emits
only non-null authored state and round-trips axis mode as either
`alignment` or `offset` plus `relative-to`.

**DrawingLayout disposition:** Recommended semantic value object indirectly
through frame-backed element APIs such as `setFrameLayout()`; direct
construction/manipulation is Advanced but supported. Native ODF carrier keys
are intentionally not accepted by `fromArray()`.

### DrawingLayoutProjector — complete public contract

```php
objectAttributes(DrawingLayout $layout): array
graphicLayoutProperties(DrawingLayout $layout): array
requiresInlineTextFlow(DrawingLayout $layout): bool
```

This stateless projector is **Infrastructure/Advanced**, not normal
end-programmer authoring API.

`objectAttributes()` projects semantic state to object-level carriers:
anchor -> `text:anchor-type`, width/height -> `svg:width/svg:height`,
horizontal offset -> `svg:x`, vertical offset -> `svg:y`. Null/unset values
are omitted.

`graphicLayoutProperties()` projects alignment to
`style:horizontal-pos/style:horizontal-rel` and
`style:vertical-pos/style:vertical-rel`; offset modes use
`from-left` / `from-top` respectively while the actual coordinate remains
on the object attribute. Wrap maps to `style:wrap`. Unset axes/wrap are
omitted.

`requiresInlineTextFlow()` returns true exactly when anchor is `as-char`.
Frame-backed elements use this distinction to select inline insertion
semantics.

### Structured-content completion result

The structured-content block is mechanically closed for the current branch:

- Paragraph: VERIFIED + DOCUMENTED-COMPLETE
- RichText: VERIFIED + DOCUMENTED-COMPLETE
- ListElement: VERIFIED + DOCUMENTED-COMPLETE
- DrawingLayout: VERIFIED + DOCUMENTED-COMPLETE
- DrawingLayoutProjector: VERIFIED + DOCUMENTED-COMPLETE, Infrastructure
- previously completed OdtElement, ImageElement, CircularImageElement,
  RichTable, RichTableCell, DrawTextBox, TableExampleGenerator, and
  HtmlImporter remain closed.

No new 1.0 architecture is introduced by this pass. The two notable
compatibility findings are the styled `addHyperlink()` style-registration gap
and the non-rendering `ListElement::setLevel()` state. They are recorded as
current behavior rather than silently repaired.

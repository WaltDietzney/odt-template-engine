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


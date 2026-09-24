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


## API-family verification — OdtTemplate lifecycle and Classic Template Processing completion

Status: **VERIFIED + DOCUMENTED-COMPLETE for the OdtTemplate classic
assignment/render lifecycle and the classic template-language implementation on
the audited branch**.

This completion pass closes the generic lifecycle questions left by the three
earlier OdtTemplate tranches and records the exact classic-language execution
contract. Capability-specific facade families (metadata, images, styles/page
defaults, Writer-native targets, inspection DTOs, mapping/preflight/automation)
remain governed by their own audits and are not falsely marked complete here.

### Canonical classic lifecycle

The recommended classic workflow is:

```php
$template = new OdtTemplate($templatePath);
$template->assign($values);
$template->assignRepeating('items', $rows);
$template->render();
$template->save($outputPath);
```

`__construct()` loads/prepares the source immediately and registers
`cleanup()` for PHP shutdown. `assign()` and `assignRepeating()` stage
data only; `render()` mutates the current working content.xml and styles.xml;
`save()` finalizes the current document-owned graphic/font state and writes
the ODT package. `save()` does not implicitly call `render()`.

`assign(array $values)` merges into the existing value stack with later keys
overwriting earlier keys. `setValues(array $values)` performs the same merge
and is retained as a **Compatibility alias**. Neither clears keys omitted by a
later call.

`assignRepeating(string $key, array $rows)` replaces the staged row
collection for that foreach key. `setRepeating()` has the same implementation
but is explicitly deprecated in source: **DEPRECATE / Compatibility; use
assignRepeating() + render()**.

`setRepeatingData(array $data)` is not an alias. It immediately repairs
broken variables and mutates both working DOMs through an older direct foreach
algorithm; it does not populate repeatStack and does not require render().
There is no focused current characterization test or repository caller beyond
the definition. **1.0 disposition: DEPRECATE / historical Compatibility and
HIDE FROM NORMAL USER DOCUMENTATION.** Its current behavior is retained, not
redesigned during 01F.

### render() ordering and mutation semantics

One render pass currently performs, independently for content.xml and
styles.xml:

1. normalize only structured placeholders staged as OdtElement values;
2. repair broken variable fragments;
3. materialize `nl2br` special placeholders;
4. materialize `ul`/`ol` special placeholders;
5. replace staged scalar values and legacy staged OdtElement values;
6. run the historical text-box scalar pass;
7. apply every staged foreach block;
8. apply paragraph-based conditionals.

This ordering is observable compatibility behavior. In particular, foreach
row substitution occurs before the final conditional pass. Current
characterization shows that condition markers cloned inside foreach blocks are
consumed by row substitution rather than evaluated row-locally. Therefore
classic foreach does **not** provide a supported row-local conditional scope.
This is the already-known post-1.0 CLASSIC-FOREACH-SCOPE-01 concern, not a 1.0
redesign opportunity.

`render()` is destructive against the working DOM. It is not a
"re-render-from-original-source" operation. Once ordinary scalar placeholders
or classic control markers have been consumed, assigning new values and
calling render again does not reconstruct them. The reset boundary is
`load()`.

The legacy `assign(['x' => $odtElement]) -> render()` path remains
Compatibility behavior and has focused repeated-render characterization for
representative structured producers. It must not be presented as equivalent to
the recommended `setElement()` semantic lifecycle.

### Classic scalar syntax

Plain variables use:

```text
{{name}}
```

The scalar replacement implementation iterates staged values and performs
literal token replacement. Values that are not OdtElement instances are passed
to PHP string replacement semantics; the public classic contract should
therefore teach scalar/string-compatible values rather than arbitrary object or
collection values.

Filtered variables use:

```text
{{filter:name}}
{{filter:name|option}}
```

Names are word-character identifiers in the parser/replacement grammar.
Unknown/missing filtered variables resolve to the empty string before filter
application. Unknown filter names return the value unchanged.

Exact built-in filter behavior:

| filter | option/default | current result |
|---|---|---|
| `upper` | none | `mb_strtoupper(value)` |
| `lower` | none | `mb_strtolower(value)` |
| `trim` | none | `trim(value)` |
| `date` | PHP date format; default `d.m.Y` | `date(format, strtotime(value))` |
| `number` | decimal count; default `2` | decimal comma + dot thousands separator |
| `currency` | option ignored | exactly 2 decimals, decimal comma + dot thousands separator + ` €` |
| `checkbox` | option ignored | truthy value -> `☑`, falsy -> `☐` |
| `nl2br` | none | special DOM materialization; ordinary filter callback itself returns value unchanged |
| `ul` | none | special DOM materialization for unordered list |
| `ol` | none | special DOM materialization for ordered list; it is handled by list replacement even though the scalar filter callback has no explicit `ol` branch |
| unknown | any | value unchanged |

`number` and `currency` first replace comma with dot and cast to float.
No locale object is involved. `date` uses PHP `strtotime()` and the process
date/time environment; invalid-date behavior is therefore the current PHP
conversion behavior, not a separately validated date contract.

### nl2br and classic list placeholders

`{{nl2br:name}}` splits the staged value on CRLF/LF/CR and inserts native
`text:line-break` elements between parts. Empty parts do not create text
nodes, but intervening line-break nodes are retained.

`{{ul:name}}` and `{{ol:name}}` split the staged string on line endings and
replace the containing `text:p` with a native `text:list`.
The styles are `Bullet_20_Symbol` and `Numbering_20_Symbol` respectively.
Each line becomes one list item, including an empty line as an empty paragraph
item. The replacement only occurs when the matched text node's direct parent is
`text:p`; this is a bounded classic convenience, not a general structured-list
templating language.

### Classic foreach syntax and limits

```text
{{#foreach:items}}
... {{name}} ...
{{#endforeach}}
```

Recommended data staging is:

```php
$template->assignRepeating('items', [
    ['name' => 'Alpha'],
    ['name' => 'Beta'],
]);
```

The active algorithm locates a start marker in a `text:p`, then searches
following siblings for the first element containing `{{#endforeach}}`.
Nodes between the marker paragraphs are removed, cloned once per row and
inserted in source order. With an empty row array the complete block, including
markers, is removed.

Row substitution is deliberately simple and differs from root scalar
replacement: every `{{...}}` token in cloned row text is interpreted as a
literal row key; a missing row key becomes the empty string. It does not parse
classic filters or structural-control semantics inside the row substitution
step.

The clone preserves authored ODF subtree structure/styles, but it also clones
native identities unchanged. Characterization explicitly records duplicate
Writer table names, bookmark identities, and Section names when such objects
are inside a classic foreach. Classic foreach therefore must not be advertised
as identity-safe native-object cloning.

Malformed foreach with no following end marker is not reported through a
dedicated exception; the current algorithm stops processing that occurrence.
Nested/complex scope semantics are not promoted beyond what current
characterization proves.

### Classic condition syntax and exact grammar

Supported paragraph marker forms are:

```text
{{#if:expression}}
{{#elseif:expression}}
{{#else}}
{{#endif}}

{{#ifnot:expression}}
...
{{#endif}}
```

Markers are paragraph-based. The selected branch's existing paragraph/span
structure is preserved. Unselected **paragraphs** are removed; surrounding
non-paragraph structures are not generically removed. Characterization
therefore deliberately records that an unselected table may retain its table
shell while the paragraphs inside it are removed.

Condition expressions support either a bare reference or one binary comparison:

```text
name
count >= 2
name == "Anna"
state != 'closed'
price < 100
```

Supported operators are `== != > < >= <=`. The left side must be a
word-character reference name. The right side is the remaining expression text
with surrounding single/double quote characters trimmed. If both operands are
numeric they are cast to float; otherwise PHP's existing loose comparison
semantics are used.

A bare reference uses:

```php
filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
```

with missing value defaulting to false. The method has a declared `bool`
return, so the classic public contract should teach boolean-compatible values
rather than promise general PHP "truthiness". `ifnot` negates the evaluated
result. `elseif` uses normal `if` evaluation. The first matching branch is
selected; else is used only when no condition matched.

An unterminated conditional block (no discovered `{{#endif}}`) is left
unprocessed rather than raising a dedicated malformed-template exception.

### Format-preservation boundary

Current Template Authoring characterization establishes a deliberately bounded
claim:

- selected conditional paragraphs preserve their existing paragraph/span
  styles;
- foreach clones preserve authored subtree/style structure;
- classic foreach does not rebuild cloned structures from PHP.

It does **not** establish that arbitrary classic controls can wrap every ODF
structure with browser/template-engine block semantics. The table-shell
characterization and duplicated native identities are explicit counterexamples.
Public documentation must keep the phrase "lightweight logic" literal.

### load(), refresh(), save(), cleanup()

`load(): void` resets the package working state from the **original template
source**, resets legacy-structured and successful Phase-E lifecycle flags, then
reruns template preparation. It discards unsaved working mutations. It does not
clear the PHP valueStack or repeatStack in current source; because the working
DOM is restored, a later explicit render may consume those still-staged values
again. This stack-retention detail is current implementation behavior and should
not be turned into a stronger application workflow guarantee.

`refresh()` has no declared return type and currently returns null. It
finalizes document-owned graphic/font state, persists core documents, and then
calls `load()`; characterization proves the observable working state is still
reset to the original template. **Compatibility lifecycle API; do not teach it
as a normal refresh/preserve-mutations operation.**

`save(string $outputPath): void` finalizes image/graphic/font state, applies
the historical bullet-indentation adjustment and delegates package writing.
Repeated save after semantic `setElement()` is characterized as stable for
representative resources and without cross-document leakage. Save does not
reset the document and does not render staged classic values automatically.

`cleanup(): void` releases the temporary package workspace. Construction also
registers it for shutdown. Independent OdtTemplate instances have isolated
workspaces. Reuse of an object after explicit cleanup is **not** a documented
contract.

### extractTemplateVariables() exact projection

`extractTemplateVariables(): array` scans serialization of the **current
working content.xml and styles.xml** and merges unique discoveries from both.
It always returns:

```php
[
    'variables' => [],
    'loops' => [],
    'conditions' => [],
    'negated_conditions' => [],
    'filters' => [],
    'filter_options' => [],
]
```

The lexical parser recognizes scalar/filter expressions matching
`{{filter?:name|wordOption?}}`, foreach names matching `\w+`, if/elseif
expression text up to `}`, and ifnot names matching `\w+`.
`filter_options` is keyed by variable name and contains unique captured
word-only options.

Because the scalar regex is lexical, classic control tokens can also contribute
incidental variable/filter-looking matches depending on their serialized text.
The method provides no scope, provenance, native-object or diagnostic model.
**Classification: Compatibility/tooling inspection API; use
`inspectTemplate()` for the semantic source contract.**

### Debug surface

`enableDebugMode(): void` only changes the debug flag from false to true.
There is no public disable/reset method. `getDebugLog(): array` returns the
current accumulated list. The protected `log()` appends only when debug mode
is enabled.

No stable set of emitted debug messages or debug-log lifecycle across
load()/refresh() is established by current focused tests. **Classification:
Advanced diagnostic/Compatibility API; message strings are not a stable
machine-readable contract.**

### TemplateProcessor and ConditionExpression visibility

The mechanical public-surface audit also records the extracted classic-language
collaborators rather than letting public PHP visibility silently become user
documentation.

`TemplateProcessor` exposes public stateless DOM operations for normalization,
scalar replacement/discovery, bounded subtree replacement, special nl2br/list
materialization, foreach, conditionals, filters, and condition evaluation.
These methods exist so the facade and Writer-native/declarative subsystems can
reuse established classic semantics against bounded DOM regions.

**Classification: Advanced/Infrastructure.** They are not the normal
end-programmer template API and should be hidden from the primary website
reference. Their public visibility is an extension/integration seam, not a
second recommended facade.

Public methods mechanically accounted for:

```php
normalizeTemplateDom(DOMDocument $dom): void
fixBrokenVariables(DOMNode $node): void
replaceScalarText(string $text, array $values, callable $applyFilter): string
scalarVariableNames(DOMNode $root): array
scalarVariableNamesOwnedBy(DOMElement $section): array
unsupportedExpressions(DOMNode $root): array
replaceScalarTextInSubtree(DOMNode $root, array $values, callable $applyFilter, bool $excludeNestedSections = false, bool $excludeSpecialExpressions = false): void
replaceScalarTextOwnedBy(DOMElement $section, array $values, callable $applyFilter): void
applyFilter(string $filter, string $value, ?string $option = null): string
replaceNl2brInDom(DOMDocument $dom, array $values): void
replaceNl2brInNode(DOMNode $root, array $values, bool $excludeNestedSections = false, ?array $allowedNames = null): void
replaceListsInDom(DOMDocument $dom, array $values): void
replaceListsInNode(DOMNode $root, array $values, bool $excludeNestedSections = false, ?array $unorderedNames = null, ?array $orderedNames = null): void
applyRepeatingInDom(DOMDocument $dom, string $key, array $rows, callable $replacePlaceholders): void
applyConditionalsInDom(DOMDocument $dom, array $values, callable $evaluateCondition): void
evaluateCondition(string $expression, array $values): bool
```

`ConditionExpression` is the parsed value object for the existing condition
grammar. Its public constructor, `parse()`, scalar accessors, and
`evaluate()` are **Advanced/Infrastructure**, not a separately recommended
user expression API. Its semantics are intentionally those documented above;
the class must not be interpreted as an invitation to extend the 1.0 grammar.

### Protected OdtTemplate compatibility facade

The protected methods on OdtTemplate are not end-programmer calls, but they are
part of subclass compatibility and therefore cannot be treated as private
implementation during 1.0 documentation/refactoring.

Classic-relevant protected wrappers include
`setValuesInDom()`, `fixBrokenVariables()`,
`replacePlaceholdersInNode()`, `replaceInText()`,
`replaceNl2brInDom()`, `replaceListsInDom()`,
`applyConditionalsInDom()`, `evaluateCondition()`,
`applyRepeatingInDom()`, `applyAllRepeatingBlocksInDom()`,
`normalizeTemplateDom()`, `renderTextBoxes()`, plus the historical
text-based conditional/repeating helpers.

Current architecture deliberately delegates many of these wrappers to
TemplateProcessor so subclass overrides at the OdtTemplate facade remain
observable. **Classification: protected Compatibility/Extension surface.**
Future refactors must preserve polymorphic dispatch where existing facade
callbacks rely on it.

### OdtTemplate / Classic completion result

For 01F, the generic OdtTemplate/classic block is now closed:

- constructor + canonical assign/assignRepeating/render/save lifecycle:
  **VERIFIED + DOCUMENTED-COMPLETE**;
- `setValues()`: **Compatibility**;
- `setRepeating()`: **DEPRECATE / Compatibility**;
- `setRepeatingData()`: **DEPRECATE / historical Compatibility; hidden from
  normal user docs**;
- `load()`: **Advanced lifecycle / Compatibility**;
- `refresh()`: **Compatibility lifecycle**;
- `cleanup()`: **Advanced lifecycle**;
- classic variables/filters/nl2br/lists/foreach/conditions:
  **VERIFIED + DOCUMENTED-COMPLETE with bounded format/scope claims**;
- legacy `assign(OdtElement)`: **Compatibility**, not a replacement for
  `setElement()`;
- `extractTemplateVariables()`: **Compatibility/tooling**;
- debug API: **Advanced diagnostic/Compatibility**;
- TemplateProcessor + ConditionExpression: **Advanced/Infrastructure**;
- protected OdtTemplate wrappers: **Compatibility/Extension**.

No implementation behavior was changed. Findings that would require semantic
redesign remain outside 1.0; in particular classic foreach row-local condition
scope remains CLASSIC-FOREACH-SCOPE-01.


## API-family completion — Metadata, Writer User Fields, and facade image operations

Status: **VERIFIED + DOCUMENTED-COMPLETE for the public Metadata and Writer
User Field families and for the current imperative `setImage()` /
`replaceImageByName()` facade operations**.

This pass closes the capability-specific OdtTemplate questions intentionally
left outside the generic lifecycle audit. It does not merge the three distinct
image models: PHP-generated `ImageElement`, classic placeholder image
insertion, and Writer-owned named-frame replacement remain separate contracts.

### Metadata completion

The earlier Metadata section already captures the complete supported field set,
aliases, keyword collection semantics, Phase-E type distinction and
document-local lifecycle. Rechecking current `MetadataManager`, integration
coverage and package lifecycle introduces no additional public option keys.

Exact public signatures:

```php
setMeta(array $meta): void
getMeta(): array
```

Supported canonical keys are exactly:

```text
title
subject
description
coverage
keywords
initial_creator
creator
language
creation_date
date
editing_cycles
editing_duration
generator
```

Input aliases remain `author -> creator` and
`initial_author -> initial_creator`. Unknown keys are silently ignored.

All singular imperative values are cast to string. `keywords` accepts either
one string (one keyword, never delimiter-split) or a PHP list containing only
strings. Any other keyword shape, including a non-list associative array or a
list containing a non-string, throws `InvalidArgumentException`. An empty
list removes all existing `meta:keyword` elements.

`getMeta()` omits supported fields absent from meta.xml. Keywords, when
present, are returned as a list in document order. Creator fields additionally
return their established aliases `author` and `initial_author`.

Mutation updates the first existing singular carrier or creates it below
`office:document-meta/office:meta`. The facade does not expose arbitrary
meta.xml element creation. If that expected root is absent, the current manager
does not invent a replacement root; mutation of a missing singular/keyword
carrier then has no insertion target.

**Disposition:** `setMeta()` and `getMeta()` are **KEEP / RECOMMENDED**.
The two author aliases are **KEEP / COMPATIBILITY**. MetadataManager is the
document-local implementation owner and is not a second normal end-programmer
API.

### Writer User Field completion

Exact recommended facade:

```php
setUserField(string $name, string $value): void
```

The earlier User Field section remains accurate and is now
**DOCUMENTED-COMPLETE** for the supported v1 binder.

The bounded source regions are exactly:

- `office:body/office:text` in content.xml;
- direct header/header-* and footer/footer-* children of each
  `style:master-page` in styles.xml.

The supported declaration type is exactly Writer
`office:value-type="string"`. A successful bind updates
`office:string-value` on every authoritative declaration belonging to the
supported logical field. It does not rewrite `text:user-field-get` display
text.

The complete public failure object is:

```php
final class UserFieldBindingException extends RuntimeException
{
    public const NOT_FOUND = 'NOT_FOUND';
    public const UNSUPPORTED_TYPE = 'UNSUPPORTED_TYPE';
    public const MALFORMED = 'MALFORMED';
    public const AMBIGUOUS = 'AMBIGUOUS';

    public function fieldName(): string;
    public function reason(): string;
}
```

An empty requested name is MALFORMED; an absent name is NOT_FOUND; unsupported
non-string declarations are UNSUPPORTED_TYPE; conflicting declarations/values
or other ambiguous logical states are AMBIGUOUS. Validation finishes before
mutation; focused tests protect atomic failure for these states.

Repeated binding is supported. `load()` restores the original template
declaration values. Binding survives classic `render()`, `save()`, and
reopen. Classic `assign()/render()` deliberately does not bind a same-named
Writer User Field.

**Disposition:** `setUserField()` and `UserFieldBindingException`'s stable
reason constants/accessors are **KEEP / RECOMMENDED**. `UserFieldBinder` and
`UserFieldAnalyzer` are **HIDE FROM USER DOCUMENTATION / Infrastructure**.

### setImage(): classic placeholder image insertion

Exact signature:

```php
setImage(string $key, string $imagePath, array $options = []): void
```

This is an immediate imperative operation. It does not stage an ImageElement
and does not require `render()`. It copies the source file to
`Pictures/<basename>`, then attempts replacement in both current content.xml
and styles.xml.

Complete recognized option vocabulary:

| key | default | current behavior |
| --- | --- | --- |
| `width` | none | explicit width; with no height derives height from intrinsic pixel ratio |
| `height` | none | explicit height; with no width derives width from intrinsic pixel ratio |
| `anchor` | `paragraph` | written directly as `text:anchor-type`; no facade validation |
| `wrap` | `none` | only `left|right|parallel` cause the historical child `style:wrap` node to be emitted; other values have no wrap-node effect |

With neither dimension, the legacy default is `5cm × 3cm`. With one
dimension, autosizing uses `getimagesize()` and the same historical
`(float) rtrim($dimension, 'cm')` conversion pattern as generated images;
the derived dimension is emitted in centimetres. This is not unit-aware
geometry. The safe documented proportional input is therefore a numeric
centimetre dimension.

The source path must exist or generic `Exception("Image file not found: ...")`
is thrown. The implementation does not provide an additional public
validation/error contract for unreadable/non-image data beyond the underlying
filesystem/`getimagesize()` behavior.

Replacement is intentionally narrow. The XPath targets `text:p` elements
whose **direct text node** contains `{{key}}`. For every match, the **entire
paragraph** is replaced by a new `text:p` containing a `draw:frame`; text or
inline structure elsewhere in that paragraph is not preserved. The generated
frame uses `draw:name="$key"`, the chosen anchor/dimensions and z-index 0,
with one `draw:image` referencing `Pictures/<basename>`.

If no placeholder matches, no dedicated not-found exception is thrown. The
image has nevertheless already been copied to the working Pictures directory.
Unknown option keys are ignored because the facade reads only the four keys
above.

**1.0 disposition:** **KEEP / RECOMMENDED as the simple classic placeholder
image convenience**, but document its paragraph-replacement and centimetre
autosizing limits explicitly. Use `ImageElement` when PHP owns a structured
image element and its semantic frame layout; use Writer-native Frame operations
when Writer owns an existing frame.

### replaceImageByName(): legacy named-frame replacement

Exact signature:

```php
replaceImageByName(string $name, string $imagePath, array $options = []): void
```

This operation targets existing `draw:frame[@draw:name=...]` objects in both
current content.xml and styles.xml. It copies the source to
`Pictures/<basename>` and updates the frame geometry plus the `xlink:href`
of each direct child `draw:image`.

Complete recognized options:

| key | default | current behavior |
| --- | --- | --- |
| `width` | `5cm` | replaces frame width |
| `height` | `3cm` | replaces frame height |

The defaults are established **before** the old one-dimensional proportional
branches. Consequently a caller supplying only width still receives the
default height `3cm`, and a caller supplying only height still receives the
default width `5cm`. The public method therefore does **not** proportionally
derive the missing dimension in normal option use. This legacy behavior is
directly protected by ARCH-05G characterization.

The source path must exist or a generic Exception is thrown. Unknown option
keys are ignored.

Target behavior is compatibility-sensitive:

- no matching named frame: no dedicated not-found exception; copied resource
  remains in the working package;
- exactly one matching frame in a DOM: update that frame;
- duplicate same-name frames in a DOM: legacy behavior updates **all** matching
  frames rather than failing ambiguity;
- a matching frame without a direct `draw:image`: width/height are still
  mutated, but no image child is created;
- other frame attributes/style/anchor/z-index are left intact by the low-level
  replacement service.

This is materially different from the Writer-native typed Frame
`replace-image` semantics used by Phase E. Phase E can preserve both
dimensions when none are supplied and derive the other dimension
proportionally when exactly one is supplied; it also participates in typed
target/preflight failure semantics. These APIs must not be documented as
aliases.

**1.0 disposition:** **KEEP / COMPATIBILITY** for
`replaceImageByName()`. For new Writer-owned template integration, teach the
typed `frame($name)` / mapped native-frame model where its supported operation
surface applies. Do not change the legacy 5cm × 3cm or duplicate-name behavior
during 1.0 final review.

### Protected image facade compatibility seams

The public image operations retain protected OdtTemplate wrappers used by the
legacy facade, notably `replaceImageInDom()` and
`replaceImageInNamedDom()`. These are **Compatibility/Extension** surface for
subclasses, not normal end-programmer APIs. Their protected visibility should
not be collapsed casually during future extraction/refactoring.

`FrameImageReplacementService::updateFrame()` is a low-level document service
used by named-frame and Writer-native execution. Although public in PHP, it is
**Infrastructure / HIDE FROM USER DOCUMENTATION**; direct callers would bypass
target resolution, resource copying and higher-level failure semantics.

### Completion result for this block

The three families are now closed for 01F:

```text
Metadata
  setMeta()/getMeta()                    KEEP / RECOMMENDED
  author aliases                         KEEP / COMPATIBILITY
  status                                 VERIFIED + DOCUMENTED-COMPLETE

Writer User Fields
  setUserField()                         KEEP / RECOMMENDED
  UserFieldBindingException              KEEP / RECOMMENDED failure contract
  analyzer/binder                        INFRASTRUCTURE / HIDDEN
  status                                 VERIFIED + DOCUMENTED-COMPLETE

Facade image operations
  setImage()                             KEEP / RECOMMENDED bounded convenience
  replaceImageByName()                   KEEP / COMPATIBILITY
  protected replacement wrappers         COMPATIBILITY / EXTENSION
  FrameImageReplacementService           INFRASTRUCTURE / HIDDEN
  status                                 VERIFIED + DOCUMENTED-COMPLETE
```

No implementation semantics were changed. In particular, the legacy named
replacement defaults and duplicate-name behavior remain intact, and the
Writer-native/Phase-E image semantics remain deliberately separate.


## API-family completion — Writer-native Targets: Bookmark, Section, Table, Frame

Status: **VERIFIED + DOCUMENTED-COMPLETE for the current Writer-native typed
target facade, target handles, descriptors, mutation operations, and their
public failure contracts**.

This family implements the Writer-owned side of the 1.0 mental model. PHP does
not reconstruct these objects from scratch: the user gives a native Writer
object a name and the engine resolves that identity in the current working
document.

### Common typed-target model

The facade entry points are:

```php
bookmark(string $name): BookmarkTarget
section(string $name): SectionTarget
table(string $name): TableTarget
frame(string $name): FrameTarget
```

All four are strict typed lookups. The same native name may independently name
different object types. Within one type, no match throws
`TargetNotFoundException`; multiple matching descriptors throw
`AmbiguousAddressableTargetException`. Malformed bookmarks additionally
throw `MalformedTargetException` during typed resolution.

The public structured resolution failure hierarchy is:

```php
abstract class AddressableTargetException extends RuntimeException
{
    public function targetType(): string;
    public function targetName(): string;
}

final class TargetNotFoundException extends AddressableTargetException {}
final class AmbiguousAddressableTargetException extends AddressableTargetException {}
final class MalformedTargetException extends AddressableTargetException {}
```

Targets are **identity-backed handles, not captured DOM nodes**. `name()`
returns the stored identity; `descriptor()` resolves again against the
handle's own current `OdtDocumentContext`. Consequently a handle observes a
replacement working DOM if its identity still exists and fails deterministically
if `load()` or `refresh()` removes that identity. Handles from separate
OdtTemplate instances do not share document state.

`AbstractAddressableTarget` and `TypedTargetResolver` are public PHP classes
but are **Infrastructure / HIDE FROM NORMAL USER DOCUMENTATION**. The normal
entry point is the OdtTemplate facade.

### BookmarkTarget — bounded Writer range text replacement

Public surface:

```php
name(): string
type(): string                         // "bookmark"
descriptor(): BookmarkDescriptor
replaceText(string $value): self
```

`BookmarkDescriptor` is an immutable snapshot with:

```php
name(): string
documentPart(): string
hasStart(): bool
hasEnd(): bool
topology(): string
text(): ?string
diagnostics(): array
toArray(): array
```

Stable topology constants:

```text
collapsed
inline
paragraph_spanning
list_spanning
table_spanning
mixed_block
malformed
```

Current bookmark inspection/typed mutation is content.xml scoped.
`replaceText()` deliberately supports only a safely bounded inline textual
range. Start and end markers must be the unique paired markers and must share
one supported parent text context: `text:p`, `text:h`, or `text:span`.
The selected payload must be either direct text nodes or exactly one
`text:span` containing text-only children. In the latter case the span and
its attributes are preserved and only its textual payload is replaced.

The bookmark markers themselves remain intact, so identity survives repeated
replacement and subsequent inspection. XML-special characters in the supplied
PHP string are inserted as literal text, not parsed markup. The operation does
not invoke classic template processing.

The replacement value may not contain CR, LF or TAB, and may not contain
leading, trailing or repeated spaces. Collapsed bookmarks, empty paired
ranges, cross-paragraph/list/table/mixed ranges and structured inline payloads
are outside this first mutation boundary and fail atomically.

A valid-but-unsupported mutation throws:

```php
BookmarkMutationException
  bookmarkName(): string
  operation(): string       // current public operation: "replaceText"
  topology(): string
  reason(): string
```

Malformed identity resolution remains the separate
`MalformedTargetException` contract.

**Disposition:** `bookmark()`, BookmarkTarget read surface and
`replaceText()` are **KEEP / RECOMMENDED**. BookmarkDescriptor and
BookmarkMutationException are **KEEP / RECOMMENDED supporting contract**.
BookmarkMutationService is **Infrastructure / hidden**.

### SectionTarget — Writer-owned structured container

Public surface:

```php
name(): string
type(): string                           // "section"
descriptor(): SectionDescriptor
text(): string
nestedNamedObjects(): array
replaceContent(OdtElement $content): self
clone(): self
instantiate(array $values): self
instantiateMany(array $items): array
section(string $name): self
```

`SectionDescriptor` exposes:

```php
name(): string
documentPart(): string
childSummary(): array
nestedNamedObjects(): array
diagnostics(): array
toArray(): array
```

Current public SectionTarget resolution is deliberately **content.xml scoped**.
A same-named section in styles.xml/master-page content does not make the
ordinary facade Section ambiguous. The descriptor child summary reports
descendant paragraph, heading, list, table and frame counts.
`nestedNamedObjects()` exposes compact `NamedObjectReference` values for
nested sections, bookmarks, tables and frames.

`text()` is a conservative plain-text view in document order. It collects
textual block content, trims block lines, drops empty lines and joins retained
lines with `"\n"`. Images do not contribute text. It is a read view, not an
ODF serialization API.

#### replaceContent()

`replaceContent(OdtElement $content)` preserves the native
`text:section` container and its identity/attributes while replacing its
children. Accepted materialized top-level block forms are:

```text
text:p
text:h
text:list
table:table
draw:frame
```

A top-level frame is hosted in a paragraph to preserve text-flow legality.
Empty RichText can clear the section while keeping the section addressable.

Replacement is staged and validates same-type native identity collisions.
Nested named sections/bookmarks/tables/frames introduced by the replacement
must not collide with same-type identities outside the section or duplicate
one another; different object types may reuse a name. Bookmark pairing inside
the replacement is validated.

Resource-bearing content requires package ownership and exposed image assets;
the OdtTemplate facade supplies that package ownership. Resource preparation
and live-DOM mutation are rollback-aware. Inline-only/non-block
materialization and unsupported/resource-incoherent content fail atomically.

Failures specific to replacement use:

```php
SectionMutationException
  sectionName(): string
  operation(): string
  reason(): string
  conflictingType(): ?string
  conflictingName(): ?string
```

#### clone()

`clone()` clones the **unsuffixed prototype** and rewrites native identities
so the returned clone is uniquely addressable. The engine allocates the next
document-safe numeric suffix and rewrites identities inside the cloned subtree,
including sections, bookmark markers, tables, frames/custom shapes and
template-expression identities covered by the clone contract.

Calling `clone()` on an already suffixed clone is unsupported in the current
slice. Clone failures use `SectionCloneException`, exposing
`sectionName()` and `reason()`.

This is intentionally not the historical internal exact-clone operation that
can temporarily create duplicate names. Public SectionTarget::clone() is the
identity-safe rewritten operation.

#### instantiate()

`instantiate(array $values)` clones the section with rewritten identities and
binds clone-owned scalar/filter expressions. Caller keys use the unsuffixed
logical variable names; generated identity suffixes remain internal.

Binding values must be scalar or null and keys must be non-empty. Every
clone-owned scalar variable requires a supplied value; missing required values
fail. Extra supplied values are ignored. Null binds as the empty string.
Existing scalar filter semantics are reused. Conditions and foreach/control
expressions in this bounded instantiation path are explicitly unsupported.

Failures use:

```php
SectionInstantiationException
  sectionName(): string
  reason(): string
  variableName(): ?string
```

The operation is atomic: a failed bind does not leave the rewritten clone
inserted.

#### instantiateMany()

`instantiateMany(array $items): array` expands an ordered collection and is a
**terminal prototype operation**: each item is instantiated in caller order,
then the prototype is removed. An empty collection therefore removes the
prototype and returns an empty list.

Each item must be an array acceptable to the scalar instantiation semantics.
If any item fails, already-created collection instances are rolled back and
the prototype remains usable. Successful returned targets are ordered like the
input.

This is materially different from repeated `instantiate()`: singular
instantiation preserves the prototype; collection instantiation finalizes the
collection by removing it.

#### nested section targeting

`$section->section($name)` resolves a descendant section relative to the
current section instance. For generated outer instances, the caller continues
to use the prototype's logical nested name while the implementation resolves
the appropriate physical suffixed identity. No match throws
TargetNotFoundException; multiple local matches throw
AmbiguousAddressableTargetException.

Nested `instantiate()` and `instantiateMany()` remain scoped to that owner
instance, so clone families in separate outer instances are independent.

**Disposition:** the SectionTarget surface above is **KEEP / RECOMMENDED**.
SectionDescriptor, NamedObjectReference and the three public Section mutation/
clone/instantiation exceptions are **KEEP / RECOMMENDED supporting
contracts**. SectionReader, mutation/clone/instantiation/collection/removal
services and working-target resolver types are **Infrastructure / hidden**.

### TableTarget — native Writer table population

Public surface:

```php
name(): string
type(): string                         // "table"
descriptor(): TableDescriptor
populate(array $rows, array $options = []): self
```

`TableDescriptor` exposes:

```php
name(): string
documentPart(): string
rowCount(): int
columnCount(): ?int
containingSection(): ?string
diagnostics(): array
toArray(): array
```

Table descriptors can describe tables discovered in content.xml or styles.xml.
Typed identity is strict across that inspected table set. **Population itself
is narrower:** only a uniquely addressable content.xml table can be populated.

The complete population data contract is
`list<list<scalar|null>>`. The outer and every inner array must be PHP lists.
String cells may not contain newline or tab characters. Every supplied row
must have exactly the writable cell count of the authored mutable row
structure. Null is written as an empty string; other scalar values are cast to
string.

The complete option vocabulary is:

```php
['keepRows' => list<int>]
```

No other option key is accepted. `keepRows` defaults to `[]`. Its indices
refer to the **original authored ordinary rows**, not generated/current row
positions. Indices must be integers in range; duplicates normalize to one kept
source index.

Header rows are preserved and consume no data. Kept ordinary rows retain their
authored position/content. Mutable rows are replaced from the captured
authored source; surplus mutable rows shrink away, while growth clones an
authored mutable row. Repeated population deliberately reuses the originally
captured source rows, so generated rows do not accumulate and changing
`keepRows` between calls still refers to the authored source.

Supported mutable Writer rows are deliberately simple:

- no repeated mutable row;
- direct `table:table-cell` children only; covered/non-cell topology fails;
- no repeated or row/column-spanned mutable cells;
- no protected mutable cells;
- no formula/non-string typed mutable cell payload;
- each mutable cell has exactly one simple Writer paragraph;
- the scalar carrier is either direct text or one optional styled
  `text:span`; ambiguous fragmented/multiple formatted runs fail.

The authored row/cell/paragraph/span formatting is retained while the scalar
payload changes. All mutable source rows must have compatible authored
structure and writable-cell count. Non-empty input requires at least one real
mutable source row.

Population stages the table and commits atomically. Contract failures use:

```php
NativeTablePopulationException
  tableName(): string
  operation(): string       // "populate"
  reason(): string
```

**Disposition:** `table()`, TableTarget read surface and `populate()` are
**KEEP / RECOMMENDED** for Writer-owned tables. TableDescriptor and
NativeTablePopulationException are **KEEP / RECOMMENDED supporting contract**.
NativeTablePopulationService and logical-row/state helpers are
**Infrastructure / hidden**.

This remains distinct from `RichTable`: Writer owns the native table here;
PHP owns a generated RichTable.

### FrameTarget — typed Writer frame identity/read surface

Public surface in the current target class is intentionally small:

```php
name(): string
type(): string                         // "frame"
descriptor(): FrameDescriptor
```

`FrameDescriptor` exposes:

```php
name(): string
documentPart(): string
payloadType(): string
width(): ?string
height(): ?string
containingSection(): ?string
diagnostics(): array
toArray(): array
```

Frame inspection covers content.xml and styles.xml. `payloadType()` is the
bounded classification `image`, `text-box`, or `other`; a direct
draw:image takes precedence if both recognized child types are present.
Width/height are the current `svg:width` / `svg:height` strings or null.

**Important public-surface boundary:** current `FrameTarget` has no direct
`replaceImage()` method. Image replacement for Writer-owned frames currently
exists through the mapped/native-object Phase-E `replace-image` operation,
not through an invented fluent FrameTarget method. The legacy
`replaceImageByName()` facade is a separate compatibility API with different
dimension and ambiguity semantics.

Therefore the 1.0 target reference must not imply:

```php
$template->frame('Portrait')->replaceImage(...); // does not exist
```

The typed FrameTarget is nevertheless the recommended strict identity/read
handle and the descriptor is part of the public Writer-native model. The
Phase-E mapped frame action will be documented in the Mapping/Preflight/
Automation family with its own payload and failure semantics.

**Disposition:** `frame()`, FrameTarget and FrameDescriptor are
**KEEP / RECOMMENDED** for typed Writer-frame identity/inspection.
FrameImageReplacementService remains **Infrastructure / hidden**.

### Descriptor and reference serialization contracts

The four descriptor `toArray()` shapes are stable machine-readable supporting
contracts:

```text
BookmarkDescriptor
  type, name, document_part, has_start, has_end, topology, text, diagnostics

SectionDescriptor
  type, name, document_part, child_summary, nested_named_objects, diagnostics

TableDescriptor
  type, name, document_part, row_count, column_count,
  containing_section, diagnostics

FrameDescriptor
  type, name, document_part, payload_type, width, height,
  containing_section, diagnostics

NamedObjectReference
  type, name, document_part
```

Diagnostic object details belong to the Inspection-family audit; this section
records only that descriptor diagnostics are exposed and serialized.

### Completion result for Writer-native Targets

```text
COMMON
  OdtTemplate::bookmark/section/table/frame    KEEP / RECOMMENDED
  strict addressable failure hierarchy         KEEP / RECOMMENDED contract
  AbstractAddressableTarget/TypedTargetResolver INFRASTRUCTURE / HIDDEN

BOOKMARK
  BookmarkTarget + BookmarkDescriptor           KEEP / RECOMMENDED
  replaceText()                                 KEEP / RECOMMENDED bounded mutation
  BookmarkMutationException                     KEEP / RECOMMENDED failure contract
  status                                        VERIFIED + DOCUMENTED-COMPLETE

SECTION
  SectionTarget + SectionDescriptor             KEEP / RECOMMENDED
  text()/nestedNamedObjects()                   KEEP / RECOMMENDED read views
  replaceContent()                              KEEP / RECOMMENDED
  clone()                                       KEEP / RECOMMENDED
  instantiate()/instantiateMany()               KEEP / RECOMMENDED
  nested section()                              KEEP / RECOMMENDED
  Section mutation/clone/instantiation errors   KEEP / RECOMMENDED contracts
  implementation services                       INFRASTRUCTURE / HIDDEN
  status                                        VERIFIED + DOCUMENTED-COMPLETE

TABLE
  TableTarget + TableDescriptor                 KEEP / RECOMMENDED
  populate()                                    KEEP / RECOMMENDED
  NativeTablePopulationException                KEEP / RECOMMENDED failure contract
  implementation/state/structure services       INFRASTRUCTURE / HIDDEN
  status                                        VERIFIED + DOCUMENTED-COMPLETE

FRAME
  FrameTarget + FrameDescriptor                 KEEP / RECOMMENDED read/identity
  direct fluent image mutation                  NOT PRESENT
  mapped Phase-E replace-image                  separate Automation family
  low-level replacement service                 INFRASTRUCTURE / HIDDEN
  status                                        VERIFIED + DOCUMENTED-COMPLETE
```

No new API or 1.0 architecture is introduced by this audit. In particular,
FrameTarget has not been retrofitted with a convenience mutation, Section
collection semantics remain prototype-finalizing, and Table population remains
bounded to the already implemented simple scalar Writer-row model.


## API-family completion — Style API

Status: **VERIFIED + DOCUMENTED-COMPLETE for the current 1.0 style authoring
surface, option mappers/splitter, semantic extension boundary, and remaining
low-level serialization helper**.

STYLE-API-02 is already architecturally closed by its own A–I series. This 01F
pass does not redesign it; it reconciles that accepted baseline with current
source/tests and records the complete public-documentation boundary.

### Canonical 1.0 style model

There are three intentional levels:

1. normal application authoring through element-local friendly options and
   named style references;
2. document-local reusable paragraph style authoring through
   `$template->styles()`;
3. semantic style requirements/dependencies for custom structured elements.

There is no process-global style registry in the current architecture.
`HasStyles`, `LegacyStyleRegistry`, old StyleMapper registration/getter
facades and the protected generic `OdtTemplate::registerStyles(array)` were
intentionally retired by STYLE-API-02. Historical architecture documents that
describe them are evidence of the migration, not current API documentation.

### OdtTemplate::styles()

Exact facade:

```php
styles(): DocumentStyles
```

The returned `DocumentStyles` instance is stable for the OdtTemplate object,
but owns no detached style state. It resolves the current
`OdtDocumentContext` for every operation. A retained facade therefore follows
the document after `load()`/document replacement instead of leaking style
definitions from the previous logical document.

**Disposition:** **KEEP / RECOMMENDED**.

### DocumentStyles::defineParagraph()

Exact signature:

```php
defineParagraph(string $name, array $options): void
```

This is the sole current generic document-style definition method. No symmetric
`defineText()`, `defineTable()`, `defineFrame()` or similar API exists or
is implied for 1.0.

The mixed option array is split in paragraph context. The effective friendly
paragraph keys are:

```text
align
text-align
text-indent
line-height
margin
margin-top
margin-right
margin-bottom
margin-left
padding
padding-top
padding-right
padding-bottom
padding-left
border
border-top
border-right
border-bottom
border-left
keep-with-next
break-before
break-after
writing-mode
number-lines
line-number
tab-stops
```

The effective friendly text keys are:

```text
bold
weight
font-weight
italic
font-style
underline
text-decoration
text-line-through
color
font-size
font-family
font-variant
monospace
style:text-position
```

In paragraph context, `align` is normalized to paragraph
`text-align`. Native-prefixed values classified by the splitter are retained
as advanced escape-hatch input according to their property responsibility.

The resulting style is a common paragraph-family definition in styles.xml with
parent style `Standard`. Paragraph options become
`style:paragraph-properties`; text options become
`style:text-properties`. A discovered font-family dependency is registered
and materialized into the current document's font-face declarations.

An equivalent same-name document-local semantic definition is idempotent.
A different same-name semantic definition conflicts and throws
`LogicException`. If an authored same-name paragraph style already exists in
the ODT, authored document data remains authoritative and is not overwritten by
the generated definition materializer.

A generated style is immediately materialized; `save()` is not required to
make it part of the working styles DOM. Repeated save is characterized as
stable.

Named style **reference** remains distinct from named style **definition**:

```php
new Paragraph('Heading')                    // reference existing style
$template->styles()->defineParagraph(...)   // define document-local style
```

A reference alone does not register or synthesize a style.

**Disposition:** **KEEP / RECOMMENDED**.

### DocumentStyles::setDocumentDefaults()

Exact signature:

```php
setDocumentDefaults(array $settings): void
```

Accepted top-level keys are:

```php
[
    'text' => array,
    'paragraph' => array,
]
```

Both are optional and default to empty arrays. If either supplied value is not
an array, `InvalidArgumentException` is thrown.

The operation modifies the named paragraph style `Standard` in styles.xml.
If that style does not exist it is created below `office:styles`; if
`office:styles` itself is absent, `RuntimeException` is thrown.

Only explicitly supplied mapped attributes are merged. Existing authored
properties not addressed by the call are preserved, so Writer inheritance from
Standard continues to apply to child paragraph styles. A supplied text
font-family also materializes its font-face dependency.

The effective text option semantics are those of
`StyleMapper::mapTextStyleOptions()`; effective paragraph semantics are those
of `StyleMapper::mapParagraphStyle()`. During final property merge only
mapped `fo:*` and `style:*` attributes are accepted and every mapped value
must be scalar; unsupported namespaces/non-scalar mapped properties throw
`InvalidArgumentException`.

This API belongs conceptually to **Document Defaults** as well as Styles. It is
recorded here so the Style API is mechanically complete; the Page/Document
Layout/Defaults audit must reference rather than rediscover it.

**Disposition:** **KEEP / RECOMMENDED**.

### Complete text option mapping

`StyleMapper::mapTextStyleOptions(array $options): array` maps:

| input | output / rule |
| --- | --- |
| native `fo:*`, `style:*` | preserved first |
| `bold` truthy | `fo:font-weight=bold` |
| `italic` truthy | `fo:font-style=italic` |
| `font-weight` non-empty | `fo:font-weight` |
| `font-style` non-empty | `fo:font-style` |
| `underline` truthy | solid/single/auto underline attributes |
| `text-decoration` non-empty | also establishes underline; value `line-through` additionally establishes strike-through |
| `color` | `fo:color` |
| `background-color` | `fo:background-color` |
| `font-size` | friendly size mapping or lower-cased pass-through |
| `font-family` | both `style:font-name` and `fo:font-family` |
| `text-line-through` truthy | `style:text-line-through-style=solid` |
| `style:text-position` | preserved |
| `font-variant=small-caps` | `fo:font-variant=small-caps` |
| `monospace === true` | font name/family `Courier New` |

Friendly font-size values are exactly
`xx-small=6pt`, `x-small=7pt`, `small=9pt`, `medium=11pt`,
`large=13pt`, `x-large=15pt`, `xx-large=17pt`.
Other non-empty values are lower-cased and passed through.

Note the splitter/mapping distinction: `background-color` is supported by the
text mapper, but `StyleOptionSplitter` does not classify it as a friendly text
key in paragraph context. Direct Paragraph text styles can therefore use it as
documented in the Structured Content audit, while a mixed RichText/
defineParagraph convenience array follows splitter responsibility.

### Complete paragraph option mapping

`StyleMapper::mapParagraphStyle(array $options): array` maps the friendly
keys:

```text
margin-left/right/top/bottom -> fo:margin-*
text-align                   -> fo:text-align
text-indent                  -> fo:text-indent
line-height                  -> fo:line-height
background-color             -> fo:background-color
keep-with-next               -> fo:keep-with-next
keep-together                -> fo:keep-together
widows                       -> fo:widows
orphans                      -> fo:orphans
break-before/after           -> fo:break-before/after
writing-mode                 -> style:writing-mode
padding[-side]               -> fo:padding[-side]
border[-side]                -> fo:border[-side]
number-lines                 -> style:number-lines
line-number                  -> style:line-number
```

`tab-stops` is a list of definitions; each entry emits
`style:position = position . "cm"` and
`style:type = alignment ?? "left"`. There is no additional alignment or
position validation in this mapper.

Every other key is passed through unchanged. That permissive fallback is an
**Advanced/Compatibility native escape hatch**, not a promise that arbitrary
CSS-like keys have ODF meaning.

The splitter does not classify `keep-together`, `widows`, `orphans` or
`background-color` as friendly paragraph keys, although the paragraph mapper
recognizes them. They can reach the mapper through direct/compatibility paths;
this mismatch is current behavior, not a reason to broaden 1.0 syntax during
01F.

### Table style mapping

`StyleMapper::mapTableStyleOptions(array $options): array` has a deliberately
strict friendly vocabulary:

```text
width          -> style:width
relative-width -> style:rel-width
alignment      -> table:align
```

Native `fo:*`, `style:*` and `table:*` keys pass through as the advanced
escape hatch. Any other friendly key throws `InvalidArgumentException`.

This mapping is consumed by the already documented RichTable API; it is not a
second table-authoring API.

### Table-cell style mapping

`StyleMapper::mapTableCellStyleOptions(array $options): array` recognizes:

```text
background-color | background -> fo:background-color
padding[-side]                 -> fo:padding[-side]
border[-side]                  -> fo:border[-side]
vertical-align                 -> style:vertical-align
align | text-align             -> fo:text-align
weight                         -> fo:font-weight
color                          -> fo:color
```

Native `fo:*` and `style:*` pass through. `vertical-align` must be a
string and normalizes by trim/lowercase to exactly
`top|middle|bottom|automatic`; otherwise `InvalidArgumentException` is
thrown.

Unlike table-level mapping, unknown friendly cell keys are silently ignored.
That is existing compatibility behavior and should not be presented as a
general extension mechanism.

### Frame style mapping

`StyleMapper::mapFrameStyleOptions(array $options): array` recognizes:

```text
background-color | fo:background-color
border[-side]
corner-radius-x | rx
corner-radius-y | ry
padding[-side]
fill | draw:fill
fill-color | draw:fill-color
wrap-influence
allow-overlap
vertical-pos
vertical-rel
horizontal-pos
horizontal-rel
```

Background color additionally establishes `draw:fill=solid` and
`draw:fill-color` unless already set in the mapped result. The position/
relation and overlap/wrap-influence aliases map to their current ODF
style/draw/loext attributes.

Unknown keys pass through unchanged. This is a permissive
**Advanced/Compatibility** mapper. Semantic DrawingLayout remains the
recommended positioning model where applicable; this mapper must not be used
to redefine DrawingLayout's validated contract.

### Image style mapping

`StyleMapper::mapImageStyleOptions(array $options): array` recognizes:

- non-empty `width` / `height` -> `svg:width` / `svg:height`;
- `wrap` only when exactly `none|left|right|run-through`;
- `align` only when exactly `left|right|center|absolute`, retained as
  non-ODF helper key `align`;
- `anchor` only when exactly `paragraph|page|char|as-char`;
- non-empty `horizontal-pos`, `horizontal-rel`, `vertical-pos`,
  `vertical-rel` -> corresponding style attributes.

Invalid enumerated values are silently omitted. Unknown keys are ignored.
This mapper is **Compatibility/Infrastructure for historical image option
projection**, not the canonical semantic frame-layout API. The ImageElement
audit remains authoritative for effective public image behavior.

### StyleOptionSplitter

Exact public helper:

```php
StyleOptionSplitter::split(array $options, string $context = 'paragraph'): array
```

Return shape is always:

```php
[
    'cell' => [],
    'paragraph' => [],
    'text' => [],
]
```

Supported contexts are `paragraph` and `table-cell`. It classifies the
friendly text/paragraph/cell keys used by the structured convenience APIs and
routes native-prefixed keys according to the current ODF responsibility
rules. `align` normalizes to paragraph `text-align` when routed there.

**Disposition:** **KEEP / ADVANCED utility**. Normal users should supply style
options to Paragraph/RichText/RichTableCell/DocumentStyles rather than invoke
the splitter directly.

### Style identity/CSS helpers

Remaining public stateless StyleMapper helpers are:

```php
generateStyleName(array $style): string
generateParagraphStyleName(): string
parseInlineStyle(string $css): array
splitCssProperties(array $rawCss): array
```

`generateStyleName()` removes only `align` and `style-name`, sorts
remaining keys and returns `auto_` plus the first eight hex characters of
the MD5 of the JSON representation. It is deterministic for equivalent
top-level key/value arrays after key sorting.

`generateParagraphStyleName()` takes **no arguments** and returns
`para_` plus eight random hexadecimal characters. Despite historical
wording, it is not content-derived/deterministic.

`parseInlineStyle()` is a small CSS-declaration lexer: split on semicolons,
retain rules containing a colon, split each at the first colon, lowercase/trim
the property name and trim the value. It does no CSS validation/cascade.

`splitCssProperties()` recognizes a bounded CSS-like text/paragraph subset
and returns `[$textStyle, $paragraphStyle]`; unrecognized keys are dropped.
It exists primarily for HtmlImporter compatibility.

**Disposition:** style-name generators are **Advanced/Infrastructure identity
helpers**; CSS helpers are **Advanced/Importer compatibility**. They are not
the primary end-programmer style API.

### StyleRequirement — canonical custom-element extension value

`StyleRequirement` is the semantic style description consumed by the
document-local pipeline. The current architecture closeout makes
`getOwnStyleRequirements()` the canonical custom-element style hook and
collector-owned `ownedElements()` traversal the canonical recursion model.

The StyleRequirement public constructor/accessors/constants are therefore
**KEEP / ADVANCED extension contract**, not normal application authoring.
A custom element may produce its own semantic requirements; application code
should normally use element options or DocumentStyles.

The stable conceptual dimensions are:

- kind: definition or reference;
- scope: common or automatic where applicable;
- ODF family;
- document part;
- style name;
- optional parent style name;
- grouped ODF properties.

`StyleContext`, requirement collectors/materializers/resolvers and
font-dependency discovery/materialization are document infrastructure. Their
public PHP methods are **HIDE FROM NORMAL USER DOCUMENTATION** unless a future
explicit low-level extension API promotes them.

### FontFaceRequirement

`FontFaceRequirement` and its conflict/resolution machinery support semantic
style dependencies. They are **Infrastructure / HIDDEN** for normal
end-programmer documentation. Users request a font through supported
`font-family` style options; they do not normally construct or materialize
font-face requirements themselves.

### StyleContext

Current source confirms the STYLE-API-02 closeout:

- document-local semantic definitions/references;
- resolution against authored current-document styles first, then
  document-local definitions;
- no process-global paragraph/text fallback;
- graphic/fill compatibility stores remain bounded internal compatibility;
- reset/snapshot/restore support document lifecycle and rollback.

Although `StyleContext` has many public PHP methods, it is
**Infrastructure / HIDE FROM USER DOCUMENTATION**. Public visibility supports
engine collaboration/testing and does not create a parallel application style
API.

### StyleWriter

Current StyleWriter has been narrowed to exactly one public method:

```php
StyleWriter::writeColumnStyles(DOMDocument $doc, array $columnWidths): array
```

For each list entry it creates an automatic table-column style named
`co0`, `co1`, ... with `style:column-width` equal to the supplied value
and returns those names in order. If `office:automatic-styles` is missing it
creates that container.

No width validation, collision detection or replacement semantics are added by
this helper. It serializes explicit data into the supplied DOM.

**Disposition:** **Infrastructure / HIDE FROM NORMAL USER DOCUMENTATION**.
It is not a registry and not an application-facing style facade.

### Retired APIs must not reappear in 1.0 documentation

The current source/accepted STYLE-API-02 baseline intentionally retired:

- `HasStyles`;
- `LegacyStyleRegistry`;
- StyleMapper paragraph/text register/getter APIs and mutable registry facade;
- process-global paragraph/text reference fallback;
- redundant transitive paragraph/text/table style getter families;
- protected generic `OdtTemplate::registerStyles(array)`.

Historical samples/docs mentioning those APIs describe older architecture.
They must not be copied into the future website API reference as compatibility
APIs that still exist.

Bounded graphic/resource compatibility getters that remain on OdtElement are
already classified in the OdtElement audit. They are not a second style
ownership model.

### Style API completion result

```text
APPLICATION AUTHORING
  element-local friendly style options          KEEP / RECOMMENDED
  named existing Writer style references        KEEP / RECOMMENDED

DOCUMENT STYLE AUTHORING
  OdtTemplate::styles()                          KEEP / RECOMMENDED
  DocumentStyles::defineParagraph()              KEEP / RECOMMENDED
  DocumentStyles::setDocumentDefaults()          KEEP / RECOMMENDED
  status                                         VERIFIED + DOCUMENTED-COMPLETE

CUSTOM ELEMENT EXTENSION
  getOwnStyleRequirements() / StyleRequirement   KEEP / ADVANCED
  ownedElements() collector traversal            KEEP / ADVANCED
  typed resource/dependency hooks                KEEP / ADVANCED

STATELESS UTILITIES
  StyleMapper mapping methods                    ADVANCED / INFRASTRUCTURE
  StyleOptionSplitter::split()                   KEEP / ADVANCED
  style identity helpers                         ADVANCED / INFRASTRUCTURE
  CSS helpers                                    ADVANCED / IMPORTER COMPATIBILITY

INTERNAL OWNERSHIP/MATERIALIZATION
  StyleContext                                   INFRASTRUCTURE / HIDDEN
  collectors/materializers/resolvers             INFRASTRUCTURE / HIDDEN
  FontFaceRequirement pipeline                   INFRASTRUCTURE / HIDDEN
  StyleWriter::writeColumnStyles()                INFRASTRUCTURE / HIDDEN

RETIRED — DO NOT DOCUMENT AS CURRENT API
  HasStyles
  LegacyStyleRegistry
  global StyleMapper registries
  old paragraph/text registry getters
  protected generic OdtTemplate::registerStyles()
```

No style architecture or behavior was changed by this 01F audit. The current
source confirms the accepted STYLE-API-02I model: normal users style elements,
define reusable generated paragraph styles through the document facade, and
let document-local semantic ownership/materialization handle persistence.


## API-family completion — Page / Document Layout / Defaults

Status: **VERIFIED + DOCUMENTED-COMPLETE for the current 1.0 page-geometry,
paragraph-flow, page-owned-content preservation, and document-default
surfaces**.

This audit deliberately preserves the native ownership boundaries established
by PAGE-FLOW-01:

```text
paragraph flow semantics
        !=
master-page/page-style identity
        !=
referenced page-layout geometry
        !=
Writer-computed physical pagination
```

No broad page-style authoring API or PHP pagination model is introduced.

### PageLayoutOdtTemplate

`PageLayoutOdtTemplate extends OdtTemplate` is the current public advanced
facade for mutating selected geometry of an **existing Writer-authored page
layout**.

Public additions:

```php
setPageMargins(
    string $top,
    string $right,
    string $bottom,
    string $left,
    string $masterPage = 'Standard'
): static

setPageLayout(
    array $options,
    string $masterPage = 'Standard'
): static
```

Both mutate the current working styles.xml immediately and return `$this`
for fluent use.

**Disposition:** **KEEP / ADVANCED**. This is a useful supported facade, but
the template-first model remains preferred for stable page design.

### setPageMargins()

`setPageMargins()` is a convenience wrapper over `setPageLayout()` and
passes exactly:

```text
margin-top
margin-right
margin-bottom
margin-left
```

The fifth argument selects the Writer master page by native
`style:name`; default is `Standard`.

The method intentionally dispatches through `$this->setPageLayout(...)`
rather than directly to PageLayoutManager. That polymorphic seam is covered by
integration tests and is part of subclass compatibility.

There is no length parser or unit validation in this facade/manager. Values
are strings; after trim they must only be non-empty. Their ODF/Writer validity
is otherwise the caller's responsibility.

**Disposition:** **KEEP / ADVANCED**.

### setPageLayout()

Complete supported friendly option vocabulary:

```text
margin-top
margin-right
margin-bottom
margin-left
page-width
page-height
orientation
```

The first six values are trimmed strings and are written respectively as:

```text
fo:margin-top
fo:margin-right
fo:margin-bottom
fo:margin-left
fo:page-width
fo:page-height
```

An explicitly supplied empty/whitespace-only value for any of those keys
throws `RuntimeException`.

`orientation` is trim/lowercase normalized and must be exactly
`portrait` or `landscape`; otherwise `RuntimeException` is thrown. It is
written as `style:print-orientation`.

**Current compatibility detail:** unknown option keys are silently ignored.
This is existing behavior, not an open-ended page-layout extension contract.
Future user documentation should list only the seven supported keys above.

The API does **not** automatically swap page width/height when orientation
changes. Size and orientation are independent supplied properties. For an A4
landscape request, for example, the caller supplies the intended width/height
as well as `orientation=landscape` if both changes are desired.

### Native resolution and structural failures

The supplied `$masterPage` identifies an existing
`style:master-page` in styles.xml. The implementation then follows its
`style:page-layout-name` reference to the corresponding
`style:page-layout` and finally mutates its
`style:page-layout-properties`.

Failures are explicit `RuntimeException` when:

- the requested master page does not exist;
- the master page has no page-layout reference;
- the referenced page layout does not exist;
- the page layout has no `style:page-layout-properties`;
- a supported geometry value is empty;
- orientation is not portrait/landscape.

The manager does not synthesize missing master pages, page layouts or
properties nodes. This is **mutation of authored geometry**, not page-style
definition.

### PageLayoutManager

Public PHP surface:

```php
__construct(OdtDocumentContext $context)
setMargins(
    string $top,
    string $right,
    string $bottom,
    string $left,
    string $masterPage = 'Standard'
): void
setLayout(array $options, string $masterPage = 'Standard'): void
```

Its semantics are the implementation semantics described above.

**Disposition:** **Infrastructure / HIDE FROM NORMAL USER DOCUMENTATION**.
Normal callers use PageLayoutOdtTemplate. Public visibility does not establish
a second application-facing page-layout API.

### Page style/master-page identity is not page layout

PAGE-FLOW-01 established the current semantic baseline:

```text
style:master-page
    = page/master style identity, succession and page-owned content

style:page-layout
    = referenced page/header/footer geometry
```

Therefore `setPageLayout()` must not be documented as defining, selecting or
assigning a Writer page style. It only follows a selected existing master
page's reference and mutates bounded geometry.

Likewise:

```text
fo:break-before / fo:break-after
    !=
style:master-page-name
```

A page break is paragraph-flow intent; requesting a particular page style is a
different native operation.

There is currently no approved public:

```text
pageStyle()
masterPage()
definePageStyle()
setPageStyle()
assignPageStyle()
setNextPageStyle()
```

API.

Programmatic page/master-style reference, definition, mutation and transition
remain the explicitly documented future capability
`PAGE-STYLE-AUTHORING-01`. 01F must not infer one from the geometry facade.

### Paragraph flow — current 1.0 baseline

Generated paragraph styles can express/preserve the native flow properties:

```text
keep-with-next -> fo:keep-with-next
keep-together  -> fo:keep-together
widows         -> fo:widows
orphans        -> fo:orphans
break-before   -> fo:break-before
break-after    -> fo:break-after
```

These are style values; the engine does not interpret them to predict page
placement. Writer/LibreOffice computes actual pagination.

The exact mapper behavior/options are already captured in the Style and
Paragraph audits. This section records their **page-flow semantics**, not a
second API.

Sections likewise do not gain generic `keepSectionOnPage()` or
break/page-style ownership. Their contained paragraphs/tables/lists/frames
retain their own native flow semantics.

### Authored page relationships and page-owned content

The 1.0 contract preserves Writer-authored relationships such as:

- paragraph style -> `style:master-page-name`;
- master page -> `style:next-style-name`;
- First Page -> Standard succession;
- first/left/right header/footer variants where authored.

The engine does not normalize these into one page model.

Header/footer content owned by `style:master-page` participates in the
normal document/template lifecycle. PAGE-FLOW characterization establishes
that page-owned content can participate in scalar processing and established
structured/resource paths while native Writer fields such as page-number
fields remain preserved.

There is no separate header/footer template language or general header/footer
authoring facade in the current 1.0 API.

A bounded historical finding remains: generic ImageElement materialization in
page-owned header content was not Writer-visible in the PAGE-FLOW regression,
while the established `setImage()` path was. That finding is not silently
resolved or generalized by this audit.

### Document defaults

The current source contains a real public document-default operation:

```php
$template->styles()->setDocumentDefaults(array $settings): void
```

Its exact accepted shape and StyleMapper behavior are documented in the Style
API audit. Semantically it mutates/creates the Writer paragraph style
`Standard` in styles.xml and merges only explicitly supplied text/paragraph
properties, preserving unspecified authored properties and native inheritance.

This creates an important source/documentation distinction:

- `FUTURE_DEVELOPMENT.md` still describes `DOCUMENT-DEFAULTS-01` as
  “DEFERRED UNLESS DEPENDENCY EMERGES” and says no generic default API should
  be invented;
- current source/tests already provide the narrower
  `DocumentStyles::setDocumentDefaults()` API that operates specifically
  through Writer's `Standard` paragraph style.

For 1.0, **current source and tests are authoritative**. The implemented
bounded operation is therefore documented and retained; the deferred future
topic should be read as the broader unresolved question of ODF
`style:default-style`, LibreOffice/application defaults, authored base
styles, page defaults and precedence—not as evidence that the existing
`setDocumentDefaults()` method is absent.

No public `setDefaultFont()`, generic ODF default-style API, or page-layout
default API is inferred.

**Disposition:** `DocumentStyles::setDocumentDefaults()` =
**KEEP / RECOMMENDED bounded document-default API**. Broader defaults
architecture remains future work.

### Template-first ownership guidance

The supported 1.0 division of responsibility is:

```text
Writer / LibreOffice template owns:
  page/master style identity
  succession
  stable headers/footers
  multi-column/page composition
  durable branding/layout structures

PHP may:
  mutate selected existing page-layout geometry
  express generated paragraph flow semantics
  modify bounded Standard paragraph defaults
  preserve/process content within authored page-owned structures

Writer layout engine owns:
  physical pagination
  page assignment consequences
  actual line/page fitting
```

This is why PageLayoutOdtTemplate remains an advanced facade rather than being
expanded into a general page composition framework.

### Documentation discrepancy found

`docs/advanced/page-layout.md` correctly describes the current public
geometry API and native master-page -> page-layout relationship, but its
“Why this is a separate class” section still says that PageLayoutOdtTemplate
“also overrides list-indentation adjustment”.

That statement is stale. ARCH-07G and current source confirm that the unrelated
`adjustBulletIndentation()` override was removed; the subclass now contains
only the two page-layout convenience methods. The stale guide text should be
corrected during the 01F documentation-cleanup pass rather than treated as
current behavior.

### Completion result

```text
PAGE GEOMETRY
  PageLayoutOdtTemplate                     KEEP / ADVANCED
  setPageMargins()                          KEEP / ADVANCED
  setPageLayout()                           KEEP / ADVANCED
  PageLayoutManager                         INFRASTRUCTURE / HIDDEN
  status                                    VERIFIED + DOCUMENTED-COMPLETE

PARAGRAPH PAGE FLOW
  keep-with-next / keep-together
  widows / orphans
  break-before / break-after                KEEP / RECOMMENDED style semantics
  physical pagination                       WRITER OWNERSHIP

DOCUMENT DEFAULTS
  styles()->setDocumentDefaults()           KEEP / RECOMMENDED, bounded
  broader ODF/default-style abstraction     FUTURE DEVELOPMENT

PAGE STYLE / MASTER PAGE AUTHORING
  authored identity/relationships           PRESERVE
  generated definition/assignment/
  transition API                            FUTURE DEVELOPMENT
  PAGE-STYLE-AUTHORING-01                   retained future capability

PAGE-OWNED CONTENT
  existing scalar/structured lifecycle      PRESERVE
  separate header/footer language/API       NOT PRESENT / NOT NEEDED FOR 1.0
```

No new 1.0 architecture was introduced. The current API remains deliberately
native-first: Writer owns page composition and pagination; PHP has bounded
geometry, flow and Standard-style default controls.


## API-family completion — Inspection, Template Contract, and Mapping

Status: **VERIFIED + DOCUMENTED-COMPLETE for the current public 1.0
Inspection, source Template Contract, and optional Mapping/Resolution model**.

These three families are intentionally documented together because they form
one non-mutating information pipeline while retaining distinct semantic views:

```text
current Working Document
    -> inspect()
    -> DocumentInspection

original authored source
    -> inspectTemplateStructure()
    -> physical expression topology

original authored source
    -> inspectTemplate()
    -> TemplateContract
          +
application mapping definition/data
          -> mapping validation/resolution
```

None of these operations authorizes document mutation by inspection alone.

### Inspection view 1 — OdtTemplate::inspect()

Exact facade:

```php
inspect(): DocumentInspection
```

This inspects the **current Working Document**, not the immutable original
source. Every call returns a new read-only snapshot and exposes no DOM nodes.
It inspects both current content.xml and styles.xml according to
DocumentInspector's bounded native-object coverage.

`DocumentInspection` public surface:

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

The collection methods return descriptor lists. Singular lookup is a
convenience lookup in the snapshot and returns null when no matching descriptor
is present; strict mutation/addressing semantics belong to the typed target
facade documented separately.

The four descriptor contracts are already completed in the Writer-native
Targets audit. `DocumentInspection::toArray()` serializes the four descriptor
collections plus diagnostics.

`InspectionDiagnostic` exposes:

```php
code(): string
severity(): string
message(): string
targetType(): ?string
targetName(): ?string
toArray(): array
```

Stable severities are exactly `warning` and `error`.

**Disposition:** `inspect()`, DocumentInspection, the native descriptors and
InspectionDiagnostic are **KEEP / RECOMMENDED** for tooling/native working
document introspection. DocumentInspector is **Infrastructure / hidden**.

### Inspection view 2 — inspectTemplateStructure()

Exact facade:

```php
inspectTemplateStructure(): TemplateStructureInspection
```

This is deliberately different from `inspect()`: it inspects the **original
source content.xml**, not the current Working Document, and focuses on visible
template-expression topology/normalization safety rather than native object
inventory.

`TemplateStructureInspection` exposes:

```php
valid(): bool
repairable(): array
unsafe(): array
expressionsByVariable(string $name): array
expressionsInScope(string $scope): array
toArray(): array
```

The contained `TemplateExpressionDescriptor` exposes raw text, expression
kind, variable/filter information, physical scope, fragment count/split state,
classification and physical-normalization state through its public accessors
and `toArray()`. Style/bookmark/native-owner evidence is represented in the
serialized descriptor rather than as mutable document objects.

`TemplateStructureDiagnostic` is the associated immutable diagnostic value,
including code, severity, message, classification, repairability, optional
expression and scope.

`TemplateStructureNormalizationResult` exposes `changed()` and
`toArray()`; it belongs to the lower-level normalization/repair machinery,
not the primary inspection facade.

**Disposition:** `inspectTemplateStructure()` and its inspection DTOs are
**KEEP / ADVANCED tooling/diagnostic API**. TemplateStructureInspector,
projector and normalizer implementation services are **Infrastructure /
hidden** unless an integration deliberately works at that low level.

### Inspection view 3 — OdtTemplate::inspectTemplate()

Exact facade:

```php
inspectTemplate(): TemplateContract
```

This is the canonical **semantic source-template contract**. It reads the
original authored source DOMs from the package, so current Working Document
mutations, render operations and imperative target changes do not redefine the
contract.

Its source regions are bounded to:

- `office:body/office:text` in content.xml;
- direct header/header-* and footer/footer-* content under each
  `style:master-page` in styles.xml.

Coverage explicitly reports excluded parts:
`meta.xml`, `settings.xml`, `META-INF/manifest.xml`, and embedded
objects.

This distinction is foundational:

```text
inspect()          = current native document state
inspectTemplate()  = original authored semantic contract
```

### TemplateContract

Stable contract version:

```php
TemplateContract::CONTRACT_VERSION === 1
```

Public surface:

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

Serialized top-level shape:

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

The contract preserves the architecture distinction:

```text
authored evidence
    -> interpreted template meaning
    -> logical data dependency
```

A native object name by itself is **not** an application-data dependency.

### BindingDescriptor

Public semantic fields/accessors:

```php
kind()
rawText()
variableName()
filterName()
filterOption()
supportState()
provenance()
dependencyId()
toArray()
```

Bindings represent visible scalar/filtered/special semantics and supported
native field evidence. A dependency id is present only when the binding
participates in a supported logical dependency.

### ControlDescriptor

Public surface:

```php
id()
kind()
representation()
supportState()
scope()
createdScope()
carrierNativeObjectId()
toArray()
```

Controls remain distinct from bindings. The contract can represent classic
controls and recognized declarative/native Section controls without claiming
that every recognized representation has identical execution semantics.

### DependencyDescriptor and DataScopeDescriptor

`DependencyDescriptor` exposes:

```php
id()
kind()
name()
scope()
path()
evidenceIds()
toArray()
```

The important dependency kinds in the current contract are VALUE and
COLLECTION.

`DataScopeDescriptor` exposes the stable scope constants:

```text
ROOT
COLLECTION_ITEM
```

and:

```php
root()
collectionItem(...)
id()
kind()
parentId()
collectionDependencyId()
pathPrefix()
dependencyPath(string $name, bool $collection = false)
toArray()
```

This is the source of semantic paths such as:

```text
name
experience[]
experience[].company
experience[].projects[]
experience[].projects[].title
```

The path is template data-scope identity, not a DOM path.

### NativeObjectDescriptor and SourceProvenance

`NativeObjectDescriptor` exposes kind, optional native name, provenance,
stable semantic id, owner-id chain and `toArray()`. Current source-native
families include Section, Bookmark, Table and Frame evidence.

`SourceProvenance` exposes:

```php
evidenceId()
sourcePart()
regionKind()
regionOwner()
carrierKind()
representationKind()
sourceOrder()
physicalScope()
nativeOwnerChain()
toArray()
```

This is explanation/provenance data. It deliberately does not expose XPath or
mutable DOM nodes.

### Contract coverage, capabilities and diagnostics

`TemplateContractCoverage` exposes `inspectedRegions()`,
`excludedParts()`, and `toArray()`.

`TemplateContractCapabilities` has stable readiness values:

```text
READY
LIMITED
BLOCKED
NOT_APPLICABLE
```

and `readiness(string $capability): ?string` plus `toArray()`.

Current inspector readiness includes the semantic capabilities used by the
authoring/mapping pipeline, including inspection, dependency mapping and native
field binding as applicable. Callers must not reinterpret LIMITED/BLOCKED as
READY.

`TemplateContractDiagnostic` exposes code, severity, message, optional
subject id, optional SourceProvenance and `toArray()`.

**Disposition:** TemplateContract and its descriptor/coverage/capability/
diagnostic value objects are **KEEP / RECOMMENDED machine-readable contract**.
TemplateContractInspector is **Infrastructure / hidden**; normal callers use
`inspectTemplate()`.

---

## Mapping — optional application-to-template relationship

Mapping remains optional. It does not replace imperative APIs and does not
become a second template schema, document AST or renderer.

The three supported target families are:

```text
Dependency Target
Native Object Action Target
Document Capability Target
```

A mapping definition describes an application/template relationship; it does
not alter TemplateContract.

### ApplicationPath

Create paths through:

```php
ApplicationPath::parse(string $path): ApplicationPath
```

Grammar is dot-separated named segments:

```text
segment      := [A-Za-z_][A-Za-z0-9_-]*
collection   := segment[]
path         := segment(.segment)*
```

Examples:

```text
person.name
jobs[]
jobs[].employer
jobs[].projects[]
jobs[].projects[].title
```

Empty or malformed paths throw `InvalidArgumentException`.

Public read surface:

```php
segments(): array
canonical(): string
terminalKind(): string
hasCollections(): bool
collectionPrefixes(): array
__toString(): string
```

`ApplicationPathSegment` has stable kinds `VALUE` and `COLLECTION`,
with `name()`, `kind()`, `isCollection()`, and `toString()`.

**Disposition:** ApplicationPath is **KEEP / RECOMMENDED** mapping vocabulary.
ApplicationPathSegment is **KEEP / ADVANCED supporting value**.

### MappingDefinition

Exact constructor:

```php
new MappingDefinition(
    array $dependencies = [],
    array $nativeObjectActions = [],
    array $documentCapabilities = []
)
```

Every list is type-checked at construction. Wrong element types throw
`InvalidArgumentException`.

Accessors:

```php
dependencies(): array
nativeObjectActions(): array
documentCapabilities(): array
```

It is immutable explicit configuration only; it performs no validation,
resolution or mutation itself.

### DependencyMapping

```php
new DependencyMapping(
    ApplicationPath $source,
    string $dependencyPath
)
```

The target dependency path must be non-empty. Accessors are `source()` and
`dependencyPath()`.

Collection boundaries are semantically significant. A collection target must
be mapped from an application path whose terminal segment is a collection.

### NativeObjectActionMapping

```php
new NativeObjectActionMapping(
    ApplicationPath $source,
    string $targetKind,
    string $targetName,
    string $actionId
)
```

All three target strings must be non-empty. This rule requests one explicitly
registered action against one named source-native object. Discovering a native
object never implicitly authorizes an action.

The actual Phase-E action catalog/applicability is documented with Automation;
Mapping merely carries the explicit relationship.

### DocumentCapabilityMapping

```php
new DocumentCapabilityMapping(
    ApplicationPath $source,
    string $group,
    string $target
)
```

Group and target must be non-empty. The current Phase-E bounded document
capability family includes metadata mapping; arbitrary document services are
not implied.

### Static mapping validation

The non-mutating validation service is:

```php
StaticMappingValidator::validate(
    MappingDefinition $definition,
    TemplateContract $contract,
    TemplateCapabilityProjection $projection,
    EngineCapabilityCatalog $catalog
): MappingValidationResult
```

`MappingValidationResult` exposes:

```php
valid(): bool
diagnostics(): array
deferredChecks(): array
```

Static validation covers, as applicable:

- target dependency existence/uniqueness;
- VALUE/COLLECTION shape agreement;
- collection-scope relationship consistency;
- duplicate target mappings;
- native target existence/kind/uniqueness;
- registered native action support/applicability;
- supported document capability target;
- contract/capability readiness.

Checks requiring concrete application data remain explicit
`DeferredMappingCheck` values rather than being guessed.

`MappingDiagnostic` and `DeferredMappingCheck` are immutable
machine-readable supporting diagnostics.

**Disposition:** the result/diagnostic DTOs are **KEEP / ADVANCED**.
StaticMappingValidator, capability projector/catalog internals are
**Infrastructure/Advanced orchestration** rather than the primary mapping
authoring surface.

### Mapping resolution

```php
MappingResolutionResolver::resolve(
    MappingDefinition $definition,
    TemplateContract $contract,
    array $data
): MappingResolution
```

Resolution first performs current Phase-E capability projection/static
validation. Invalid static mapping throws `MappingResolutionException`, whose
`validationResult()` exposes the complete MappingValidationResult.

Resolution precedence for dependency values is:

```text
1. explicit DependencyMapping
2. scoped same-name resolution
3. unresolved
```

The same-name rule is deliberately bounded:

- ROOT VALUE dependencies may resolve from one same-named ROOT value;
- COLLECTION boundaries are **never** established by convention;
- collection dependencies therefore require explicit collection mapping;
- inside an explicitly established mapped collection scope, same-name child
  VALUE dependencies may resolve relative to that scope;
- there is no unrestricted search through unrelated application branches.

`MappingResolution` exposes the three resolution lists:

```php
dependencies()
nativeObjectActions()
documentCapabilities()
```

### DependencyMappingResolution

Stable statuses/provenance constants:

```text
RESOLVED
UNRESOLVED
EXPLICIT
SCOPED_SAME_NAME
```

Public surface:

```php
target(): DependencyDescriptor
status(): string
source(): ?ApplicationPath
provenance(): ?string
dataResolution(): ?ApplicationDataResolution
```

### NativeObjectActionResolution / DocumentCapabilityResolution

These explicit mapping resolutions expose their mapping, source path, resolved
data status/provenance and ApplicationDataResolution. Their current provenance
is `EXPLICIT`; there is no implicit native-action/document-capability target
search.

### Application data resolution

`ApplicationDataResolver::resolve(ApplicationPath $path, array $data)`
returns `ApplicationDataResolution`.

Stable statuses:

```text
MISSING
NULL
PRESENT
EMPTY_COLLECTION
WRONG_SHAPE
```

The resolution object exposes status, value, optional item index and nested
items. These distinctions are intentional; in particular:

```text
missing collection != null collection != empty collection != wrong-shape value
```

Mapping must not silently normalize those states into one another.

**Disposition:** ApplicationDataResolution is a **KEEP / ADVANCED supporting
contract**; ApplicationDataResolver is **Infrastructure / Advanced**.

### Capability projection

`TemplateCapabilityProjection` is the non-mutating bridge between
source-template semantics and the engine-version capability catalog. It
exposes dependency targets, native-object targets and document capabilities
plus typed lookup helpers.

The projector and `EngineCapabilityCatalog` are **Infrastructure / Advanced
orchestration**, not application domain configuration. Their existence does
not move engine capability declarations into TemplateContract: source
semantics and engine-version capability remain separate by design.

### Boundary with Preflight and Automation

Mapping ends with an inspectable resolution. Concrete preflight adds actual
application-data/action payload validation against the current Working
Document, and Automation performs mutation.

Therefore:

```text
TemplateContract
 + MappingDefinition
 + Application Data
       ↓
static validation / resolution        NON-MUTATING
       ↓
MappingResolution
       ↓
Concrete Preflight                    NON-MUTATING
       ↓
Automation                            MUTATING
```

This block intentionally does **not** mark ConcreteMappingPreflight or the
automation executors complete; those belong to the next two API-family audits.

### Completion result

```text
INSPECTION — WORKING DOCUMENT
  OdtTemplate::inspect()                    KEEP / RECOMMENDED
  DocumentInspection/descriptors            KEEP / RECOMMENDED
  InspectionDiagnostic                      KEEP / RECOMMENDED support
  DocumentInspector                         INFRASTRUCTURE / HIDDEN
  status                                    VERIFIED + DOCUMENTED-COMPLETE

PHYSICAL SOURCE INSPECTION
  inspectTemplateStructure()                KEEP / ADVANCED
  structure inspection DTOs                 KEEP / ADVANCED
  inspector/projector/normalizer services   INFRASTRUCTURE / HIDDEN
  status                                    VERIFIED + DOCUMENTED-COMPLETE

SEMANTIC TEMPLATE CONTRACT
  OdtTemplate::inspectTemplate()            KEEP / RECOMMENDED
  TemplateContract                          KEEP / RECOMMENDED
  contract descriptors/scopes/provenance    KEEP / RECOMMENDED support
  coverage/capabilities/diagnostics         KEEP / RECOMMENDED support
  TemplateContractInspector                 INFRASTRUCTURE / HIDDEN
  status                                    VERIFIED + DOCUMENTED-COMPLETE

MAPPING AUTHORING
  ApplicationPath                           KEEP / RECOMMENDED
  MappingDefinition                         KEEP / RECOMMENDED
  DependencyMapping                         KEEP / RECOMMENDED
  NativeObjectActionMapping                 KEEP / RECOMMENDED
  DocumentCapabilityMapping                 KEEP / RECOMMENDED

MAPPING VALIDATION/RESOLUTION
  MappingResolution + typed resolutions     KEEP / ADVANCED inspectable result
  MappingValidationResult/diagnostics       KEEP / ADVANCED support
  low-level validators/resolvers/projectors INFRASTRUCTURE / ADVANCED
  status                                    VERIFIED + DOCUMENTED-COMPLETE
```

No new API or execution order was introduced. Inspection remains read-only,
TemplateContract remains source-oriented, and Mapping remains an optional
relationship/resolution layer rather than a renderer.


## API-family completion — Concrete Preflight

Status: **VERIFIED + DOCUMENTED-COMPLETE for the current Phase-E concrete
preflight / dry-run boundary**.

Preflight is the final non-mutating gate between Mapping Resolution and
Automation:

```text
TemplateContract + MappingDefinition + Application Data
        ↓
mapping resolution / static validation
        ↓
ConcreteMappingPreflight
        + current DocumentInspection
        ↓
ConcretePreflightResult
        ↓ READY only
Automation
```

It does not mutate the document, application data, image source files, or
template source.

### ConcreteMappingPreflight

Exact public entry point:

```php
preflight(
    MappingDefinition $definition,
    TemplateContract $contract,
    array $data,
    DocumentInspection $workingDocument
): ConcretePreflightResult
```

The optional constructor dependencies are implementation/test injection seams:

```php
__construct(
    ?MappingResolutionResolver $resolver = null,
    ?DependencyConcretePreflightValidator $dependencyValidator = null,
    ?FrameImageReplacementPreflightValidator $frameValidator = null,
    ?MetadataPayloadValidator $metadataValidator = null
)
```

Normal application code should use the default constructor.

The supplied `DocumentInspection` is an already-created read-only snapshot
of the **current Working Document**. It is currently required for concrete
named-frame image applicability. It is not substituted by TemplateContract,
because source evidence and current working-state applicability are distinct.

**Disposition:** `ConcreteMappingPreflight::preflight()` and its result model
are **KEEP / RECOMMENDED for Phase-E automation users**. Constructor service
injection is **Advanced**.

### Preflight result

`ConcretePreflightResult` has stable aggregate statuses:

```text
READY
ERROR
```

Public surface:

```php
status(): string
ready(): bool
mappingResolution(): MappingResolution
operations(): array
diagnostics(): array
```

`status()` is ERROR if any operation is ERROR; otherwise READY.
`diagnostics()` flattens all operation diagnostics in operation order.

The constructor requires every supplied operation to be a
`ConcretePreflightOperation`; invalid list members throw
`InvalidArgumentException`.

### Operation result

Every resolved dependency/native action/document capability becomes one
`ConcretePreflightOperation`.

Stable statuses:

```text
READY
ERROR
```

Public surface:

```php
targetFamily(): string
targetIdentity(): string
resolution(): DependencyMappingResolution
    | NativeObjectActionResolution
    | DocumentCapabilityResolution
capabilityId(): ?string
payloadKind(): ?string
applicability(): ?string
status(): string
diagnostics(): array
```

Invalid constructor status values throw `InvalidArgumentException`.

Current target-family/identity conventions are:

```text
dependency
    identity = TemplateContract dependency path

native_action
    identity = "<targetKind>:<targetName>"

document_capability
    identity = "<group>.<target>"
```

The operation records the already-resolved Mapping result; preflight does not
create a second mapping graph.

### ConcretePreflightDiagnostic

Public fields/accessors:

```php
code(): string
message(): string
targetFamily(): string
targetIdentity(): string
sourcePath(): ?string
context(): array
```

`context` is machine-readable `array<string, scalar|null>`. For nested
collection failures it can contain `item_path` as a dot-separated zero-based
index path.

Diagnostic **codes**, target identities and structured context are the
machine-oriented contract. Human-readable messages explain the failure but
should not be treated as a separate stable programmatic protocol.

### Dependency concrete validation

Preflight derives concrete payload requirements from the dependency's actual
TemplateContract consumers rather than assigning one universal scalar type.

Current payload kinds are:

```text
NAMED_RECORD_COLLECTION
STRING
SCALAR
CONDITION_VALUE
DEPENDENCY_VALUE
```

Consumer rules:

- COLLECTION dependencies use `NAMED_RECORD_COLLECTION`;
- SPECIAL and native Writer User Field bindings require STRING;
- SCALAR / FILTERED_SCALAR accept string, int, float or bool;
- pure IF/IFNOT dependencies use CONDITION_VALUE;
- otherwise the fallback description is DEPENDENCY_VALUE.

Important null/empty semantics:

- unresolved dependency -> error;
- missing source -> error;
- NULL is an error for collections and ordinary bindings;
- NULL is accepted for a dependency consumed **only** by IF/IFNOT;
- an empty collection is valid for a COLLECTION dependency consumed by
  FOREACH;
- missing, null, scalar/wrong-shape and empty collection remain distinct;
- collection items used for foreach must be arrays with **string keys only**;
  an empty record `[]` is valid;
- nested failures retain item-index provenance.

Representative dependency diagnostic codes currently emitted:

```text
UNRESOLVED_APPLICATION_SOURCE
MISSING_APPLICATION_RESOLUTION
MISSING_SOURCE_VALUE
NULL_SOURCE_VALUE
WRONG_APPLICATION_SHAPE
INVALID_COLLECTION_ITEM_RECORD
INCOMPATIBLE_DEPENDENCY_PAYLOAD
```

The dependency validator is marked `@internal`.

**Disposition:** DependencyConcretePreflightValidator =
**Infrastructure / HIDE FROM NORMAL USER DOCUMENTATION**. Its observable
result semantics belong to ConcreteMappingPreflight.

### Native Section and Bookmark payload boundaries

For current explicit native actions:

```text
section:<name> + replace-content
    requires PRESENT scalar payload instanceof OdtElement

bookmark:<name> + replace-text
    requires PRESENT scalar string payload
```

Incompatible values produce `INCOMPATIBLE_NATIVE_ACTION_PAYLOAD`.

Their concrete applicability is currently projected as APPLICABLE after
static mapping/capability validation; no second Working-Document structural
probe is performed here for these two action families.

This differs intentionally from named-frame image replacement below.

### Named Frame replace-image preflight

The current replace-image application payload is deliberately bounded:

```php
[
    'source' => '/local/readable/image.png',
    'options' => [
        'width'  => '4cm',   // optional
        'height' => '25mm',  // optional
    ],
]
```

Required/allowed shape:

- payload must be an array;
- `source` must exist and be a string;
- only top-level `source` and optional `options` are supported;
- `options` must be an array;
- only `width` and `height` are accepted options;
- each dimension must be a **positive** ODF-style length with unit
  `cm|mm|in|pt|pc|px`;
- whitespace-normalized or alternative units are not silently accepted by
  this boundary;
- source must be an existing readable local file;
- supported extensions are png, jpg/jpeg, gif, svg, bmp, webp;
- raster format must agree with detected MIME;
- SVG must parse as an SVG document in the SVG namespace.

Typical failures:

```text
INCOMPATIBLE_NATIVE_ACTION_PAYLOAD
INVALID_REPLACEMENT_OPTION
INVALID_IMAGE_SOURCE
ACTION_NOT_APPLICABLE
```

Concrete frame applicability additionally requires that the source-derived
frame can be resolved **exactly once** in the supplied current
DocumentInspection, in the same document part, and that its payload type is
`image` (direct draw:image child).

Applicability values used by this validator are:

```text
APPLICABLE
NOT_APPLICABLE
```

This current concrete check is why preflight receives both TemplateContract
(source truth) and DocumentInspection (working-state truth).

`ImageReplacementPreflightPayload` is explicitly documented in source as an
`@internal` E2-C projection and **not** an application-facing/future
replacement payload API.

**Disposition:** FrameImageReplacementPreflightValidator and
ImageReplacementPreflightPayload = **Infrastructure / hidden**. The payload
shape above is nevertheless part of the observable current
ConcreteMappingPreflight contract.

### Metadata concrete payload validation

Current Phase-E metadata preflight uses target-specific payload kinds from the
engine capability catalog:

```text
title              STRING
subject            STRING
description        STRING
keywords           LIST<STRING>
initial_creator    STRING
creator            STRING
language           LANGUAGE
creation_date      DATETIME
date               DATETIME
editing_cycles     NON_NEGATIVE_INTEGER
editing_duration   DURATION
generator          STRING
coverage           STRING
```

Concrete rules:

**STRING**
: PHP string only.

**LIST<STRING>**
: PHP list array only; every item must be a string. A comma-separated string
is not equivalent.

**LANGUAGE**
: string matching the bounded hyphenated language-tag grammar currently used
by the validator; values such as `en-US` are accepted while `en_US` is
not.

**DATETIME**
: bounded ISO-like date-time string including calendar/day validation, time
limits, optional fractional seconds and optional Z/offset. Date-only strings
are not accepted.

**NON_NEGATIVE_INTEGER**
: non-negative PHP int or digit string with optional leading `+`.
Floats and booleans are not accepted.

**DURATION**
: bounded ISO-8601-style duration syntax implemented by the validator;
at least one date/time component must be present and a present `T` must
introduce an actual time component.

Incompatible values produce
`INCOMPATIBLE_DOCUMENT_CAPABILITY_PAYLOAD`.

MetadataPayloadValidator is an implementation service rather than a second
public metadata API.

**Disposition:** **Infrastructure / hidden**; its observable accepted payload
semantics are documented through preflight.

### Capability/applicability relationship

Concrete preflight builds on, rather than replaces, the Phase-E
`EngineCapabilityCatalog` and mapping capability projection.

Current native capabilities are:

```text
section  / replace-content / ODT_ELEMENT
bookmark / replace-text    / STRING
frame    / replace-image   / IMAGE_REPLACEMENT
```

and dependency automation capability id is:

```text
dependency.automation
```

The general capability-projection enum remains:

```text
APPLICABLE
NOT_APPLICABLE
UNKNOWN
```

while the concrete frame validator resolves its own bounded working-state
decision to APPLICABLE or NOT_APPLICABLE.

The capability catalog and individual capability DTOs are
**Infrastructure / Advanced introspection**, not an invitation for callers to
register arbitrary new Phase-E actions in 1.0.

### Non-mutation guarantee

The focused ConcreteMappingPreflight tests explicitly characterize the dry-run
boundary: preflight leaves unchanged the supplied application data,
DocumentInspection snapshot, image source file and fixture/template file.

No document operation is executed by `preflight()`.

A READY result is therefore an authorization/readiness input for a later
automation call, not evidence that anything has already been changed.

### Lifecycle and stale snapshots

Preflight evaluates the **supplied** TemplateContract, MappingDefinition,
application data and DocumentInspection snapshot. The method does not
automatically re-inspect the Working Document.

Consequently callers performing imperative Working Document mutations between
inspection/preflight and automation must treat the snapshot/result as
belonging to that preflight state. The current API does not define a general
live/staleness-tracking object.

This is a lifecycle boundary, not a reason to add new 1.0 architecture.

### Public-documentation model

Recommended Phase-E usage should present preflight as a complete dry run, for
example conceptually:

```php
$contract = $template->inspectTemplate();
$working = $template->inspect();

$preflight = (new ConcreteMappingPreflight())->preflight(
    $mapping,
    $contract,
    $applicationData,
    $working
);

if (!$preflight->ready()) {
    // inspect $preflight->diagnostics()
    // do not automate
}
```

Automation execution is documented separately and must enforce its own READY
precondition rather than relying on application discipline alone.

### Completion result

```text
ConcreteMappingPreflight::preflight()       KEEP / RECOMMENDED (Phase-E)
ConcretePreflightResult                     KEEP / RECOMMENDED result
ConcretePreflightOperation                  KEEP / RECOMMENDED support
ConcretePreflightDiagnostic                 KEEP / RECOMMENDED support

DependencyConcretePreflightValidator        INFRASTRUCTURE / HIDDEN
FrameImageReplacementPreflightValidator     INFRASTRUCTURE / HIDDEN
ImageReplacementPreflightPayload            INTERNAL / HIDDEN
MetadataPayloadValidator                    INFRASTRUCTURE / HIDDEN

Dependency concrete semantics               VERIFIED + DOCUMENTED-COMPLETE
Section/bookmark payload boundaries         VERIFIED + DOCUMENTED-COMPLETE
Frame image payload/applicability            VERIFIED + DOCUMENTED-COMPLETE
Metadata payload semantics                  VERIFIED + DOCUMENTED-COMPLETE
Dry-run/non-mutation boundary               VERIFIED + DOCUMENTED-COMPLETE
```

No new validation semantics, payload conversion, or execution behavior was
introduced. This audit records the concrete gate that already exists between
Mapping and Automation.

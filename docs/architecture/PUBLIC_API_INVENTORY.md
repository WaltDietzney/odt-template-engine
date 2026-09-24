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

First perform the Public API Verification Audit:

```text
DISCOVERED
    -> implementation inspected
    -> tests identified
    -> semantics/options characterized
    -> compatibility/lifecycle checked
    -> VERIFIED
    -> CLASSIFIED
    -> DOCUMENTED
```

The resulting classification is the basis for the 1.0 end-programmer API
reference.

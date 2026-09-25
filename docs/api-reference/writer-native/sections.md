# Sections

A Writer Section is a named native structured container owned by the Writer template. `SectionTarget` lets PHP inspect, replace, clone, or instantiate that structure without turning the Section itself into PHP-owned document structure.

## Resolve a Section

**Recommended**

```php
public function section(string $name): SectionTarget
```

```php
$profile = $template->section('Profile');
```

Resolution is strict within the Section type:

- no match throws `TargetNotFoundException`;
- multiple matches throw `AmbiguousAddressableTargetException`.

Current public Section targeting is deliberately scoped to `content.xml`. A same-named Section in master-page content does not make the ordinary facade target ambiguous.

Targets are identity-backed handles. They do not retain a captured DOM node.

## `SectionTarget`

```php
name(): string
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

`type()` returns `section`.

## Read current Section content

### `text()`

```php
public function text(): string
```

Returns a conservative plain-text view in document order. Textual block lines are trimmed, empty lines are omitted, and retained lines are joined with `"\n"`. Images do not contribute text.

This is a read projection, not ODF serialization.

### `nestedNamedObjects()`

```php
public function nestedNamedObjects(): array
```

Returns `NamedObjectReference` values for recognized named Sections, bookmarks, tables, and frames nested inside the Section.

## Replace Section content

**Recommended · Writer container / PHP replacement content**

```php
public function replaceContent(OdtElement $content): self
```

Preserves the native `text:section` container, its name, and its attributes while replacing its children with the supplied structured content.

```php
use OdtTemplateEngine\Elements\RichText;

$content = (new RichText())
    ->addParagraph('Generated profile text');

$template->section('Profile')->replaceContent($content);
```

Accepted materialized top-level block forms are:

```text
text:p
text:h
text:list
table:table
draw:frame
```

A top-level frame is hosted in a paragraph to maintain legal text flow. An empty `RichText` can clear the Section while preserving the Section itself.

### Identity and resource validation

Replacement validates native identities introduced inside the new content. Same-type nested Section/bookmark/table/frame identities must not collide with same-type identities outside the Section or duplicate one another. Different native object types may use the same name.

Bookmark pairing inside replacement content is validated.

Resource-bearing replacement content requires package ownership and exposed image assets. Normal access through `OdtTemplate::section()` supplies the package context required by the replacement pipeline.

Unsupported materialization, identity conflicts, bookmark problems, or resource failures fail atomically.

`SectionMutationException` exposes:

```php
sectionName(): string
operation(): string
reason(): string
conflictingType(): ?string
conflictingName(): ?string
```

## Clone a prototype Section

**Recommended**

```php
public function clone(): self
```

Clones an **unsuffixed prototype** Section and rewrites supported native identities in the cloned subtree so that the returned Section remains uniquely addressable.

```php
$copy = $template->section('ExperienceEntry')->clone();

echo $copy->name(); // for example: ExperienceEntry_1
```

The engine allocates the next document-safe numeric suffix and rewrites identities covered by the clone contract, including nested Sections, bookmark markers, tables, frames/custom shapes, and covered template-expression identities.

Calling `clone()` on an already suffixed clone is unsupported in the current 1.0 contract.

Failures throw `SectionCloneException`:

```php
sectionName(): string
reason(): string
```

This public operation is the identity-safe rewritten clone path. It is not a promise of a raw exact XML duplicate.

## Instantiate one Section

**Recommended**

```php
public function instantiate(array $values): self
```

Clones the prototype with rewritten identities and binds scalar/filter template expressions owned by that clone.

Caller keys use the original logical variable names; generated suffixes remain an internal identity detail.

```php
$entry = $template->section('ExperienceEntry')->instantiate([
    'position' => 'Senior Developer',
    'note' => 'Current position',
]);
```

### Binding contract

Keys must be non-empty strings. Values must be scalar or `null`.

Every scalar variable required by the clone must be supplied. Extra supplied values are ignored. `null` binds as an empty string. Existing supported scalar filter semantics are reused.

Conditions, foreach, and other control expressions are outside this bounded instantiation path.

Failure throws `SectionInstantiationException`:

```php
sectionName(): string
reason(): string
variableName(): ?string
```

Instantiation is atomic: failed binding does not leave a clone behind.

Unlike `instantiateMany()`, singular `instantiate()` **preserves the prototype** so it may be instantiated again.

## Instantiate a collection

**Recommended · terminal prototype operation**

```php
public function instantiateMany(array $items): array
```

Expands the prototype into an ordered collection and then removes the prototype.

```php
$entries = $template->section('ExperienceEntry')->instantiateMany([
    ['position' => 'Developer', 'note' => 'Current'],
    ['position' => 'Consultant', 'note' => 'Previous'],
]);
```

Each item must be an array accepted by the singular scalar-instantiation contract. Returned `SectionTarget` instances follow caller order.

This operation is deliberately different from repeated `instantiate()`:

| Operation | Prototype after success |
| --- | --- |
| `instantiate()` | retained |
| `instantiateMany()` | removed |

An empty collection removes the prototype and returns `[]`.

If any item fails, the whole collection operation is rolled back and the prototype remains usable.

Because successful `instantiateMany()` removes the prototype, an existing handle to that prototype will subsequently fail strict resolution.

## Nested Section targeting

```php
public function section(string $name): self
```

Resolves a descendant Section relative to the current Section instance.

This is especially important for nested prototypes:

```php
$experience = $template->section('ExperienceEntry')->instantiate([
    'position' => 'Developer',
    'note' => 'Current',
]);

$activities = $experience->section('ActivityEntry');

$activities->instantiateMany([
    ['activity' => 'Architecture'],
    ['activity' => 'Review'],
]);
```

The caller continues to use the prototype's logical nested name. For generated outer instances, the implementation resolves the corresponding physical suffixed identity locally.

No local match throws `TargetNotFoundException`; multiple local matches throw `AmbiguousAddressableTargetException`.

Nested clone families remain scoped to their owning outer instance.

### 1.0 addressing boundary

Instance-relative logical addressing is currently provided for nested **Sections** through `SectionTarget::section()`.

Equivalent instance-relative logical targeting of nested bookmarks and other native object families is **not** part of the 1.0 contract. That remains separate future work rather than an implied capability of Section instantiation.

## Descriptor

`SectionDescriptor` is documented under [Inspection](inspection.md). It exposes the current Section identity, document part, child summary, nested named-object references, diagnostics, and deterministic serialization.

## Ownership

The Section container is Writer-owned.

The individual operation determines what PHP owns inside that boundary:

- `replaceContent()`: Writer keeps the Section container; PHP supplies replacement children.
- `clone()`: Writer-authored Section acts as the prototype; PHP creates an identity-safe native copy.
- `instantiate()`: Writer-authored prototype supplies structure; PHP supplies bounded scalar values.
- `instantiateMany()`: Writer-authored prototype supplies collection structure; PHP supplies ordered item data and finalizes the prototype collection.

This is distinct from `setElement()`, where PHP owns the inserted structured region itself.

## See also

- [Inspection](inspection.md)
- [Bookmarks](bookmarks.md)
- [Structured Content](../structured-content/index.md)
- [Writer-native Objects](index.md)

# Bookmarks

Writer bookmarks provide named native text ranges that remain owned by the Writer template.

`OdtTemplate::bookmark()` resolves one bookmark strictly in the current Working Document and returns a `BookmarkTarget`.

## Resolve a bookmark

**Recommended**

```php
public function bookmark(string $name): BookmarkTarget
```

```php
$bookmark = $template->bookmark('CustomerName');

echo $bookmark->descriptor()->text();
```

Resolution is typed. A name used by a Section, table, or frame does not conflict with a bookmark of the same name.

Within the bookmark type:

- no match throws `TargetNotFoundException`;
- multiple matches throw `AmbiguousAddressableTargetException`;
- malformed bookmark identity throws `MalformedTargetException`.

Current bookmark targeting is scoped to `content.xml`.

Targets are identity-backed handles rather than captured DOM nodes. `descriptor()` resolves the stored name against the current document state again.

## `BookmarkTarget`

```php
name(): string
type(): string
descriptor(): BookmarkDescriptor
replaceText(string $value): self
```

`type()` returns `bookmark`.

## Replace bookmark text

**Recommended · bounded mutation**

```php
public function replaceText(string $value): self
```

Replaces the textual payload of a safely bounded inline bookmark range while preserving the bookmark markers and identity.

```php
$template
    ->bookmark('CustomerName')
    ->replaceText('Maria Muster');
```

The replacement string is inserted as literal text. XML-special characters are escaped as text; they are not interpreted as markup. The operation does not invoke classic template processing.

### Supported range

The current mutation contract is deliberately narrow.

The bookmark start and end markers must form one unique paired range in a supported text context: `text:p`, `text:h`, or `text:span`.

The selected payload must be either:

- direct text nodes; or
- exactly one `text:span` containing text-only children.

For the single-span form, the span and its attributes are preserved while its text is replaced.

The following inspected bookmark forms are **not** automatically mutable:

- collapsed bookmarks;
- empty paired ranges;
- paragraph-spanning ranges;
- list-spanning ranges;
- table-spanning ranges;
- mixed-block ranges;
- structured or fragmented inline payloads outside the supported form.

Inspection topology describes what was found; it does not broaden the mutation contract.

### Replacement value

The replacement value must not contain:

- CR, LF, or TAB;
- leading whitespace;
- trailing whitespace;
- repeated spaces.

Unsupported values fail before DOM mutation.

### Atomic failure

A valid bookmark whose shape/value cannot safely be mutated throws `BookmarkMutationException`:

```php
bookmarkName(): string
operation(): string
topology(): string
reason(): string
```

The current operation name is `replaceText`.

Unsupported replacement is atomic: the bookmark range is left unchanged.

Malformed bookmark identity is a resolution problem and uses `MalformedTargetException` instead.

## Descriptor

`BookmarkDescriptor` is the immutable inspection snapshot documented under [Inspection](inspection.md). Its topology and text projection are useful for deciding what a Writer template contains, but applications should rely on `replaceText()` itself for the supported mutation boundary.

## Ownership and lifecycle

Writer owns the bookmark and surrounding text structure. PHP changes only the supported text payload.

Because markers remain intact, the same target can be replaced repeatedly while the identity still exists. Mutations survive normal save/reopen.

`load()` resets the Working Document. An existing target handle then resolves against that reset document and can fail if its identity no longer exists.

## See also

- [Inspection](inspection.md)
- [Sections](sections.md)
- [Writer-native Objects](index.md)

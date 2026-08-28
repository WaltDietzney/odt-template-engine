# Named Sections

LibreOffice named sections (`text:section`) are native ODT blocks with a
semantic name. The ODT Template Engine can address those blocks directly from
PHP instead of treating the document as only one large placeholder string.

Typical names include `ExperienceEntry`, `ActivityEntry`, `EducationEntry`,
and `ProjectEntry`. Use names that describe the document domain rather than
generic names such as `Section1`. Stable semantic names make templates easier
to inspect and maintain and are friendlier to future structured-document
tooling.

This page documents the public `OdtTemplate` and `SectionTarget` APIs. Internal
clone, identity, and removal services are implementation details.

## Address a section

```php
$experience = $template->section('ExperienceEntry');
```

`OdtTemplate::section(string $name): SectionTarget` resolves a named native
section in the current document state. The returned `SectionTarget` is a live,
identity-backed handle. It provides read operations such as `descriptor()` and
`text()`, and mutation operations such as `clone()`, `instantiate()`, and
`instantiateMany()`.

Resolution is strict. A missing section raises `TargetNotFoundException`; an
ambiguous native name raises `AmbiguousAddressableTargetException`. Do not
assume a missing section returns `null`.

```php
$section = $template->section('EducationEntry');

echo $section->name();
echo $section->text();
$descriptor = $section->descriptor();
```

The native name comes from the section authoring in LibreOffice. Keep the name
stable if application code will address it.

## `clone()` — structural duplication

```php
$prototype = $template->section('ExperienceEntry');
$copy = $prototype->clone();
```

`SectionTarget::clone(): SectionTarget` creates a native structural clone. It:

- preserves the source section and its native subtree;
- preserves styles, lists, frames, nested sections, bookmarks, and resources;
- rewrites supported native and template identities deterministically; and
- returns the new section target.

It does not bind data. Use it when you need a structural duplicate or when
building a lower-level document operation. Generated names are deterministic
native identities, not a caller-facing naming contract; use the returned target
instead of constructing suffixes manually.

An invalid clone state raises `SectionCloneException`. Cloning a generated,
suffixed section is not a supported way to create another generation in the
current API.

## `instantiate()` — one bound instance, prototype retained

```php
$prototype = $template->section('ExperienceEntry');

$entry = $prototype->instantiate([
    'note' => 'Aktuelle Position',
    'position' => 'Senior Projektmanager',
]);
```

`SectionTarget::instantiate(array $values): SectionTarget` performs:

```text
clone → deterministic identity rewrite → own-scope binding → insert
```

The caller uses unsuffixed template keys. The generated identity suffix is
internal to the cloned document. The returned target is immediately usable for
`text()`, `descriptor()`, and nested section addressing.

The original prototype remains visible and reusable. This is intentional:

```php
$first = $prototype->instantiate(['note' => 'A', 'position' => 'Rolle A']);
$second = $prototype->instantiate(['note' => 'B', 'position' => 'Rolle B']);
```

Both instances are bound independently while `$prototype` remains templated.
Values must be scalar or `null`; missing required own-scope values and invalid
binding data raise `SectionInstantiationException`. Extra values follow the
current binding behavior and are ignored when no corresponding expression is
present. A bookmark name is not a scalar binding key: bookmark replacement is
a separate explicit operation.

## `instantiateMany()` — ordered finalized collection

```php
$entries = $template
    ->section('ExperienceEntry')
    ->instantiateMany([
        ['note' => 'Aktuelle Position', 'position' => 'Senior Projektmanager'],
        ['note' => 'Vorherige Position', 'position' => 'Marketing-Spezialist'],
    ]);
```

`SectionTarget::instantiateMany(array $items): array` treats the target as a
collection prototype. It creates one bound native instance per input item in
input order, removes the prototype after successful expansion, and returns a
list of the created `SectionTarget` objects.

The hard invariant is:

```text
visible instances after finalization === input item count
```

Consequently, the example leaves two visible `ExperienceEntry_*` sections and
no remaining unsuffixed `ExperienceEntry` prototype. The returned list has two
targets in the same order as the input.

### Empty collections

```php
$instances = $template->section('ProjectEntry')->instantiateMany([]);
```

This creates no instances, removes the selected prototype, and returns `[]`.
Put only content inside a repeatable section that should disappear when its
collection is empty. Keep a heading outside the section if the heading should
remain when there are no entries.

### Ordering and failure behavior

Input order is preserved: `[A, B, C]` becomes `A, B, C`. Collection expansion
prepares each bounded instance through the existing instantiation path. If an
item fails validation or binding, already inserted instances are rolled back
and the prototype remains available. Prototype removal is the final operation;
a removal failure is reported and does not count as successful collection
expansion. This is bounded collection rollback, not a general document
transaction system.

`instantiateMany()` accepts a list of associative arrays. Do not pass arbitrary
scalar shorthand or use it as a declarative nested-data mapper.

## Choosing the operation

| Operation | Binds data | Keeps prototype | Result |
| --- | ---: | ---: | --- |
| `section(name)` | No | Yes | live `SectionTarget` handle |
| `clone()` | No | Yes | one structural clone target |
| `instantiate(data)` | Yes | Yes | one bound instance target |
| `instantiateMany(items)` | Yes | No | ordered list of bound targets |

Use `section()` to address a native block, `clone()` for structural work,
`instantiate()` for one bound instance while continuing to use the prototype,
and `instantiateMany()` when the input collection defines the complete final
set. Collection finalization is terminal for that prototype: after removal,
the original target no longer resolves its native identity and there is no
hidden PHP-only copy to resurrect it.

## Nested sections and owner scope

A section target can resolve a nested section relative to its own subtree:

```php
$activityPrototype = $entry->section('ActivityEntry');
```

This is different from:

```php
$template->section('ActivityEntry');
```

The template-level call uses document/global scope. The target-level call uses
the current section as the owner and ignores same-name sections outside that
owner. Missing or ambiguous local matches fail explicitly.

Each generated outer instance owns an independent nested prototype. Expand
nested collections explicitly:

```php
$experiences = $template
    ->section('ExperienceEntry')
    ->instantiateMany($jobs);

foreach ($experiences as $index => $experience) {
    $experience
        ->section('ActivityEntry')
        ->instantiateMany($activities[$index]);
}
```

The caller never needs to know physical names such as `ActivityEntry_1_2`.
Each local collection removes only its own `ActivityEntry` prototype; it cannot
remove another owner's section.

## Own-scope binding

Nested sections are binding boundaries. In this structure:

```text
ExperienceEntry
├── {{position}}
└── ActivityEntry
    └── {{activity}}
```

outer instantiation binds `position` and leaves `activity` available to the
local ActivityEntry operation. This allows an outer item to be created without
consuming a nested prototype's required value. When the nested section is
instantiated, its caller still supplies the unsuffixed key `activity`.

## Native identities and bookmarks

Cloning and instantiation rewrite supported section, bookmark, named-table,
named-frame, technical-ID, reference, and template-expression identities as
needed to keep generated native structures addressable. The exact generated
suffix is not the preferred application abstraction.

Bookmarks inside a cloned section remain native and receive rewritten names
where required. Paired and collapsed bookmark topology is preserved. Template
expression binding does not implicitly replace a bookmark merely because a data
key has a similar name; use the explicit bookmark API for bookmark operations.

## Structure and formatting preservation

The structured section API uses the template-structure preservation pipeline.
Supported replacements mutate template text in its existing native fragments,
preserving styles, spans, list structure, section membership, bookmark marker
order, sibling order, and authored whitespace. This is not a promise that every
ambiguous LibreOffice structure can be repaired automatically.

For authoring recommendations, especially placeholder fragmentation, dynamic
lengths, list ownership, and fixed-position frames, see the [Practical ODT
template authoring guide](../getting-started/template-authoring-guide.md).

## Save and reopen

Generated sections are native ODT structures. After rendering and saving, they
survive reopening and can be resolved by their native generated identities.
Finalized prototypes remain absent; collection lifecycle does not depend on
hidden PHP memory. Plan collection expansion before finalization rather than
expecting to add more items through a removed prototype after reopening.

```php
$template->render();
$template->save(__DIR__ . '/output/result.odt');

$reopened = new OdtTemplate(__DIR__ . '/output/result.odt');
$savedEntry = $reopened->section('ExperienceEntry_1');
```

The current tests cover generated section and nested-section save/reopen
behavior. For production templates, also reopen the result in LibreOffice and
review the visual layout.

## Complete CV-shaped example

The following keeps scalar assignment and structured section expansion
explicit:

```php
$template->assign([
    'firstname' => 'Max',
    'lastname' => 'Mustermann',
    'profession' => 'Senior Projektmanager',
]);

$experiences = $template
    ->section('ExperienceEntry')
    ->instantiateMany([
        ['note' => 'Aktuelle Position', 'position' => 'Senior Projektmanager'],
        ['note' => 'Vorherige Position', 'position' => 'Marketing-Spezialist'],
    ]);

$activities = [
    [
        ['activity' => 'Leitung eines interdisziplinären Projektteams.'],
        ['activity' => 'Fristen und Budgets zuverlässig überwacht.'],
    ],
    [
        ['activity' => 'Entwicklung digitaler Marketingkampagnen.'],
    ],
];

foreach ($experiences as $index => $experience) {
    $experience->section('ActivityEntry')->instantiateMany($activities[$index]);
}

$template->render();
$template->save(__DIR__ . '/output/cv.odt');
```

For the full executable/public example, see [Sample 25 — Complete CV
showcase](../product/SAMPLE-25_COMPLETE_CV_SHOWCASE.md) and
`samples/sample_25_sectionInstantiation.php`.

## Related guidance

- [Practical ODT template authoring guide](../getting-started/template-authoring-guide.md) — how to design the ODT in LibreOffice.
- [Variables & Filters](../template-language/variables-and-filters.md) — scalar and filter syntax.
- [Bookmarks](../product/NAMED-RANGE-01A_TEXT_REPLACEMENT_SEMANTICS.md) — explicit named-range operations.
- [Sample 25 showcase](../product/SAMPLE-25_COMPLETE_CV_SHOWCASE.md) — native CV collection example.
- [SECTION-03 final review](../product/SECTION-03_FINAL_REVIEW.md) — implementation boundaries and compatibility findings.

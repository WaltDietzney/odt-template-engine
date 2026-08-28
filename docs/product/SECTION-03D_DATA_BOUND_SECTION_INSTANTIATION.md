# SECTION-03D — Data-Bound Section Instantiation

## A. Goal and evidence

SECTION-03D adds the first bounded data-bound section instance on top of
SECTION-03C. The implementation was checked against PRODUCT-01C,
ADDRESSABLE-01/02, SECTION-01, SECTION-02A-D, SECTION-03A-C, the Template
Processor and FRAME-LAYOUT documents, ARCH-05/07 ownership decisions, and the
real LibreOffice-authored `sample_25_sectionClone.odt` fixture.

The fixture contains an outer `ExperienceEntry`, nested `ActivityEntry`,
paired and collapsed bookmarks, lists, and a split `{{activity}}` expression.

## B. Public API and pipeline

The additive API is:

```php
$instance = $template
    ->section('ExperienceEntry')
    ->instantiate([
        'note' => 'Aktuelle Position',
        'position' => 'Senior Projektmanager',
        'activity' => 'Leitung eines interdisziplinären Projektteams.',
    ]);
```

The pipeline is:

```text
prototype section
    → detached native clone
    → deterministic identity rewrite
    → clone-local scalar binding
    → validation
    → insertion
```

`SectionInstantiationService` owns the orchestration. `SectionTarget` remains
a thin typed facade. `OdtDocumentContext` remains the authoritative DOM owner.

## C. Local data scope and binding map

Callers provide unsuffixed keys. For clone index `1`, the internal map is
derived as:

```text
note     → note_1
position → position_1
activity → activity_1
```

Only the detached clone is processed. The source prototype and unrelated
document expressions are not evaluated or changed. Bookmark names are not
implicitly treated as data keys.

## D. Missing and extra values

Every scalar variable discovered in the clone must have a corresponding
caller key. A missing value raises `SectionInstantiationException` with the
`missing required value` reason and the variable name. This is strict and
machine-readable rather than silently producing an empty instance.

Extra caller keys are ignored. This keeps the first API compatible with
ordinary associative application data while preventing extras from affecting
unrelated document content. Non-scalar values are rejected as
`invalid binding data`.

## E. Template Processor boundary

`TemplateProcessor` remains the owner of scalar replacement and filter
semantics. A small stateless subtree seam was added to process logical text
groups below one detached native subtree. It reuses the existing
`replaceScalarText()` and `applyFilter()` behavior rather than copying the
template language into the section service.

Logical text groups treat spans and bookmark markers as transparent, but do not
cross paragraph, list-item, table-cell, section, or text-box boundaries. The
split Sample-25 `{{activity_1}}` expression therefore binds without flattening
its native spans or moving its bookmark markers.

## F. Filters, conditions, and foreach

Existing scalar filters are supported, for example:

```text
{{upper:name}}       → value from `name`
{{date:start|d.m.Y}} → value from `start` with existing date semantics
```

The identity rewrite and bounded scalar processor recognize filter variables;
filter names and options are unchanged.

Conditional and foreach expressions are intentionally rejected by this first
instantiation contract when they occur inside the clone. They require
structural processing, scope semantics, and potentially node removal or
repetition, which cannot be safely approximated by scalar subtree replacement.
Existing whole-document condition/foreach behavior is unchanged. A later
slice may add explicitly bounded control-structure instantiation.

## G. Bookmarks

Native bookmark identities remain independent addressable objects. Instantiation
does not infer that a `company` value should mutate `Company_1`, and it does
not call bookmark replacement. Paired/collapsed markers are preserved by the
underlying native clone and identity rewrite.

## H. Ordering and prototype visibility

The prototype remains visible and unchanged. Repeated prototype instantiation
produces:

```text
ExperienceEntry
ExperienceEntry_1
ExperienceEntry_2
ExperienceEntry_3
```

Instances are inserted after the last existing member of the same clone family,
so their document order follows allocation order. SECTION-03C `clone()` keeps
its original immediate-after-prototype behavior; the ordering distinction is
intentional and documented.

## I. Atomicity

Cloning, identity rewriting, expression validation, and binding occur before
the detached clone is inserted. Missing values, invalid data, unsupported
expressions, and clone failures leave the live document unchanged. The source
prototype remains reusable after every failed or successful attempt.

## J. Returned target and lifecycle

The method returns a `SectionTarget` for the rewritten name, such as
`ExperienceEntry_1`. Its descriptor, text, and nested named-object view read
the bound current document immediately. Identity-backed targets continue to
resolve against the current context after `load()`; indices are derived from
the document and not from PHP-held counters.

Instances survive save/reopen and remain strictly resolvable. Separate
`OdtTemplate` instances have independent package/context state.

## K. Sample 25

`samples/sample_25_sectionInstantiation.php` creates two instances from the
real CV section:

```text
Aktuelle Position
Senior Projektmanager
Leitung eines interdisziplinären Projektteams.

Vorherige Position
Marketing-Spezialist
Entwicklung digitaler Marketingkampagnen.
```

The source prototype remains templated. Nested sections, bookmarks, list
structure, and authored styles remain native and independently named.

## L. Package/XML and visual validation

The generated Sample-25 ODT is ZIP-valid. `content.xml`, `styles.xml`,
`meta.xml`, and `META-INF/manifest.xml` parse successfully. Reopening through
`OdtTemplate` resolves both instances and reports their bound values.

The agent environment did not provide LibreOffice visual rendering, so visual
equivalence is not claimed. Local validation commands are:

```sh
php samples/sample_25_sectionInstantiation.php
./tools/visual-regression/render-odt.sh \
    samples/output/output_25_sectionInstantiation.odt
```

The expected result is the visible prototype followed by instance 1 and
instance 2, with the existing CV formatting and list layout preserved.

## M. Tests and compatibility

Focused tests cover local binding, unsuffixed caller keys, fragmented
expressions, filters, missing/extra/invalid values, strict unsupported control
structures, ordering, save/reopen, reload, isolation, bookmark non-binding,
and atomic failure. The existing SECTION-03B/03C, section mutation,
TemplateProcessor, lifecycle, and public sample tests remain green.

No existing API was removed, renamed, or deprecated. `instantiate()` is
additive and does not change global `assign()`, `render()`, or existing
template-language behavior.

## N. Limitations and next work

This slice does not implement nested `ActivityEntry` repetition, N×M
hierarchical instances, data-bound bookmarks, prototype removal, table-row
instantiation, resource/style redesign, or a new template language.

The next section slice should define nested ActivityEntry instantiation and
its clone-family identity model, including how local parent data and repeated
child data interact without conflating it with ordinary foreach processing.

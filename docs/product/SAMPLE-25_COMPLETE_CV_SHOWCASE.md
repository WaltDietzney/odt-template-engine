# Sample 25 — Complete CV showcase

## Purpose

Sample 25 is the public end-to-end demonstration of the engine's native ODT
section model. It combines scalar replacement with finalized outer and nested
section collections in a LibreOffice-authored CV layout.

## Template and data

The source is `samples/templates/sample_25_sectionClone.odt`. Its repeatable
structure is an `ExperienceEntry` section containing an `ActivityEntry`
section. The existing header, contact area, frames, lists, bookmarks and
fixed-position layout remain native and are not rebuilt by the sample.

The discovered scalar expressions are `firstname`, `lastname`, `profession`,
`phone`, `adress`, `mail`, `note`, `position`, and `activity`. `adress` is the
existing template spelling. The malformed historical `{{phone]]` token was
corrected to `{{phone}}` as a minimal authoring fix; no engine or layout
change was made for it.

The fictional contact data is Max Mustermann, Senior Projektmanager,
`+49 151 12345678`, Musterstraße 12, 33602 Bielefeld, and
`max.mustermann@example.com`. Three experience records are rendered with
3 / 2 / 4 activities: Senior Projektmanager, Marketing-Spezialist, and
Projektkoordinator.

## Collection demonstration

The script assigns scalar values, calls `ExperienceEntry::instantiateMany()`,
and explicitly expands each returned experience's local `ActivityEntry`
collection. Collection finalization removes prototypes only after successful
materialization, so the final document has exactly three outer instances and
3 / 2 / 4 nested instances. It has zero unresolved `{{...}}` expressions.

This is explicit native section collection processing, not declarative
recursive mapping. The behavior is defined by SECTION-03F and implemented by
SECTION-03G. Static content outside a repeatable section remains owned by the
template and is not guessed or deleted.

## Validation and readiness

The smoke test checks representative contact values, package validity, section
counts, prototype removal and the zero-placeholder invariant. The sample is
deterministic and requires no manual post-processing, making it suitable for
later Sample Explorer or Sample Editor inclusion.

Visual acceptance requires local LibreOffice rendering:

```sh
php samples/sample_25_sectionInstantiation.php
./tools/visual-regression/render-odt.sh samples/output/output_25_sectionInstantiation.odt
```

The sample does not add recursive mapping, document import, or prototype
resurrection. `DOCUMENT-IMPORT-01` records the future direction for identifying
engine-generated ODT files, extracting structured CV data, and rendering it
into another template. Identification metadata and integrity verification are
separate concerns.

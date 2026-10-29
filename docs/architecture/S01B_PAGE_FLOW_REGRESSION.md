# S01b Page-Flow Regression

## Findings

The S01b visual regression was caused by authored-flow application, not by
Section identity rewriting or by the paragraph-flow mapper.

The repeated job prototype used this structure:

```text
JobSection
├── date paragraph
├── title paragraph
├── company paragraph
└── ActivitySection
    └── one cloned list container per activity
```

The styles did not establish a complete `keep-with-next` chain. Adding the
properties to the activity paragraph style alone was insufficient because
Writer treats nested list/Section containers as independent flow units. The
Operations Analyst heading therefore remained on page 1 while later activity
paragraphs flowed to page 2.

The qualification prototype already had direct heading/description
paragraphs. It only lacked the heading-to-description flow rule, so the
`Knowledge sharing` split was corrected by applying `keep-with-next` to
`CVQualification`.

## Implemented correction

`DocumentStyles::updateParagraph()` now mutates an existing authored paragraph
style in either `styles.xml` or `content.xml` while preserving its identity,
inheritance, and unrelated properties.

S01b applies:

```text
CVDate           keep-with-next + keep-together
P7               keep-with-next + keep-together
P4               keep-with-next + keep-together
P13              keep-with-next + keep-together
CVQualification  keep-with-next + keep-together
```

For each generated job, the sample replaces the nested activity container
with one direct sequence inside the existing `JobSection`:

```text
date → title → company → activity 1 → activity 2 → ...
```

This gives Writer one continuous native paragraph chain. No page numbers,
calculated page breaks, or PHP pagination are used.

## List-formatting follow-up

The activity bullets are emitted as one native `ListElement` per job. The
earlier temporary representation used ordinary paragraphs with a literal
`•`, which made wrapped continuation lines start at the paragraph margin. The
native list uses the template's `Bullet_20_Symbol` list style and therefore
provides the actual `text:list`, `text:list-item`, and hanging label geometry.

The final LibreOffice render confirms that wrapped activity lines align after
the bullet while the Operations Analyst entry remains on page 2.

## Binding rules

1. A complete professional experience entry remains together whenever the
   complete entry fits on a page. If it does not fit, Writer moves the native
   paragraph chain to the next page.
2. A qualification heading remains with its description.
3. These are generic document-flow requirements and are also requirements for
   the future Visual Template Designer.

## Verification

The corrected S01b output was generated and exported with LibreOffice 26.2.6.3
using a writable `HOME`, `XDG_CONFIG_HOME`, `XDG_CACHE_HOME`, and a separate
`UserInstallation` profile.

Result:

- 3 A4 pages;
- Operations Analyst and all four activities are on page 2;
- Knowledge sharing and its description are both on page 3;
- no orphaned target heading;
- no unintended blank page;
- ODT ZIP integrity and save/reopen tests pass.

The PDF was visually inspected after rendering to PNG.

# PAGE-FLOW-01B — Paragraph Flow Empirical Evidence

## Status

**Research evidence. No public API or implementation decision is implied by this document.**

This companion document records the Writer-authored empirical evidence collected for PAGE-FLOW-01B. It supplements `PAGE_FLOW_01_RESEARCH_AND_DESIGN.md` and should be read under the governing PAGE-FLOW-01 rule:

> **The engine describes or preserves native ODF flow semantics; LibreOffice/Writer computes actual pagination.**

The fixtures used during this research are exploratory artifacts. Their XML and visible Writer pagination were compared directly. They are not automatically public samples or permanent test fixtures.

## 1. Keep with next

### 1.1 Direct paragraph formatting

A controlled fixture placed a heading (`BERUFSERFAHRUNG`) at the end of page 1 and its following paragraph (`Projektleiter` / `Firma Muster GmbH`) on page 2.

With keep-with-next disabled, the heading remained on page 1 while the following paragraph began on page 2.

With Writer's keep-with-next behavior enabled only on the heading, Writer generated an automatic paragraph style in `content.xml` containing:

```xml
<style:style
    style:name="P1"
    style:family="paragraph"
    style:parent-style-name="Standard"
    style:master-page-name="">
    <style:paragraph-properties
        style:page-number="auto"
        fo:keep-with-next="always"/>
</style:style>
```

The heading then moved to page 2 together with the following paragraph.

This establishes:

> `fo:keep-with-next="always"` is a relationship from one paragraph to its following paragraph. It does not prescribe a physical page; Writer computes the resulting pagination.

Writer also emitted `style:master-page-name=""` and `style:page-number="auto"` in the direct-format automatic style. Those values are retained as serialization observations, but no PAGE-FLOW architecture semantics are inferred from them yet.

### 1.2 Named paragraph-style ownership

The same semantic behavior was repeated with a named Writer paragraph style, `ResearchHeading`.

Writer stored the named style in `styles.xml`:

```xml
<style:style
    style:name="ResearchHeading"
    style:family="paragraph"
    style:parent-style-name="Standard"
    style:master-page-name="">
    <style:paragraph-properties
        style:page-number="auto"
        fo:keep-with-next="always"/>
</style:style>
```

`content.xml` then only referenced that style from the paragraph.

The visible pagination behavior was the same as with direct formatting.

This establishes an ownership distinction without a semantic distinction:

```text
direct formatting
    → automatic paragraph style in content.xml

named paragraph style
    → named paragraph style in styles.xml

both
    → fo:keep-with-next="always"
```

This aligns with the STYLE-API-02 architecture principle that style semantics and style ownership are separate concerns.

Writer's built-in heading style also exhibited keep-with-next semantics in the research fixture, providing additional evidence that this is ordinary Writer paragraph-flow behavior rather than a special fixture mechanism.

## 2. Keep paragraph together

A long paragraph was positioned at a page boundary.

With the behavior disabled, Writer split the paragraph across pages.

With Writer's keep-paragraph-together behavior enabled, Writer generated:

```xml
<style:style
    style:name="P1"
    style:family="paragraph"
    style:parent-style-name="Standard"
    style:master-page-name="">
    <style:paragraph-properties
        fo:keep-together="always"
        style:page-number="auto"/>
</style:style>
```

The complete paragraph then moved to the following page instead of splitting.

This establishes a semantic distinction from keep-with-next:

```text
fo:keep-with-next="always"
    → external relationship: do not separate this paragraph from the next paragraph

fo:keep-together="always"
    → internal divisibility: do not split this paragraph across pages
```

The current `StyleMapper` explicitly maps `keep-with-next`, `break-before`, and `break-after`, but does not currently expose an equivalent explicit friendly mapping for `keep-together`. This is now an empirically evidenced capability gap. It is not yet an API decision.

A secondary observation was that Writer's default table-row style contained `fo:keep-together="auto"`. That may become relevant to TABLE-LAYOUT-01, but it does not expand PAGE-FLOW-01B into table-layout design.

## 3. Orphans and widows

Writer represents controlled paragraph splitting quantitatively rather than as an all-or-nothing keep rule.

### 3.1 Both controls enabled

A long paragraph was positioned so that, without protection, only one line remained before the page boundary. With both controls enabled at value `2`, Writer generated:

```xml
<style:paragraph-properties
    fo:orphans="2"
    fo:widows="2"
    style:page-number="auto"/>
```

The rendered pagination changed so that two lines remained on the first page.

With the corresponding protection disabled in the research fixture, Writer serialized zero values for the disabled controls rather than merely omitting them. This is an important serialization observation: absence and explicit `0` must not be assumed to be identical without further inheritance/default characterization.

### 3.2 Orphans isolated

An isolated fixture used:

```xml
<style:paragraph-properties
    fo:orphans="2"
    fo:widows="0"
    style:page-number="auto"/>
```

Writer retained at least two lines of the paragraph before the page break.

This establishes:

> `fo:orphans="N"` specifies the minimum number of lines from the beginning of a split paragraph that must remain before the page break.

### 3.3 Widows isolated at the opposite boundary

A second fixture was deliberately arranged so that, with widow protection disabled, almost the complete paragraph fit on page 1 and only its final line flowed to page 2.

The OFF fixture used:

```xml
<style:paragraph-properties
    fo:orphans="2"
    fo:widows="0"
    style:page-number="auto"/>
```

and Writer allowed a single final line on page 2.

The ON fixture used:

```xml
<style:paragraph-properties
    fo:orphans="2"
    fo:widows="2"
    style:page-number="auto"/>
```

and Writer moved additional text to page 2 so that at least two lines remained after the break.

This establishes:

> `fo:widows="N"` specifies the minimum number of lines from the end of a split paragraph that must remain after the page break.

The two properties therefore describe opposite sides of an allowed paragraph split:

```text
paragraph crossing a page boundary

page before break                      page after break
      ↑                                      ↑
  fo:orphans                             fo:widows

minimum lines before                minimum lines after
```

Unlike `fo:keep-together="always"`, widow/orphan control still permits paragraph splitting; it constrains which splits Writer may choose.

## 4. Break before and break after

Two fixtures produced the same visible pagination:

```text
page 1
ABSATZ A

page 2
ABSATZ B
ABSATZ C
```

The native semantics were deliberately different.

### 4.1 Break after

Writer attached the requirement to paragraph A through an automatic paragraph style containing:

```xml
<style:paragraph-properties
    style:page-number="auto"
    fo:break-after="page"/>
```

Semantic interpretation:

> Paragraph A requests a new page after itself.

### 4.2 Break before

Writer attached the requirement to paragraph B through an automatic paragraph style containing:

```xml
<style:paragraph-properties
    style:page-number="auto"
    fo:break-before="page"/>
```

Semantic interpretation:

> Paragraph B requests a new page before itself.

The visible result can therefore be identical while semantic ownership differs:

```text
A -- break-after="page" --> | B

A | <-- break-before="page" -- B
```

This distinction matters for dynamic structured documents. If an optional preceding block is removed, a break owned by that block may disappear with it, while a break-before requirement owned by the following block remains semantically attached to that following block.

The current `StyleMapper` direction for both `break-before` and `break-after` is therefore empirically consistent with Writer-authored ODF.

## 5. Flow semantics versus pagination result

The fixtures repeatedly reinforce a critical PAGE-FLOW distinction.

Semantic flow requirements include:

```xml
fo:keep-with-next="always"
fo:keep-together="always"
fo:orphans="2"
fo:widows="2"
fo:break-before="page"
fo:break-after="page"
```

These properties constrain Writer's layout decisions. They do not identify physical page numbers or page containers.

Writer may also serialize `text:soft-page-break` at locations corresponding to calculated pagination. Such a marker is not equivalent to a semantic request such as `fo:break-before="page"`.

The architecture must therefore distinguish:

```text
native flow requirement
    ↓
Writer layout computation
    ↓
calculated pagination / possible soft-page-break marker
```

The engine must not derive a stable API such as "this paragraph is on page 2" from calculated pagination markers. Changes in content, fonts, geometry, styles, or preceding flow may cause Writer to paginate differently.

## 6. Empirical semantic vocabulary established so far

| Concern | Native property | Established meaning |
| --- | --- | --- |
| Keep with following paragraph | `fo:keep-with-next="always"` | Do not separate this paragraph from its following paragraph |
| Keep paragraph together | `fo:keep-together="always"` | Do not split this paragraph across pages |
| Orphans | `fo:orphans="N"` | Minimum lines before the break |
| Widows | `fo:widows="N"` | Minimum lines after the break |
| Break before | `fo:break-before="page"` | This paragraph requests a page break before itself |
| Break after | `fo:break-after="page"` | This paragraph requests a page break after itself |

All characterized properties live in `style:paragraph-properties`. Their containing style may have different ownership, including automatic paragraph styles in `content.xml` and named paragraph styles in `styles.xml`.

This leads to the current evidence-based model:

> **Paragraph flow is semantic paragraph-style data. Ownership of the style and the resulting physical pagination are separate concerns.**

## 7. Current-engine implications — evidence, not decisions

The research currently supports the following observations:

1. Existing explicit `StyleMapper` mappings for `keep-with-next`, `break-before`, and `break-after` point in the correct native direction.
2. `keep-together`, widows, and orphans are native paragraph-flow capabilities not yet represented by equivalent explicit friendly mappings in the currently inspected mapper path.
3. Widows and orphans are quantitative values, so a future authoring surface must not accidentally reduce their native semantics to a boolean if the numeric value matters.
4. `always`, `auto`, explicit numeric values, explicit `0`, omission, and inheritance/default behavior must not be collapsed until their semantics are characterized sufficiently.
5. Direct formatting and named-style authoring can express the same flow semantics with different style ownership.
6. Flow requirements should remain attached to the semantic owner that requests them; visually equivalent pagination does not make `break-before` and `break-after` interchangeable.
7. None of these findings justify PHP-side pagination logic.

## 8. Remaining PAGE-FLOW-01B research

The core keep/break/widow/orphan vocabulary is now empirically characterized. PAGE-FLOW-01B still needs to resolve the following before its research can be considered complete:

1. **Paragraph-triggered page-style transitions.** PAGE-FLOW-01A established that `style:master-page-name` and `fo:break-before="page"` are distinct semantics. PAGE-FLOW-01B still needs a focused characterization of how a paragraph combines or inherits a page-style request with paragraph flow behavior.
2. **Inheritance and defaults.** Determine the meaningful differences among omitted properties, inherited values, Writer defaults, explicit `auto`, explicit `always`, and explicit numeric/zero values where they affect engine semantics.
3. **Current-engine characterization.** Add tests around existing `StyleMapper` mappings and `Paragraph` semantic `StyleRequirement` generation, then characterize whether authored flow semantics survive scalar replacement, structured insertion, save/reopen, and relevant style materialization paths.
4. **Generated structured content.** Verify how PHP-owned `Paragraph` instances currently express the supported flow properties and identify the exact capability gap for `keep-together`, widows, and orphans without yet designing the final public API.
5. **Save/reopen and rendered-output validation.** Durable characterization should include engine save/reopen behavior and, where pagination behavior matters, headless PDF/LibreOffice validation rather than relying only on the exploratory Writer fixtures.

The named-style ownership experiment already provides sufficient evidence that direct versus named ownership is a semantic-ownership distinction. It does not need to be repeated mechanically for every individual flow property unless later evidence reveals a property-specific difference.

## 9. Interim PAGE-FLOW-01B conclusions

The current evidence is sufficient to state the following without making an API decision:

1. Writer models the researched paragraph-flow behaviors through `style:paragraph-properties`.
2. Keep-with-next and keep-together are distinct native relationships.
3. Widows and orphans are quantitative constraints on an allowed paragraph split.
4. Break-before and break-after may produce identical visible pagination while assigning the requirement to different semantic owners.
5. Style ownership is independent from flow semantics: the same semantic property can live in an automatic or named paragraph style.
6. Semantic flow requirements are not physical pagination results.
7. Writer remains the pagination authority.
8. The engine already has a partial paragraph-flow vocabulary but has empirically confirmed gaps that must be assessed during PAGE-FLOW-01 architecture design.

These conclusions should feed the later PAGE-FLOW-01 Change Contract only after the remaining 01B, 01C, 01D, and current-engine characterization work is complete.

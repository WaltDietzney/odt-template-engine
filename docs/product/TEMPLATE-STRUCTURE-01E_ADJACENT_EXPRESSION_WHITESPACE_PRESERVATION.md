# TEMPLATE-STRUCTURE-01E — Adjacent Expression Whitespace Preservation

## A. Observed regression

Adjacent template expressions must not acquire or lose separators during load,
processing, or save. The relevant invariant is:

```text
{{a}}{{b}}   → {{a}}{{b}}
{{a}} {{b}}  → {{a}} {{b}}
{{a}}-{{b}}  → {{a}}-{{b}}
```

## B. Source XML evidence

The current Sample-25 archive does not contain adjacent `firstname` and
`lastname` expressions without a separator. Its exact header topology is:

```xml
<text:p text:style-name="P5">
  <text:span text:style-name="T3">{{firstname}}</text:span>
  <text:span text:style-name="T2"> </text:span>
  <text:span text:style-name="T3">{{lastname}}</text:span>
</text:p>
```

The middle span contains authored literal ASCII space. Span boundaries do not
themselves imply a separator.

## C. Output XML evidence before the fix

The old load path used `preserveWhiteSpace = false`, causing the whitespace-only
separator text node to be discarded while loading. The old package serializer
also applied `>\s+<`, which could discard the same authored space during writeback.
The resulting topology could therefore become an empty T2 span between the two
expressions. The reported visual symptom was not reproduced as an engine-added
space from this fixture; the repository evidence instead showed destructive
loss of authored whitespace. A synthetic no-space fixture was used to verify
that the engine does not add whitespace.

## D. Lifecycle trace

The affected stages were characterized as follows:

1. archive: literal space exists in the T2 span;
2. DOM load before the change: whitespace-only content is discarded;
3. template normalizer: no separator is inferred or added;
4. processing: replacement mutates token text only;
5. pre-save DOM: the separator has already been lost by the old loader;
6. old save: broad whitespace stripping could also remove text between tags;
7. fixed save: authored literal space remains serialized in the T2 span.

## E. Identified mutation point

The first destructive point is package XML loading with whitespace-only nodes
disabled. The serializer’s broad `>\s+<` expression was a second independent
risk. Neither the projector, normalizer, nor replacement service inserts
spaces between complete expressions.

## F. ODF whitespace semantics

Literal text-node whitespace is document content, including a single space in a
text span. `text:s` is a separate ODF structural space element and remains a
separate element. Span boundaries alone are not visible whitespace. The engine
now preserves whitespace-only nodes during core XML loading and does not
convert literal spaces to `text:s` or vice versa.

## G. Hard preservation rule

Whitespace and punctuation between complete expressions are literal authored
content. The engine does not infer, normalize, delete, or change separators.
This applies equally inside paragraphs, headings, draw text boxes, and styled
span runs.

## H. Affected code path

The bounded fix is in `OdtPackage`: core DOM loading preserves whitespace, and
writeback removes only indentation containing line breaks rather than every
whitespace sequence between tags. Template projection and structure-preserving
replacement already concatenate physical text with `implode('')`, so they do
not invent separators.

## I. Fix

`OdtPackage::loadXmlFile()` now preserves whitespace. `sourceDom()` likewise
does not request blank-node removal for original-structure inspection. The
package serializer uses a line-break-aware indentation cleanup expression;
single-space text nodes survive the round trip.

## J. Compatibility

This preserves actual ODF text content while continuing to remove ordinary XML
pretty-print indentation. Existing template processing, filters, sections,
collections, bookmarks, styles, resources, and sample behavior remain
unchanged. The change is intentionally not a template-language redesign.

## K. Characterization matrix

Focused tests cover adjacent expressions with no separator, literal space,
hyphen, slash, comma-space, same/different styled spans, `text:s`, paragraph
and draw-text-box contexts, single/both-variable replacement, and no-op
load/save. The synthetic no-space ODT confirms that separate spans remain
adjacent after a package round trip.

## L. Sample-25 verification

Sample 25 retains its authored T2 space span between `firstname` and
`lastname`; the collection output remains otherwise unchanged, including three
outer experiences and 3/2/4 nested activities. Static activity siblings remain
untouched as required by SECTION-03F/03G.

## M. Limitations

The agent-side visual rendering environment was not used to claim a visual
pass. This slice does not attempt broad whitespace normalization, LibreOffice
layout correction, or rich formatting distribution.

## N. TEMPLATE-FORMAT-PRESERVATION relationship

This slice resolves the narrower package load/save whitespace-loss hazard for
literal separators. It does not complete the broader
`TEMPLATE-FORMAT-PRESERVATION-01` work, including effective style semantics,
rich structural replacement, or control-structure preservation.

## O. Future development note

The separate future theme “Engine-generated document identification and
structured data extraction/re-use” remains unimplemented and is not expanded
here.

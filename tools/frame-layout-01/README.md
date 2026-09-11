# FRAME-LAYOUT-01 H1-H7 Cross-Part Image Research

This tool generates a controlled seven-document matrix for the PAGE-FLOW / FRAME-LAYOUT image discrepancy.

It is research tooling, not a public sample.

## Generate fixtures

From the repository root:

```bash
php tools/frame-layout-01/generate-cross-part-image-fixtures.php
```

Generated files are written to:

```text
tmp/frame-layout-01-cross-part/
```

Do not commit these generated ODT files.

## Cases

| Case | Purpose |
| --- | --- |
| H1 | `setImage()`, `as-char`, header — known Writer-visible baseline |
| H2 | `ImageElement`, `as-char`, header — known Writer-invisible baseline |
| H3 | H2 structure with `draw:style-name` removed |
| H4 | H2 structure referencing the Writer-style `Graphics` graphic style |
| H5 | H1 paragraph/frame structure plus H2's generated ImageElement graphic style |
| H6 | `ImageElement`, `as-char`, body |
| H7 | `ImageElement`, `as-char`, header, generated independently from H6 |

The generator also compares the canonical `draw:frame` subtree from H6 and H7 and writes the result to:

```text
tmp/frame-layout-01-cross-part/RESULTS.txt
```

## Manual LibreOffice review

Open H1 through H7 in LibreOffice Writer and record whether the logo is visibly rendered.

Use this table:

| Case | Visible? | Notes |
| --- | --- | --- |
| H1 |  |  |
| H2 |  |  |
| H3 |  |  |
| H4 |  |  |
| H5 |  |  |
| H6 |  |  |
| H7 |  |  |

The goal is to identify which boundary follows Writer visibility:

- document part;
- paragraph/insertion context;
- `draw:style-name`;
- graphic style definition;
- anchor;
- or a combination.

## Interpretation rules

Do not infer a fix from one passing case alone.

Examples:

- If H3 becomes visible, generated style-reference semantics become the leading hypothesis.
- If H4 becomes visible, compatibility with the referenced graphic style becomes important.
- If H5 remains visible, the H1 paragraph/frame insertion context is compatible with the ImageElement style.
- If H6 is visible and H7 is not while their frame subtrees are canonically identical, the decisive boundary is outside the frame subtree and document-part/insertion context becomes primary.
- If H5 is visible but H2/H3/H4 are not, paragraph containment is a strong candidate because H1/H5 preserve the `text:p -> draw:frame` structure.

These are research interpretations only. No production change is authorized by this matrix.

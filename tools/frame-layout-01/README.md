# FRAME-LAYOUT-01 H1-H7 Cross-Part Image Research

This tool generates a controlled seven-document matrix for the PAGE-FLOW / FRAME-LAYOUT image discrepancy.

It is research tooling, not a public sample.

## Writer-authored base fixture

The generator no longer synthesizes its own ODT package. Use a real Writer-authored base document containing exactly these research placeholders:

```text
header: {{header_logo}}
body:   {{body_logo}}
```

Default path:

```text
research/frame-layout-01-cross-part-base.odt
```

The base file is validated before generation: `{{body_logo}}` must exist in `content.xml`, `{{header_logo}}` must exist in `styles.xml`, and a real master-page header must be present.

## Generate fixtures

From the repository root:

```bash
php tools/frame-layout-01/generate-cross-part-image-fixtures.php
```

Or pass another Writer-authored base explicitly:

```bash
php tools/frame-layout-01/generate-cross-part-image-fixtures.php /path/to/base.odt
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


## Render the complete matrix to PDF and PNG

The repository's existing visual-regression pipeline is reused directly.

Run:

```bash
bash tools/frame-layout-01/render-cross-part-image-matrix.sh
```

or with an explicit Writer-authored base:

```bash
bash tools/frame-layout-01/render-cross-part-image-matrix.sh /path/to/base.odt
```

The wrapper first regenerates H1-H7 and then calls:

```text
tools/visual-regression/render.sh
```

Outputs are written to the existing visual-regression locations:

```text
tmp/visual-regression/pdf/
tmp/visual-regression/images/
```

This gives a much faster side-by-side check than opening all seven ODT files manually. If LibreOffice reports a repair/error prompt when opening the Writer-authored base itself, stop and fix the base before interpreting the matrix.

The generator also records structural sanity checks in `RESULTS.txt`, including:

- whether `{{body_logo}}` was actually consumed in H6;
- H6 body `draw:image` count;
- H7 header `draw:image` count;
- canonical H6/H7 frame-subtree equality.

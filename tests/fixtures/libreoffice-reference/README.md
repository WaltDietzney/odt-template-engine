# LibreOffice ODF Reference Fixtures

This directory contains small, manually created LibreOffice documents used as
empirical references for native ODF serialization.

The fixtures are independent of current ODT Template Engine output. They are
not expected-output snapshots for the engine and must not be silently adjusted
to make an implementation pass. The ODF specification remains normative;
LibreOffice serialization provides practical implementation evidence.

## Layout

- `odt/` — original reference ODT files;
- `extracted/` — raw and, later, normalized evidence extracted from those ODTs.

Raw extraction preserves the original serialized XML; it is not a generated
engine snapshot.

## Captured fixtures

### STYLE-01 — Named paragraph style

- File: `odt/STYLE-01-named-paragraph-style.odt`
- LibreOffice: 24.2.7.2 (X86_64), Community, Build 420(Build:2)
- Platform: Ubuntu 24.04, package `4:24.2.7-0ubuntu0.24.04.6`
- SHA-256: `1782fa8733db3e88752284e76d72e7fff649ef3fe4aee38976263fcdf6ce53eb`
- Status: original fixture captured
- Round-trip: not yet performed

### STYLE-02 + STYLE-04 — Direct formatting

- File: `odt/STYLE-02-04-direct-formatting.odt`
- LibreOffice: 24.2.7.2 (X86_64), Community, Build 420(Build:2)
- Platform: Ubuntu 24.04, package `4:24.2.7-0ubuntu0.24.04.6`
- SHA-256: `12e48079af090d25c42186e81052a8d3d8a356be1e2298986eedbf1c3b0bdb9f`
- Status: original fixture captured
- Round-trip: not yet performed

### STYLE-05 — Named style with direct override

- File: `odt/STYLE-05-named-style-direct-override.odt`
- LibreOffice: 24.2.7.2 (X86_64), Community, Build 420(Build:2)
- Platform: Ubuntu 24.04, package `4:24.2.7-0ubuntu0.24.04.6`
- SHA-256: `7ae5f32ddd9ef99f3db5ddcdd9b340d52425b5075bffa97c1e9899bf36b99a02`
- Status: original fixture captured
- Round-trip: not yet performed

### FONT-01 — Non-default font

- File: `odt/FONT-01-non-default-font.odt`
- LibreOffice: 24.2.7.2 (X86_64), Community, Build 420(Build:2)
- Platform: Ubuntu 24.04, package `4:24.2.7-0ubuntu0.24.04.6`
- SHA-256: `4680810fd77fc32ff2502d142f80c59a16fde883777f45e9eb487016710c3231`
- Status: original fixture captured
- Round-trip: not yet performed

### TABLE-02 — Formatted cell

- File: `odt/TABLE-02-formatted-cell.odt`
- LibreOffice: 24.2.7.2 (X86_64), Community, Build 420(Build:2)
- Platform: Ubuntu 24.04, package `4:24.2.7-0ubuntu0.24.04.6`
- SHA-256: `dba70c9fe41026bda5a3f41bf2d1f2502c152f99e6d4e4272b0849d6f2f05c9b`
- Status: original fixture captured
- Round-trip: not yet performed

### FRAME-01 + FRAME-02 — Text-box position

- File: `odt/FRAME-01-02-text-box-position.odt`
- LibreOffice: 24.2.7.2 (X86_64), Community, Build 420(Build:2)
- Platform: Ubuntu 24.04, package `4:24.2.7-0ubuntu0.24.04.6`
- SHA-256: `a12b50088eed794fb74799ae8feb91db5ac0be3e48a900e99e9c3d270560f7b3`
- Status: original fixture captured
- Round-trip: not yet performed

### IMAGE-01 — Embedded image

- File: `odt/IMAGE-01-embedded-image.odt`
- LibreOffice: 24.2.7.2 (X86_64), Community, Build 420(Build:2)
- Platform: Ubuntu 24.04, package `4:24.2.7-0ubuntu0.24.04.6`
- SHA-256: `2e4554d7494f28ebee77bc063dc93816e9a77986d3f9b0b707cff3287dd2bbeb`
- Embedded resource: `Pictures/100000000000028000000280B8169D6C.jpg`, JPEG,
  640x640, SHA-256 `0227b05d69f45b2acdc56ec7dcb966ed8d284b7054a6ac8c8e7ffa6cfa3c3bef`
- Source PNG: not supplied; byte-for-byte source comparison not available
- Status: original fixture captured
- Round-trip: not yet performed

### PAGE-01 + PAGE-02 — Layout and master page

- File: `odt/PAGE-01-02-layout-master-page.odt`
- LibreOffice: 24.2.7.2 (X86_64), Community, Build 420(Build:2)
- Platform: Ubuntu 24.04, package `4:24.2.7-0ubuntu0.24.04.6`
- SHA-256: `e4f5476cbbf7971461998df99a8f7d5eaf7d0eedf64476cfd0c38a5ce9ce9a81`
- Status: original fixture captured
- Round-trip: not yet performed

## Naming

Use the stable reference ID followed by a descriptive name, for example:

```text
STYLE-01-named-paragraph-style.odt
STYLE-02-direct-paragraph-formatting.odt
IMAGE-01-embedded-image.odt
```

## Provenance

Every fixture must have documented provenance, including:

- reference ID;
- LibreOffice version;
- operating system/platform;
- creation date;
- exact reproducible manual creation procedure;
- whether the file was reopened and resaved;
- SHA-256 of the original ODT;
- relevant ODF version reported by the package, if available.

Do not silently regenerate a fixture with a different LibreOffice version.
Create new provenance and explicitly compare when a version change is needed.

## Independence and inspection

Fixtures must be created manually with LibreOffice, not by the template engine
or a simulator. Future analysis should preserve the original ODT and retain
raw extracted XML alongside any normalized excerpts so normalization cannot
hide relevant relationships.

The future extraction workflow is expected to expose at least `content.xml`,
`styles.xml`, `META-INF/manifest.xml`, relevant metadata/settings files, and a
package file listing. The extraction tool is not created in this step.

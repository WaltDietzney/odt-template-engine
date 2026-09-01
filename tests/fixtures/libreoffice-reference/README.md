# LibreOffice ODF Reference Fixtures

This directory will contain small, manually created LibreOffice documents used
as empirical references for native ODF serialization.

The fixtures are independent of current ODT Template Engine output. They are
not expected-output snapshots for the engine and must not be silently adjusted
to make an implementation pass. The ODF specification remains normative;
LibreOffice serialization provides practical implementation evidence.

## Layout

- `odt/` — original reference ODT files;
- `extracted/` — raw and, later, normalized evidence extracted from those ODTs.

No binary fixtures are created by the initial framework change.

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

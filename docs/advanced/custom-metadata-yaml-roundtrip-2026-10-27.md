# Native custom metadata and YAML roundtrip (2026-10-27)

## Purpose

Enable applications such as Bewerbungstools to store a versioned YAML snapshot
in an ODT `meta:user-defined` field, independently of the editable native
Sections, Bookmarks and Writer User Fields.

## API and compatibility

`$template->setMeta(['custom' => ['bewerbungstools.profile' => $yaml]]);`

`$yaml = $template->getMeta()['custom']['bewerbungstools.profile'] ?? null;`

The `custom` map is an additive, string-only extension. Standard metadata
keys and aliases remain supported; unrelated unknown keys remain ignored.
Named custom fields are updated without deleting unrelated entries. Inputs
are validated before mutation. Existing metadata is returned under `custom`.

## Serialization fix

`OdtPackage::saveMinifiedXml()` historically removed all line breaks, tabs
and repeated spaces from every XML part, including text nodes of `meta.xml`.
That corrupted multiline YAML. `meta.xml` is now serialized without that
minification. Content and styles retain their previous save behavior.

## Verification

- PHP syntax checks passed for modified PHP files.
- Existing S01b sample executed with the repository source using a temporary
  autoloader; no sample source or template was modified.
- Added YAML through the public `setMeta(['custom' => ...])` facade.
- Generated ODT was resaved by headless LibreOffice; the custom field remained
  byte-identical and PyYAML parsed it to the original synthetic structure.
- 23 named Sections remained present with identical names after LibreOffice
  roundtrip, including nested `JobSection_*` and `ActivitySection_*`.
- Integration tests added for save/reopen, repeated updates, and invalid input.

This is an automated save roundtrip, not manual Writer editing. Further tests
should cover user-edited document data, stale YAML conflicts, and larger YAML
payloads. Custom metadata is not tamper-proof and is not a purchase token.

## Scope

No production deployment or change to Bewerbungstools runtime. The S01b
showcase and its original template remain unchanged.

## Regressionstest nach Implementierung

- Isolierte Composer-Testabhängigkeiten unter `/tmp`, PHPUnit 11.5.57 mit PHP 8.5.4.
- `OdtTemplatePackageLifecycleTest.php`: **9 Tests, 52 Assertions, bestanden**.
- Vollständige Suite mit temporär eingebundenem `vendor`-Verzeichnis:
  **997 Tests, 7.662 Assertions, Exit 0**; PHPUnit meldet 1 Warnung,
  4 Deprecations und 8 PHPUnit-Deprecations. Diese Hinweise wurden
  nicht als behoben eingestuft.
- Temporärer `vendor`-Link nach dem Test entfernt; Arbeitsverzeichnis sauber.
- `master` und Tag `v1.0.0` bleiben auf `c3558ff`; die Erweiterung liegt
  ausschließlich auf `feature/custom-odt-metadata`. Kein Push, Merge oder Release.

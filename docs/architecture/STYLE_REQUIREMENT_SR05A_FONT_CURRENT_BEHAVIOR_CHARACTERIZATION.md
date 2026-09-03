# SR-05A — Current Font Dependency Behavior Characterization

## 1. Scope

This note records the behavior of the engine before SR-05 font dependency
migration. It is a characterization document, not an implementation of the
SR-05 target architecture.

The scope is limited to Paragraph/Text styles, `styles.xml`, `content.xml`,
font-face declarations, and observable lifecycle/static-state behavior.
Graphics, tables, page layouts, font embedding, and default-font semantics are
out of scope.

## 2. Baseline

The characterization was performed in:

```text
worktree: /home/walt/Projekte/odt-template-engine-01fd5
branch: architecture/style-context-01fd5-transitive-requirements
baseline: 8b33cbdca74989ec7438a83f3b76cf9830f97e00
pre-SR-05 implementation baseline: 6f3eae5b64ebf4e3acbb82aa66ea122a60729f80
```

The approved target contract is
`STYLE_REQUIREMENT_SR05_FONT_DEPENDENCIES_CHANGE_CONTRACT.md`. The older
architecture documents named by that contract are not present on this branch;
they remain maintained on the separate documentation/reference branch. This
note does not reproduce or modify that contract.

## 3. Current font data flow

The current migrated Paragraph/Text path is:

```text
Paragraph convenience options
    -> StyleOptionSplitter / StyleMapper
    -> native StyleRequirement propertyGroups
    -> StyleContext
    -> StyleRequirementMaterializer
    -> styles.xml style:style
    -> StyleWriter::writeAllStyles() scans styles.xml
    -> styles.xml office:font-face-decls/style:font-face
```

`StyleRequirementMaterializer` is style-only. It does not discover or write
font faces.

The legacy path is separate:

```text
StyleMapper registration / legacy text style state
    -> StyleWriter style serialization
    -> StyleWriter font scanning or writeFontFaces()
    -> style:font-face declarations
```

Normal `OdtTemplate::save()` calls `StyleWriter::writeAllStyles()` on the
`styles.xml` DOM. That method scans attributes whose names end in
`font-name`, then creates missing font-face declarations in
`styles.xml/office:font-face-decls`.

## 4. Active semantic path

`Paragraph::getOwnStyleRequirements()` maps text-related Paragraph options and
generated inline text options before constructing `StyleRequirement` objects.
For example, convenience `font-family` becomes:

```text
style:text-properties:
    style:font-name = <font value>
    fo:font-family = <font value>
```

The semantic materializer writes these native attributes directly. It does not
call `StyleMapper` again.

For the current Paragraph producer, these definitions are common styles in
`styles.xml`. The semantic path therefore does not naturally produce a
font-dependent `content.xml` style.

## 5. Legacy/compatibility path

`StyleMapper::registerTextStyle()` and `setTextStyle()` remain compatibility
APIs. `StyleWriter::writeAllStyles()` can serialize registered legacy text
styles into `office:styles`. `StyleWriter::writeTextStyles()` is a separate
specialized path and records generated style names in the static
`$generatedTextStyles` cache.

`StyleWriter::$fontsUsed` is also static. It is populated by the specialized
writer when it sees `style:font-name` and is consumed by `writeFontFaces()`.
Neither static collection is document-owned or reset when a new DOM/document
is created.

SR-05A does not change these compatibility paths.

## 6. Physical materialization behavior

### Normal `writeAllStyles()` path

- Semantic Paragraph/Text style definitions are physically present in
  `styles.xml` before `writeAllStyles()` runs.
- Font references are discovered by scanning the styles DOM.
- A missing `office:font-face-decls` container is created in `styles.xml`
  before `office:styles`.
- A missing font face is appended with `style:name` and `svg:font-family`
  both set to the scanned `style:font-name` value.
- Repeated identical font references produce one declaration because the
  discovered font names are collected by associative key and existing
  declaration names are skipped.

### Specialized `writeTextStyles()` / `writeFontFaces()` path

- Registered text styles are written with prefixed element names created by
  the legacy DOM path.
- `style:font-name` also populates static `$fontsUsed`.
- `writeFontFaces()` creates declarations from that static set.
- The static generated-style cache can suppress writing the same style into a
  later DOM, while the static font set can still cause a font-face declaration
  to be written into that later DOM.

## 7. `styles.xml` versus `content.xml`

The current migrated Paragraph/Text producers use common styles in
`styles.xml`. Their font declarations therefore appear in:

```text
styles.xml/office:font-face-decls/style:font-face
```

`StyleWriter::writeAllStyles()` is called with the styles DOM and does not scan
`content.xml` for semantic font references.

An existing internal `StyleRequirementMaterializer` call can place an
automatic Text style in `content.xml/office:automatic-styles`, but the current
`writeAllStyles()` path still only scans `styles.xml`. Consequently no
font-face declaration is generated in `content.xml` for that reachable
lower-level case.

This is an observation, not a recommendation to add content-part behavior in
SR-05A.

## 8. Existing declaration behavior

For the normal styles-DOM path, existing declarations in
`styles.xml/office:font-face-decls` are identified by `style:name`.

- Matching identity: preserved and not duplicated.
- Existing `svg:font-family`: preserved; it is not rewritten.
- Existing identity/family mismatch: preserved as existing document data.
- Existing declarations in `content.xml` are not consulted by the normal
  styles-DOM scan.

Thus existing foreign ODT data is effectively authoritative on the path that
does inspect it, while the newly generated representation is not identity-safe
for distinct font family values.

## 9. Font-face identity versus font family

Current newly generated behavior collapses the two values.

For native semantic text properties:

```text
style:font-name = CharacterizationFont1
fo:font-family = 'DejaVu Serif'
```

the style definition preserves both attributes. However, the generated
font-face declaration is observed as:

```text
style:name = CharacterizationFont1
svg:font-family = CharacterizationFont1
```

The supplied `fo:font-family` value is not used to populate the generated
font-face family. This is a known SR-05 gap.

## 10. Lifecycle and isolation observations

`StyleContext` and semantic style requirements are document-local. Existing
document lifecycle tests show that semantic definitions/references reset when
core documents are replaced or a template is loaded.

Normal `writeAllStyles()` font discovery scans the current styles DOM, so
separately saved semantic documents do not inherit font declarations merely
from that scan.

The specialized legacy writer is different: `$generatedTextStyles` and
`$fontsUsed` are process-global static state. In characterization, a style
written to one DOM was suppressed in a second DOM by the generated-style cache,
while the previously observed font still produced a font-face declaration in
the second DOM through `writeFontFaces()`.

This behavior is intentionally preserved and documented here; it is not fixed
in SR-05A.

## 11. Characterization test matrix

Executable coverage is in
`tests/Integration/StyleRequirementFontCurrentBehaviorCharacterizationTest.php`.

| Case | Current observation |
|---|---|
| A. One semantic Paragraph/Text font | Style properties and font face appear in `styles.xml`; identity and family are equal to the scanned font name. |
| B. Multiple semantic styles, same font | One `styles.xml` font-face declaration is produced. |
| C. Multiple different fonts | One declaration per distinct scanned font name. |
| D. Native identity differs from family | Style preserves both values; generated font-face uses identity for both `style:name` and `svg:font-family`. |
| E. Existing declaration | Existing identity/family is preserved and not duplicated. |
| F. Document part | Current semantic producer path is styles.xml-only; a reachable content.xml automatic style receives no font-face declaration from `writeAllStyles()`. |
| G. Lifecycle/static state | Semantic DOM state is document-local; specialized legacy writer caches are process-global and leak across DOMs. |

Existing related coverage remains in:

- `tests/Integration/StyleContextTextFontCharacterizationTest.php`
- `tests/Integration/StylePipelineP2BTest.php`
- `tests/Integration/DocumentFinalizationArch03CTest.php`
- `tests/Integration/ParagraphStylePersistenceTest.php`

## 12. Known gaps relative to the SR-05 contract

The following are target requirements from SR-05, not current behavior:

- explicit document-local font dependency ownership;
- independent preservation of font-face identity and font family;
- per-document-part dependency handling for both core XML parts;
- same-identity/different-family conflict detection;
- no process-global font state on the semantic path;
- no duplicate physical ownership between semantic and legacy paths;
- lifecycle-safe reset of all new font dependency state.

SR-05A does not implement any of these changes.

## 13. Explicit non-decisions and out-of-scope items

SR-05A does not:

- add FontFace models, collectors, registries, or materializers;
- modify `StyleRequirement`;
- modify `StyleRequirementMaterializer`;
- modify `StyleWriter` or `StyleMapper`;
- change font-face placement;
- add content.xml font dependency materialization;
- change `style:default-style` or `Standard` semantics;
- embed fonts or add font files;
- migrate graphics, images, tables, page layouts, or master styles;
- redesign the document lifecycle;
- issue a visual regression approval.

These decisions remain for the subsequent SR-05 architecture work.

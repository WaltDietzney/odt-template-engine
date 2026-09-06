# D5G-D — Static / Finalization Compatibility Narrowing

Status: implementation record

## 1. Scope and baseline

This slice starts at D5G-C commit `d8ecaea7` and narrows only internal
OdtTemplate finalization that was still broader than current-document evidence
requires. It does not remove static APIs, change `StyleWriter` defaults, alter
legacy element semantics, or introduce a lifecycle service.

The governing boundary is:

```text
public/static compatibility registry
    -> current-document references
    -> OdtTemplate adoption/finalization
```

The semantic `StyleContext` and package-owned resources remain authoritative for
the modern `setElement()` lifecycle.

## 2. Static registry and writer audit

| Family | Registry / API | Writer or reader | OdtTemplate current-document filter | D5G-D result |
|---|---|---|---|---|
| paragraph | `LegacyStyleRegistry` via `StyleMapper::registerParagraphStyle()` | `StyleWriter` defaults; `StyleContext` legacy fallback | OdtTemplate disables legacy paragraph writing and uses document-local compatibility handling | retained public/default compatibility |
| text | `StyleMapper` registered text styles | `StyleWriter` defaults; `StyleContext` legacy fallback | OdtTemplate disables legacy text writing | retained public/default compatibility |
| frame | public `StyleMapper::$frameStyles`, `addFrameStyle()` | `StyleWriter` graphic branch | current `draw:style-name` references, legacy path only | retained, OdtTemplate writer activation narrowed |
| image | `registerImageStyle()` / `getRegisteredImageStyles()` | `injectLegacyImageStyles()` | current drawing graphic references | retained, current-reference adoption |
| fill-image | `registerFillImage()` / `getRegisteredFillImages()` | `injectLegacyImageStyles()` | current `draw:fill-image-name` or referenced legacy image style | retained, current-reference adoption |
| table | public `StyleMapper::$tableStyles`, register/get API | `StyleWriter` table branch; RichTable semantic adoption | current table structural references | retained, current allowlist |
| table-cell | `registerTableCellStyle()` / getter | `StyleWriter` table-cell branch | current table-cell references plus semantic-owned exclusions | retained, current allowlist |
| fonts | private StyleMapper registry and document font requirements | font materializers / writer font scan | document-local font requirements and DOM references | outside this slice |

`StyleWriter::writeAllStyles()` remains a public broad compatibility callable.
Its default arguments still serialize registered paragraph, text, frame,
table-cell, and table families as before. OdtTemplate is the component that
decides which current-document names are passed to the writer.

## 3. Finalization data flow

### Table and table-cell

```text
StyleMapper static registration
    -> current table/table-cell structural reference scan
    -> StyleWriter allowlist
    -> styles.xml
```

The D5G-C behavior remains in force for both semantic and legacy paths. An
unrelated global registration is not written. Element-owned semantic cells are
also excluded from becoming a second common definition.

### Frame

```text
legacy OdtElement rendering
    -> StyleMapper frame registry
    -> current draw:style-name scan
    -> pending frame names
    -> StyleWriter graphic branch
```

D5G-D changes only the internal activation flag passed by OdtTemplate: the
branch is enabled when the current legacy document has at least one referenced
frame name. With no current frame reference, the writer is not asked to read
the frame registry. Direct callers of `StyleWriter` are unaffected.

### Image and fill-image

```text
legacy rendering
    -> StyleMapper image/fill registries
    -> current draw references / fill-image names
    -> injectLegacyImageStyles()
    -> styles.xml declarations and legacy graphic styles
```

The existing reference helpers already filter adoption to the current DOM.
Their protected compatibility hook and legacy path remain unchanged. The known
legacy ImageElement package-resource omission is not changed.

## 4. Characterization evidence

The focused D5G-D characterization suite records:

* direct `StyleWriter::writeAllStyles($dom)` still materializes registered
  paragraph, table, table-cell, and frame styles;
* an assigned missing DrawTextBox placeholder does not adopt a static frame
  definition;
* an assigned missing ImageElement placeholder does not adopt a static image
  definition;
* an assigned missing CircularImageElement placeholder does not adopt an
  unrelated static fill-image declaration.

Existing D5G-C, SR-06, and SR-07 suites additionally cover two-document image,
fill-image, frame, table, and table-cell isolation, current-reference
adoption, repeated save, and mixed semantic/legacy output.

The missing-placeholder characterization intentionally does not repair the
known legacy CircularImage resource-copy behavior. `registerLegacyGraphicRequirements()`
can copy a rendered fill-image asset before placeholder replacement succeeds;
that package behavior remains a separate compatibility issue.

## 5. Implementation

The only production change is in `src/OdtTemplate.php`:

* current legacy frame names are still derived only when the legacy structured
  lifecycle is active;
* the `StyleWriter` frame branch is now enabled only when that current name set
  is non-empty;
* table/table-cell current-reference allowlists remain active for every
  OdtTemplate save/refresh path as established by D5G-C.

No static registry was cleared, copied into document-local mutable state, or
made authoritative. No public or protected signature changed.

## 6. Compatibility retained

The following are deliberately retained:

* `StyleMapper` registration/getter APIs and public static properties;
* direct default `StyleWriter::writeAllStyles()` broad behavior;
* `assign()` / `render()` including styles.xml placeholders;
* protected OdtTemplate hooks and dynamic dispatch;
* ImageElement style identity and wrap/position synchronization;
* CircularImageElement legacy fill/style projections;
* DrawTextBox frame carriers;
* RichTable legacy rendering separate from SR-07 semantic ownership;
* `legacyStructuredValuesMaterialized` and `legacyFrameStylesMaterialized`;
* load/refresh lifecycle semantics;
* public OdtElement legacy getters;
* LegacyStyleRegistry paragraph first-write-wins behavior.

## 7. Package and lifecycle evidence

The new tests inspect `styles.xml` for all changed routing cases. Existing
integration suites inspect `content.xml`, `styles.xml`, manifest entries,
Pictures entries, semantic table definitions, graphic declarations, and
repeated-save output. The D5G-D change removes only a writer branch that had no
current frame names; it does not change any referenced frame, image,
fill-image, table, or table-cell definition. No rendering-relevant structural
XML change was observed, so a LibreOffice run is not required for this narrow
filter activation change.

## 8. Remaining D5G-E work

* final regression closeout across all D5G lifecycle and package cases;
* explicit final documentation of remaining legacy package-resource quirks;
* final review of repeated render/save and refresh/load behavior;
* confirmation that no newly discovered static reader requires a separate
  compatibility decision.

## 9. STYLE-CONTEXT-01 handoff

The remaining global/static StyleContext topics are intentionally deferred:

* legacy paragraph/text lookup fallback in `StyleContext`;
* long-term policy for public static StyleMapper registration;
* LegacyStyleRegistry lifetime and first-write-wins semantics;
* any redesign of StyleWriter's direct compatibility defaults.

These are not changed by D5G-D.

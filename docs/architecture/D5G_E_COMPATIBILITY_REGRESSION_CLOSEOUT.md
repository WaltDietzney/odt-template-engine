# D5G-E — Compatibility Regression Closeout

Status: **FINAL GO**

## 1. Scope

D5G-E is the regression and documentation closeout for the D5G compatibility
sequence. No production code changed in D5G-E. The closeout verifies the
semantic `setElement()` lifecycle, the legacy `assign()` / `render()`
lifecycle, public/protected compatibility, document isolation, repeated
lifecycle behavior, and the documented residual compatibility quirks.

The verified base is D5G-D commit `063b9fc1`.

## 2. D5G A–E summary

* **D5G-A** audited the two structured-element lifecycles and identified the
  document-wide legacy switch and process-global registries.
* **D5G-B** characterized `assign(OdtElement)` / `render()` including dual DOM
  processing, repeated rendering, legacy projections, resources, and refresh/
  load behavior.
* The **D5G Change Contract** preserved public/protected compatibility while
  allowing evidence-based internal narrowing.
* **D5G-C** narrowed table/table-cell finalization to current-document
  references and preserved legacy graphic compatibility.
* **D5G-D** preserved public static/default writer behavior while narrowing the
  OdtTemplate frame finalization branch to current referenced names.
* **D5G-E** verifies the resulting boundaries and closes D5G with documented
  residue.

## 3. Final lifecycle matrix

### Modern semantic path

| Producer | Semantic PRE | Resources PRE | Styles/content/package | Repeated save |
|---|---|---|---|---|
| Paragraph | paragraph/text requirements | complete | document-local styles and content | stable in regression suite |
| RichText | owned paragraph/text requirements | nested resources complete | document-local styles/content | stable |
| ListElement | owned list requirements | nested resources complete | document-local styles/content | stable |
| ImageElement | no semantic graphic producer currently | asset discoverable | native frame/content path; no semantic graphic definition | characterized |
| CircularImageElement | graphic requirement complete | fill-image and asset complete | semantic graphic/fill declaration and package resource | stable |
| DrawTextBox | graphic requirement complete | not applicable | document-local graphic/frame definition | stable |
| RichTable | table/column/row/cell requirements complete | nested assets complete | semantic table families in owning parts | stable |

### Legacy `assign()` / `render()` path

| Producer | content.xml | styles.xml | manifest/Pictures | legacy projection |
|---|---|---|---|---|
| Paragraph | inserted through legacy replacement | compatibility styles remain supported | unchanged | getters/legacy registration preserved |
| RichText | inserted through legacy replacement | producer-specific legacy behavior characterized | unchanged | preserved |
| ListElement | inserted through legacy replacement | producer-specific legacy behavior characterized | unchanged | preserved |
| ImageElement | may reference `Pictures/...` | current image style adoption only | known resource omission retained | placement/style identity stable |
| CircularImageElement | graphic structure inserted | fill/image compatibility adopted when referenced | legacy resource side effect characterized | fill/style fields retained |
| DrawTextBox | frame structure inserted | current frame carrier adopted | unchanged | frame compatibility retained |
| RichTable | legacy table structure inserted | current table/cell definitions only | unchanged unless nested assets | not redirected to semantic path |

### Mixed lifecycle

Existing mixed-lifecycle and isolation tests cover combinations of semantic
and legacy paragraphs, graphics, images, circular images, and tables. The
observed invariant is:

```text
semantic requirements remain document-local and authoritative
legacy definitions are adopted only when current references require them
unrelated static registrations remain out of serialized document output
```

No semantic requirement is suppressed by legacy insertion, and no unrelated
global table, table-cell, frame, image, or fill-image entry is adopted.

### Missing placeholders

| Assigned value | Current output effect |
|---|---|
| Paragraph | no unrelated table/cell definition |
| ImageElement | no unreferenced image style |
| CircularImageElement | no unreferenced fill-image declaration; legacy resource side effect remains possible |
| DrawTextBox | no unreferenced frame style |

The legacy boolean may still record entry into the structured path, but current
document references govern serialized compatibility definitions.

## 4. Public compatibility

Verified and retained:

* `StyleMapper` registration/getter APIs and static registries;
* direct default `StyleWriter::writeAllStyles()` behavior;
* `assign()`, `render()`, `save()`, `refresh()`, and `load()`;
* public OdtElement legacy getters;
* `HasStyles` compatibility;
* SR-06 and SR-07 public producer behavior.

No public signature or static API was removed or redefined.

## 5. Protected compatibility

The protected compatibility suite remains green. It verifies dispatch through:

* `fixBrokenVariables()` and `setValuesInDom()` during `render()`;
* `replacePlaceholderWithDom()` during structured insertion;
* save/page-layout hooks such as `adjustBulletIndentation()`;
* the protected `injectImageStyles()` facade.

No visibility or dynamic-dispatch boundary changed.

## 6. Static registry isolation

The static registries remain process-global compatibility state. Current
document evidence is the adoption boundary:

* frame: current `draw:style-name` references;
* image: current graphic style references;
* fill-image: current fill-image names or referenced image styles;
* table: current table structural references;
* table-cell: current cell references plus semantic-owned exclusions;
* paragraph/text: OdtTemplate disables broad legacy writer output while direct
  public writer compatibility remains available.

Two-document tests show that a style/resource used by document A remains
observable in the global registry but is not serialized into unrelated
document B.

## 7. Repeated lifecycle

Characterized and regression-covered operations include:

* repeated `render()`;
* repeated `save()`;
* `render()` → `save()` → `render()` → `save()`;
* `refresh()`;
* `load()`;
* independent template instances.

Content and structural references remain stable. Style serialization is treated
according to the producer-specific D5G characterization rather than a new
universal byte-identity rule. `load()` resets document-local lifecycle state but
does not clear static registries. `refresh()` retains its historical persist
and reload semantics and is not treated as an alias for `save()`.

## 8. Package and resources

Regression coverage inspects:

* `content.xml`;
* `styles.xml`;
* `META-INF/manifest.xml`;
* `Pictures/*` where applicable;
* semantic table and graphic declarations;
* repeated-save package contents.

No D5G-E production change occurred, so no new rendering-relevant XML or
package structure was introduced. A new LibreOffice regression was therefore
not required. Existing SR-07H visual evidence remains the accepted visual gate.

## 9. Retained compatibility quirks

The following are explicitly observed and intentionally retained:

1. Legacy ImageElement may emit a `Pictures/<file>` reference without copying
   the physical file through the legacy path.
2. CircularImageElement may establish legacy fill/resource compatibility state
   before a missing placeholder is known to be unreplaced.
3. `assign()` / `render()` processes `content.xml` and `styles.xml` separately;
   one element may receive multiple `toDomNode()` calls.
4. `refresh()` has historical reload semantics distinct from `save()`.
5. StyleMapper registries remain public/process-global compatibility state.
6. Direct StyleWriter defaults remain broad and are not document-local semantic
   ownership.
7. Legacy OdtElement getters remain available and override-sensitive.

None of these quirks was silently corrected in D5G-E.

## 10. D5G exit criteria

| Criterion | Result |
|---|---|
| D5F semantic lifecycle remains authoritative | PASS |
| Legacy assign/render remains supported | PASS |
| Protected polymorphism remains effective | PASS |
| Public legacy getters/static APIs remain available | PASS |
| Document-wide routing narrowed where evidenced | PASS |
| Duplicate internal finalization reduced where evidenced | PASS |
| Document isolation preserved | PASS |
| Mixed semantic/legacy documents remain valid | PASS |
| Repeated lifecycle regression-covered | PASS |
| Legacy graphics/table/resource residue documented | PASS |
| No behavior correction hidden in closeout | PASS |
| Full automated preflight green | PASS |
| Rendering-relevant changes appropriately reviewed | PASS — no D5G-E production change |
| Static/global residue handed off cleanly | RETAINED RESIDUE |

## 11. STYLE-CONTEXT-01 handoff

The next architecture closeout may address, but D5G-E does not implement:

* paragraph/text fallback lookup in `StyleContext`;
* `LegacyStyleRegistry` lifetime and first-write-wins policy;
* long-term public static StyleMapper policy;
* direct broad StyleWriter compatibility defaults;
* remaining current-document adoption helper consolidation;
* final policy for public legacy getter facades.

These are explicit compatibility/design handoff items, not D5G failures.

## 12. Validation

Focused D5G/SR-06/SR-07/protected compatibility suite:

* 117 tests;
* 990 assertions;
* 7 warnings;
* 6 PHPUnit deprecations.

Full Composer suite:

* 585 tests;
* 3680 assertions;
* 1 warning;
* 7 PHPUnit deprecations;
* no failures.

Also completed:

* PHP lint for `src/` and `tests/`;
* `composer validate --no-check-publish`;
* `git diff --check`.

The local documentation build toolchain was unavailable in the worktree and
was not simulated.

## 13. Final verdict

**D5G-E COMPLETE WITH DOCUMENTED RESIDUE**

**D5G COMPATIBILITY CLOSEOUT — FINAL GO**

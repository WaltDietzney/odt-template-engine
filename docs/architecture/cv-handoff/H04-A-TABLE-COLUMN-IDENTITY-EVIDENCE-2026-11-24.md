# H04-A — Generated table-column identity characterization

Status: **Accepted / characterization complete** (24 November 2026)

H04-A records current engine behavior for the CV handoff's H04 requirement,
TABLE-COLUMN-IDENTITY-01. It adds no source behavior, public API, sample, or
Writer-specific implementation. H04-B remains a separate design and
implementation decision.

## Evidence basis

- Original CV handoff: CV repository `research/engine-handoff/UEBERGABE-AUFTRAEGE.md`, inspected on Patil; local [handoff index](README.md), which names H04 as
  table-column identity and requires an engine capability comparison before
  implementation.
- Existing table closeout: [`TABLE_LAYOUT_01_CLOSEOUT.md`](../TABLE_LAYOUT_01_CLOSEOUT.md),
  which records that generated columns currently use positional `coN` names and
  leaves collision policy unresolved.
- Current implementation: `RichTable` emits semantic `co0`, `co1`, ...
  requirements for generated columns; direct DOM rendering delegates absolute
  columns to `StyleWriter`, which also emits positional names.

## Characterized behavior

The focused suite is `tests/Integration/H04TableColumnIdentityCharacterizationTest.php`.

| Case | Observed behavior | Evidence assertion |
| --- | --- | --- |
| Two generated absolute definitions, same position, different widths | Document-local semantic registration rejects the second definition as a conflicting `co0` identity | `LogicException` |
| Generated absolute plus generated relative definition at the same position | The absolute `co0` and relative `co0` definitions conflict | `LogicException` |
| Two identical generated definitions | The semantic context reuses one definition and materializes one `co0`/`co1` style per document | one style each; requested widths retained |
| Authored automatic `co0` plus generated `co0` | The authored style remains authoritative; the generated definition is not appended | one `co0`, authored width retained |
| Direct DOM, one table | Positional names are emitted and table-column references resolve to the emitted widths | `co0`/`co1`, `3cm`/`6cm` |
| Direct DOM, two conflicting tables | The legacy writer appends two same-name `co0` definitions and both table references use `co0` | two definitions; `2cm` and `7cm` |
| Repeated save | The semantic path remains single-definition and geometry-stable | one style each on both saves |
| Save, reload, save | Existing serialized `co0`/`co1` definitions and references survive reload with geometry unchanged | one style each; `2cm`/`4cm` |

“Resolved geometry” here means the width reached by following each
`table:table-column/@table:style-name` to the corresponding
`style:table-column-properties` in `content.xml`. It does not claim visual
layout equivalence in LibreOffice, and H04-A contains no new Writer gate.

## Interpretation boundary

The semantic path already distinguishes a true same-identity conflict from an
identical definition. The direct-DOM compatibility path does not perform that
semantic conflict check and can serialize ambiguous duplicate identities.
Authored automatic styles are existing document data and currently win over a
generated definition with the same family/name in materialization.

These are compatibility facts to preserve or explicitly revise in H04-B; they
are not approval for an automatic rename policy.


## Review correction evidence

Baseline: Forgejo develop ec89001. The original eight tests remain intact.
Eight additional tests cover the native construction path and compatibility:

- Two named tables inserted with appendBody share equal definitions, survive
  two explicit render calls and retain their individual column references.
- Conflicting absolute or mixed geometry through appendBody throws and retains
  the first table; the rejected second table is absent.
- Standalone relative DOM emits co0/co1 references without definitions.
- Mixed direct DOM can resolve a relative reference to an absolute definition.
- A namespace-conformant authored co0 (9cm) is reused for a requested 2cm.
  This differs from the duplicate-definition legacy DOM case.
- Public StyleWriter returns positional names and appends duplicate definitions.
- Two document instances in one process retain independent widths.
- A same-name automatic definition in styles.xml does not suppress a generated
  content.xml definition; this is XML evidence, not a Writer resolution claim.

Focused H04 suite: 16 tests, 158 assertions, no failures; existing deprecation
notices remain. No production behavior changed. H04-B and its Writer gate remain open.

Review correction preflight (PHP 8.5.4 / PHPUnit 11.5.58): focused regression
including PublicSampleSmokeTest: 33 tests / 756 assertions; full composer test:
1098 tests / 8852 assertions, no failures (1 warning, 4 deprecations, 8 PHPUnit
deprecations). PHP lint: 386 files; composer validate and diff checks passed.
Earlier remote-image failures did not recur. No source or sample changes.

Strict Zensical documentation build: passed, no issues (27.62s).
H04-A accepted following review correction; H04-B implementation and Writer gate remain separate.

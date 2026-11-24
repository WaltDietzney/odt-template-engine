# P2 consolidation evidence — 2026-11-24

Canonical status: [P2 consolidation](../../../docs/architecture/cv-handoff/P2-CONSOLIDATION-2026-11-24.md).
Owner accepted H05–H09 native feasibility/design. This directory adds transport,
comparison and provenance evidence; it does not integrate research classes into src.

## Reproduce without overwriting accepted evidence

From /srv/projects/odt-template-engine-p2:

```sh
python3 research/p2-capability-audit/consolidation/reproduce.py --render
python3 research/p2-capability-audit/consolidation/verify_replay.py
```

The first command creates a fresh timestamped replay tree. The second verifies
source-to-Writer renders and available original rendered references, and checks
protected accepted research hashes. latest-replay.json identifies the complete
report. Without --render, only package generation/comparison is requested; this
is not a render PASS. Full verification requires a rendered replay.

Dependencies: current PHP source and research bootstrap, Python 3, LibreOffice,
pdftoppm, pdfinfo, pdftotext; fixed installed fonts. Tested renderer is LibreOffice
26.2.6.3 and PHP 8.5.4. Runtime/font changes require new comparison rather than
assuming pixel identity. PHP 8.2 compatibility is an implementation gate, not
established by this replay. Historical scripts are retained unchanged; transport
patches and commands are recorded in the replay report. The E011 input is pinned
under inputs; the existing repository table fixture is used by its current path.

## Recorded result

- 19 package comparisons pass after bounded generated-style identity normalization.
- 18 rendered documents / 21 pages match their fresh Writer roundtrips.
- 17 available reference documents / 20 pages match the replay.
- All protected accepted research bytes are unchanged.
- 52 historical PDFs/PNGs recovered from the old renderer tree were SHA-256 verified.
- No original PDF for p2-v01-padding-spacing was recovered. Its accepted ODT is
  preserved; new renders under v01/reproduced-render are explicitly regenerated.

StyleMapper generates random para_[hex] paragraph names. compare_packages.py
normalizes only those paragraph definitions and their typed references using
exact definitions/scope. All content, order, geometry and Pictures bytes remain
compared. This is comparison-only; no canonical package or engine code is changed.
ZIP/XML byte determinism is not claimed.

## Provenance and preservation

- evidence-before.json: source/live Forgejo baseline, 967-file hash inventory,
  verified local checkpoint, pinned inputs and temporary-tree comparison.
- recovered-render-provenance.json: recovered source/destination checksums.
- regenerated-render-provenance.json: fresh padding render provenance.
- latest-replay.json and replay-report.json: commands, comparisons and final summary.
- replay-summary.json: compact durable copy of the verified result.
- legacy-notes: former handoff retained before replacing the active entrypoint.

The manual padding input equals the saved Writer reference, rather than the old
temporary generator output. This is documented history, not silently overwritten.
Checkpoints are local same-host copies, not verified independent/off-host backups.
Purchased reference rights/provenance remain to be checked before public distribution.
Replay caches, docs-build snapshots, Python caches and checkpoint archives are
ignored by research .gitignore; accepted source/artifact directories are not ignored.
No git staging, commit, push, merge or deployment is performed by these scripts.

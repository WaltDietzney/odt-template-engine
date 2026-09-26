# F6.3 — Release-Candidate Handoff and Freeze

**Status:** READY FOR FREEZE — FINALIZATION-01 handoff candidate

## Purpose

This record defines the final F6.3 handoff required by the accepted `FINALIZATION_01_PLAN.md`.

F6.1 established the repository-wide validation evidence. F6.2 recorded the completed finalization work, bounded corrections, deferred findings, retained Compatibility/Deprecated surfaces, and architecture-integrity conclusion.

F6.3 does not perform another design, implementation, documentation, or feature milestone. Its purpose is to fix the resulting FINALIZATION-01 baseline as the candidate to be validated by the separate:

**RELEASE-1.0 INTEGRATION PRE-FLIGHT**

The release integration pre-flight, not F6.3, decides whether this frozen candidate is suitable for the 1.0 release.

## Freeze scope

When this F6.3 handoff is merged to `develop`, the resulting `develop` commit becomes the FINALIZATION-01 release-candidate baseline.

At that boundary, the following are considered fixed for the candidate:

- public API shape and its Recommended / Advanced / Compatibility / Deprecated classifications;
- documentation information architecture and learning path;
- canonical L/C/B/S public sample surface;
- project presentation and voluntary support presentation;
- Composer/Packagist distribution contract and supported environment;
- accepted template/Writer-native/mapping/preflight/automation semantics already established before FINALIZATION-01;
- retained Compatibility and Deprecated behavior documented by the 1.0 reference.

The exact post-merge commit SHA is the freeze identity. It must be recorded by the RELEASE-1.0 INTEGRATION PRE-FLIGHT before integrated release validation begins.

## Evidence accepted into the handoff

The candidate inherits the completed FINALIZATION-01 evidence.

### F1 — Canonical Sample Surface

**PASS / COMPLETE.**

The public sample surface uses the canonical L/C/B/S taxonomy. Historical numbered material is not part of the normal public learning surface.

### F2 — Public API 1.0

**PASS / COMPLETE.**

The public PHP surface is inventoried, classified, reconciled, and documented according to the accepted reference contract.

### F3 — Documentation & Learning Path

**PASS / COMPLETE.**

README, guides, API reference, navigation, samples, and Sample Explorer teach the same three complementary structure-ownership models and the accepted explicit lifecycle.

### F4 — Project Presentation & Support

**PASS / COMPLETE.**

Public presentation and voluntary project-support visibility are reconciled with the final 1.0 product story.

### F5 — Distribution & Consumer Readiness

**PASS / COMPLETE.**

The package metadata/environment contract was audited, a clean external Composer consumer workflow succeeded, all three product models were exercised from the installed dependency, and representative output was opened successfully in LibreOffice.

### F6.1 — Repository-wide Preflight

**PASS / COMPLETE.**

The final repository gate recorded:

- Composer install: PASS;
- strict Composer validation: PASS;
- PHP lint across `src/` and `tests/`: PASS;
- full PHPUnit suite: 995 tests / 7,656 assertions — PASS;
- PublicSampleSmoke coverage: PASS as part of the full suite;
- `git diff --check`: PASS;
- strict Zensical build: PASS;
- release-facing consistency scans: REVIEWED / no unresolved blocker;
- GitHub CI PHP 8.2 / 8.3 / 8.4: PASS;
- GitHub documentation build: PASS;
- LibreOffice boundary: satisfied by recent F5.2 evidence because F6.1 introduced no rendering-sensitive engine change.

### F6.2 — Finalization Closeout

**PASS / COMPLETE.**

The closeout record confirms the completed gates, bounded finalization corrections, deferred findings, retained Compatibility/Deprecated surfaces, validation results, and that FINALIZATION-01 introduced no new document architecture.

## Freeze rules

After the F6.3 handoff is merged, the candidate is frozen for integrated release validation.

The RELEASE-1.0 INTEGRATION PRE-FLIGHT must validate the exact frozen baseline. It must not become another architecture or general cleanup milestone.

During the freeze:

1. no new public API shape is added or redesigned;
2. no new template syntax or document-model architecture is introduced;
3. no canonical sample taxonomy or documentation information architecture is redesigned;
4. no Compatibility/Deprecated surface is removed merely for cleanup;
5. no unrelated refactoring is admitted;
6. deferred post-1.0 capabilities remain deferred unless the integrated pre-flight proves a concrete accepted-1.0 blocker.

If the release integration pre-flight finds a concrete blocker:

1. characterize the blocker against accepted 1.0 semantics;
2. apply only the bounded correction required by that blocker;
3. repeat the affected automated and/or LibreOffice acceptance checks;
4. repeat any broader integration check whose evidence was invalidated;
5. establish and record a new frozen candidate commit.

A failed release-preflight check is therefore evidence to investigate, not permission to reopen FINALIZATION-01 design.

## Known non-blocking observations carried forward

The freeze does not erase known observations that were explicitly classified as non-blocking:

- `SECURITY.md` retains its pre-1.0 `master` security-fix wording and requires release-time policy review when stable 1.0 is actually published;
- the F6.1 PHPUnit run reported one test-fixture `mkdir(): File exists` warning;
- PHPUnit reported aggregate deprecations without evidence of an accepted-1.0 engine defect;
- post-1.0 architecture/capability topics remain in `FUTURE_DEVELOPMENT.md`.

These items must not be silently converted into release-preflight scope unless concrete evidence establishes a 1.0 blocker.

## RELEASE-1.0 INTEGRATION PRE-FLIGHT handoff

The separate release pre-flight receives:

- the exact frozen post-F6.3 `develop` commit;
- the F6.1 repository validation record;
- the F6.2 finalization closeout record;
- the accepted 1.0 API/reference contract;
- canonical samples and public documentation;
- clean-consumer/distribution evidence;
- existing manual LibreOffice evidence and all relevant architecture/change-contract evidence.

Its responsibility is the final integrated proof of the candidate, including the broader release-level checks defined for that phase. It may validate integrated visual/rendering, save/reopen, export, professional-document, packaging, and other release acceptance boundaries without redesigning the product.

F6.3 intentionally does not duplicate that integrated proof.

## Acceptance

F6.3 passes when:

- this handoff record is merged to `develop`;
- CI/documentation checks for the handoff change are green;
- the resulting post-merge `develop` SHA is treated as the frozen candidate identity;
- RELEASE-1.0 INTEGRATION PRE-FLIGHT begins from that exact identity.

Until merge, this document remains **READY FOR FREEZE** rather than claiming a freeze that has not yet happened.

## Conclusion

All substantive FINALIZATION-01 work is complete. This F6.3 record establishes the boundary between product/repository finalization and final integrated release validation.

After successful merge and recording of the resulting `develop` SHA:

**FINALIZATION-01 is COMPLETE and the frozen candidate is handed to RELEASE-1.0 INTEGRATION PRE-FLIGHT.**

# H02 C3 — Master region mutation

Adds `PageLayoutOdtTemplate::updatePageStyle(string $name, array $options): static`.
Only header and footer keys are supported. Omitted regions remain unchanged;
an OdtElement replaces or creates a normal region, null removes it, and an
empty Paragraph retains an empty region. Authored left/first variants block
mutation of the corresponding region. Layout, fills and succession are unchanged.
Resource copying and semantic style materialization reuse existing services;
failure restores document state and removes newly copied assets.

C07 demonstrates replacement and creation. Focused tests cover reopen,
invalid options, variants, native block rejection, rollback and image manifest.
Visual candidate: tmp/h02-c3/C07.odt. Manual acceptance pending.

## Acceptance (2026-11-18)

Owner confirmed visual acceptance of C07. Full suite: 1059 tests, 8445 assertions, no errors or failures; 1 warning, 4 deprecations, 8 PHPUnit deprecations. Sample registry and smoke: 7 tests, 844 assertions. PHP lint, composer validate and git diff --check passed. Writer roundtrip and documentation build not yet verified. No commit or push yet.

Writer roundtrip verified: updated header and new footer preserved after native ODT save. Documentation build blocked: zensical is not on PATH.

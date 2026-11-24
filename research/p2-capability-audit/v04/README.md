# P2-V04 – Native contact composition
Synthetic contact data. Existing engine APIs only. generate.py derives template
from existing Writer-authored body/header User Field fixture; bind.php uses
setUserField, assign, render and save. Existing dependency autoloader from H04
worktree is used with PSR-4 explicitly redirected to P2 src.
Bare string conditions failed with TypeError (ConditionExpression boolean filter
returns null for ordinary address text). generate-bare-condition.py preserves
the rejected template generator; do not change legacy semantics in H07.
Use explicit boolean show_* flags computed by consumer from trimmed values.
Full/minimal/long variants rendered in Writer; body/header field reevaluates
to Ada Example, absent rows removed, long address/email wraps.
Roundtrip text and PNG comparisons identical for all three variants.
Human visual review pending; narrow sidebar and all-empty case not yet tested.

## Owner correction – contact authoring contract
Contact values must exclusively use native Writer User Fields, including
address components, phone, mobile, email and website. No classic template
variables or classic conditional markers in the contact template.
The initial V04 classic-condition prototype is historical partial evidence,
not an accepted construction recipe. Symbols must be independently variable.
Investigate native conditional paragraph visibility including anchored icons;
distinguish hiding from actual removal and assess retained data. No new API
or production change approved.

## Superseding native contact evidence
The classic-variable documents above remain historical only. Continue with
[native/README.md](native/README.md) for the native-field H07 A–F prototypes,
failed approaches, exact recipes, checks and pending owner visual gate.

Compact class-based composition now continues in [composition/README.md](composition/README.md).
Earlier table-based candidates are retained as historical technical evidence.

# Advanced & Compatibility

Most applications should start with the Recommended API documented in the task-oriented reference.

This area exists for two different audiences:

- **Advanced API** — supported 1.0 capabilities with narrower preconditions, deeper ODF/Writer semantics, tooling roles, or orchestration responsibilities.
- **Compatibility API** — retained behavior for existing applications and historical integrations. New code should normally use the stated replacement.

Public PHP visibility alone does not make a class or method application API. Infrastructure that is public for collaboration, testing, serialization, or internal extension mechanics remains outside the normal end-programmer reference.

## Choose the right section

- [Advanced API](advanced.md) — supported specialist capabilities and extension seams.
- [Compatibility & Deprecated API](compatibility.md) — retained historical calls, behavioral differences, and preferred replacements.

Deprecated APIs are **not removed for 1.0** merely because a preferred replacement exists. Removal belongs to a later explicit compatibility decision.

Likewise, retired APIs that no longer exist in the current source are not resurrected as “compatibility APIs” by this documentation.

## Classification rule

```text
Recommended
    normal path for new application code

Advanced
    supported, but requires specialist lifecycle/ODF/tooling knowledge

Compatibility
    retained for existing callers; prefer the documented replacement

Deprecated
    compatibility surface explicitly discouraged for new code

Infrastructure / Hidden
    public in PHP for technical reasons, but not an end-programmer API
```

These classifications describe the 1.0 contract; they are not a promise that every public PHP symbol will remain public forever.

# RESEARCH-01A — Declarative Section Fixture Evidence

## Status

**Research evidence only. No syntax or API contract is approved by this note.**

This note records the empirical LibreOffice evidence for using a native Writer Section name as a possible carrier of engine-specific structural template semantics.

## Fixture

User-authored LibreOffice fixture:

```text
06 -foreachl-sections.odt
```

The Section was named in Writer as:

```text
#foreach:expiriene
```

The misspelling is preserved here deliberately because it is part of the fixture evidence and also illustrates a future diagnostics requirement.

## ODF representation

The saved `content.xml` contains the Section name unchanged:

```xml
<text:section
    text:style-name="Sect1"
    text:name="#foreach:expiriene">
    <text:p text:style-name="P1">Das ist ein Bereich</text:p>
</text:section>
```

This confirms that, for this Writer-authored fixture:

- `#` is accepted in the Section name;
- `:` is accepted in the Section name;
- Writer serializes the name directly as `text:name`;
- the semantic-looking name does not replace the normal native Section structure;
- the Section remains associated with its ordinary Section style through `text:style-name`.

The observation is therefore compatible with a model in which the native Section continues to own layout and document structure while its name optionally carries a declarative template role.

Conceptually:

```text
text:name="#foreach:experience"
    -> possible template declaration

text:style-name="Sect1"
    -> ordinary native Section styling/layout
```

These concerns remain orthogonal in the characterized fixture.

## Architecture relevance

The existing SECTION-03 implementation already provides the difficult mutation semantics required for repeated Sections:

- named Section resolution;
- repeated instantiation;
- item-local scalar binding;
- deterministic native identity rewriting;
- prototype removal for collection finalization;
- empty-collection behavior;
- rollback after a failed item;
- nested Section collections;
- save/reopen persistence.

A declarative name such as:

```text
#foreach:experience
```

would therefore be investigated as a discovery/mapping layer over the existing structured Section operation, not as a second foreach renderer.

The working semantic model is:

```text
Writer Section: #foreach:experience
    -> discover declaration
    -> resolve application collection: experience
    -> existing instantiateMany() semantics
    -> bind {{...}} values inside each instance
    -> remove/finalize prototype
```

## Hybrid authoring hypothesis

The current preferred research direction separates structural control from value binding.

Example template:

```text
Section: #foreach:experience

    {{position}}
    {{company}}
```

The native Section declares the repeatable document block. The existing `{{...}}` syntax remains responsible for item-local scalar values.

This avoids the scope problem of document-global Writer User Fields inside collections and retains a materializable, DOCX-friendly value-binding path.

Native Writer fields and conditions remain relevant for cases in which they provide genuine Writer/ODF semantic value, especially document-global values and richer conditional logic. They are not currently assumed to replace `{{variable}}` generally.

## Diagnostics implication

The fixture's actual name is:

```text
#foreach:expiriene
```

A future declarative processor must not silently guess that this means `experience`.

If the application provides no `expiriene` collection, diagnostics should report the template declaration and missing data key explicitly. Fuzzy correction would hide authoring errors and would make template semantics non-deterministic.

## Remaining characterization

Before any naming syntax is approved, create a canonical fixture with:

```text
Section: #foreach:experience

    {{position}}
    {{company}}
```

Then verify:

1. exact `content.xml` representation;
2. Writer save/reopen stability;
3. current `section('#foreach:experience')` resolution;
4. current `instantiateMany()` behavior with `#` and `:` in the prototype name;
5. generated instance names and native identity rewriting;
6. item-local placeholder binding for at least two items;
7. empty collection behavior;
8. save/reopen of the finalized ODT;
9. whether the final static ODT converts cleanly to DOCX;
10. whether any current regex or identity code incorrectly assumes `\w+` Section names.

The purpose of this next fixture is not to implement automatic declaration discovery yet. It is to prove that the **existing SECTION-03 machinery accepts the candidate native name unchanged**.

## Current conclusion

The empirical evidence supports continued investigation of named native Sections as declarative structural carriers.

It does **not** yet establish `#foreach:...` as public syntax.

The strongest current working direction remains:

> Use native ODT objects for document structure and selected structural template semantics, keep `{{...}}` for simple portable value binding, and use Writer field/condition semantics selectively where they add genuine authoring or document-semantic value.

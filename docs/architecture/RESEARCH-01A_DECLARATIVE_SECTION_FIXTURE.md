# RESEARCH-01A — Declarative Section Fixture Evidence

## Status

**Research evidence only. No syntax or API contract is approved by this note.**

This note records the empirical LibreOffice and current-engine evidence for using a native Writer Section name as a possible carrier of engine-specific structural template semantics.

## 1. Initial Writer fixture

User-authored LibreOffice fixture:

```text
06 -foreachl-sections.odt
```

The Section was named in Writer as:

```text
#foreach:expiriene
```

The misspelling is preserved here deliberately because it is part of the fixture evidence and also illustrates a future diagnostics requirement.

### ODF representation

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

The observation is compatible with a model in which the native Section continues to own layout and document structure while its name optionally carries a declarative template role.

Conceptually:

```text
text:name="#foreach:experience"
    -> possible template declaration

text:style-name="Sect1"
    -> ordinary native Section styling/layout
```

These concerns remain orthogonal in the characterized fixture.

## 2. Canonical placeholder fixture

A second Writer-authored fixture was created specifically to test the existing SECTION-03 path:

```text
07-foreach-section-placeholders.odt
```

Its document structure is:

```text
ABSATZ VORHER

Section: #foreach:experience
    {{position}}
    {{company}}

ABSATZ NACHHER
```

Writer serializes the candidate declaration as an ordinary native Section whose name is exactly:

```text
#foreach:experience
```

The `#` and `:` characters therefore survive normal Writer authoring and ODF serialization without normalization.

## 3. Current-engine characterization

The canonical fixture was processed with the current engine only. No production code, template-processing feature, Section service, or automatic `#foreach` interpretation was added.

The characterization used the existing public structured Section API:

```php
$template = new \OdtTemplateEngine\OdtTemplate(
    'research/07-foreach-section-placeholders.odt'
);

$template
    ->section('#foreach:experience')
    ->instantiateMany([
        [
            'position' => 'Projektleiter',
            'company' => 'Firma A',
        ],
        [
            'position' => 'Entwickler',
            'company' => 'Firma B',
        ],
    ]);

$template->save('/tmp/research-07-foreach-section-placeholders-result.odt');
```

The saved result was then reopened through `OdtTemplate` to characterize persistence.

### Observed behavior

The current engine successfully:

1. resolved `section('#foreach:experience')`;
2. executed the existing `instantiateMany()` path;
3. bound both item-local placeholder sets correctly;
4. generated deterministic Section names:
   - `#foreach:experience_1`
   - `#foreach:experience_2`;
5. removed the original `#foreach:experience` prototype;
6. preserved `ABSATZ VORHER` and `ABSATZ NACHHER` unchanged;
7. saved the resulting ODT;
8. reopened the saved ODT successfully.

The relevant resulting structure was:

```xml
<text:p>ABSATZ VORHER</text:p>
<text:section text:name="#foreach:experience_1">
  <text:p>Projektleiter</text:p>
  <text:p>Firma A</text:p>
</text:section>
<text:section text:name="#foreach:experience_2">
  <text:p>Entwickler</text:p>
  <text:p>Firma B</text:p>
</text:section>
<text:p>ABSATZ NACHHER</text:p>
```

No exception, XPath problem, normalization, naming problem, identity-rewrite problem, or lifecycle failure was observed because of `#` or `:`. Both characters were preserved in the generated Section identities.

The generated output was deliberately written outside the repository. No `samples/output/` artifact was touched by the experiment.

## 4. Architecture relevance

This characterization answers an important mechanical question.

The existing SECTION-03 implementation already provides the mutation semantics required for a declarative repeated Section:

- named Section resolution;
- repeated instantiation;
- item-local scalar binding;
- deterministic native identity rewriting;
- prototype removal for collection finalization;
- empty-collection behavior;
- rollback after a failed item;
- nested Section collections;
- save/reopen persistence.

The experiment proves that these existing mechanics also accept a prototype named `#foreach:experience` unchanged.

Therefore, a future declarative name such as:

```text
#foreach:experience
```

would not require a second foreach renderer merely to perform collection instantiation. The remaining candidate capability is principally a **declaration discovery and orchestration layer** over the existing structured Section operation.

The working semantic model is:

```text
Writer Section: #foreach:experience
    -> discover declaration
    -> resolve application collection: experience
    -> existing instantiateMany() semantics
    -> bind {{...}} values inside each instance
    -> remove/finalize prototype
```

This is a stronger conclusion than the initial Writer-only fixture allowed: the candidate native name is not only representable in ODF, it is already compatible with the current SECTION-03 execution mechanism.

## 5. Hybrid authoring hypothesis

The current preferred research direction separates structural control from value binding.

Example template:

```text
Section: #foreach:experience

    {{position}}
    {{company}}
```

The native Section can carry the repeatable document boundary. The existing `{{...}}` syntax remains responsible for item-local scalar values.

This avoids the scope problem of document-global Writer User Fields inside collections and retains a materializable, DOCX-friendly value-binding path.

Native Writer fields and conditions remain relevant for cases in which they provide genuine Writer/ODF semantic value, especially document-global values and richer conditional logic. They are not currently assumed to replace `{{variable}}` generally.

## 6. Diagnostics implication

The first fixture's actual name is:

```text
#foreach:expiriene
```

A future declarative processor must not silently guess that this means `experience`.

If the application provides no `expiriene` collection, diagnostics should report the template declaration and missing data key explicitly. Fuzzy correction would hide authoring errors and make template semantics non-deterministic.

## 7. What is proven and what remains undecided

### Proven by characterization

- Writer can author and serialize Section names containing `#` and `:`.
- `#foreach:experience` survives as the native `text:name` value.
- The current Section resolver accepts that exact name.
- Existing `instantiateMany()` can clone that Section repeatedly.
- Existing item-local `{{...}}` binding works inside the clones.
- Existing identity rewriting produces valid deterministic names while preserving the candidate prefix.
- Collection finalization removes the prototype.
- The resulting ODT survives save/reopen through the engine.

### Not decided by this research

- `#foreach:...` is not yet an approved public template syntax.
- Automatic discovery of such declarations is not yet designed.
- Render lifecycle ordering for automatic structural processing is not yet contracted.
- Missing-data diagnostics and validation behavior are not yet designed.
- No decision is made here about `#if`, `#ifnot`, `#with`, or a broader object-name language.
- No decision is made here to replace current visible foreach syntax.
- Finalization/materialization for DOCX remains a separate interoperability architecture question.

This distinction is deliberate: the experiment validates the execution substrate without prematurely approving the declarative frontend.

## 8. RESEARCH-01A conclusion

The declarative Section hypothesis is now mechanically well supported.

> A future declarative `#foreach:collection` can be designed as a template-side semantic declaration over the existing SECTION-03 structured instantiation path rather than as a new collection-processing engine.

The broader RESEARCH-01 direction remains:

> Use native ODT objects for document structure and selected structural template semantics, keep `{{...}}` for simple portable value binding, and use Writer field/condition semantics selectively where they add genuine authoring or document-semantic value.

The next planning step should not automatically be implementation of declarative foreach. RESEARCH-01 was established to improve the evidence available for roadmap decisions. Its findings should therefore be evaluated together with the existing `ROADMAP.md`, `FUTURE_DEVELOPMENT.md`, architecture decisions, implementation state, interoperability requirements, and version-1.0 goals before selecting the next implementation milestone.

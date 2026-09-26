# F4.1 — Website / Sample Explorer Presentation Audit

**Status:** COMPLETE — bounded FINALIZATION-01 F4.1 presentation slice

## Objective

Review the public project entrance and Sample Explorer from a new-user
perspective after F3 stabilized the 1.0 terminology, learning path, API
reference, and canonical samples.

The presentation should expose the product that now exists rather than the
earlier template-engine-only story.

## Evidence reviewed

The audit used:

- the current public project URL and documentation destinations;
- the deployed-site source in `demo/sample-explorer/`;
- the canonical `samples/sample-registry.php`;
- the completed F3 README and documentation model;
- the FINALIZATION-01 F4 contract.

The external web fetch available during the audit could not retrieve the live
site, so presentation changes were verified against the repository source that
defines the Sample Explorer/project page. Live deployment verification remains
required after integration.

## Audit findings

### Strong existing presentation

The project page already had a useful first screen, Composer installation,
editable-ODT value proposition, live generation, canonical registry-backed
sample cards, a professional CV showcase, real-world project links, project
support, documentation navigation, and GitHub/Packagist destinations.

The Sample Explorer already consumes only registry entries with
`status === 'canonical'`, so the public sample inventory follows the F1
L/C/B/S surface mechanically.

### Stale product story

The first-screen and explanatory sections still primarily described the older
template-first feature set: variables, conditions, loops, images, rich text,
lists, tables, styles, and metadata.

That was no longer enough to explain the completed 1.0 product. The page did
not teach the three ownership models established in F3 and did not make the
Writer-native/inspection/mapping capabilities visible at the product level.

### Documentation routing

The feature section labeled a GitHub repository link as “Read the documentation
on GitHub”. The project now has a dedicated published documentation site and a
completed Practical API Reference, so these are the appropriate user
destinations.

### Professional showcase wording

S01b was already visually prominent and broadly correct, but its copy could
state the mixed ownership model more precisely instead of presenting page
layout as the important API lesson.

## Applied F4.1 reconciliation

The project page now:

- explains the first-screen product in terms of Writer/PHP structure ownership;
- presents **Simple Template Processing**, **Structured ODT Construction**, and
  **Writer-native Document Model** as the three complementary working models;
- retains the visible placeholder example for the shortest learning path;
- surfaces Writer-native structures, source-template inspection, Writer User
  Fields, mapping, Concrete Preflight, and bounded automation in the capability
  summary;
- links directly to the published documentation and Practical API Reference;
- labels the live count as canonical samples;
- describes S01b explicitly as a mixed-ownership professional showcase.

The page continues to use the canonical registry as its sample source of truth.

## Scope boundaries

F4.1 does not redesign project-support methods or their prominence. Existing
support UI is left for F4.2.

F4.1 also does not change engine runtime/API semantics, sample behavior, or the
documentation architecture.

## Validation and live-site gate

Normal CI/documentation validation should run on the integrated repository
change. Because the public website itself was not retrievable by the audit
environment, F4.1 additionally requires a post-deployment live check of:

- first-screen rendering and navigation;
- the three-model section;
- documentation/API Reference links;
- canonical sample filters/cards;
- S01b showcase link and card;
- ODT generation/download for at least one packaged canonical sample.

Subject to those checks, the F4.1 presentation reconciliation is complete.

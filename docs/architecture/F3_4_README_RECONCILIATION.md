# F3.4 — README Reconciliation

**Status:** COMPLETE — bounded FINALIZATION-01 F3.4 documentation slice

## Objective

Make the repository README the concise public entrance to the completed 1.0
documentation and learning path. The README points outward to detailed guides,
canonical samples, and the Practical API Reference instead of duplicating them.

## Evidence reviewed

The reconciliation used the current `develop` state after the F3.3 merge,
including:

- `docs/architecture/FINALIZATION_01_PLAN.md`;
- `docs/index.md`;
- the F3.2 Quick Start;
- the canonical Sample Guide;
- the completed F3.3 guide reconciliation;
- the Practical API Reference and final 1.0 terminology.

## Changes

The README now:

- uses the accepted names **Simple Template Processing**, **Structured ODT
  Construction**, and **Writer-native Document Model**;
- keeps the ownership question — Writer or PHP — as the organizing principle;
- retains a minimal Recommended `assign() → render() → save()` quick start and
  states explicitly that `save()` does not call `render()`;
- removes obsolete numbered-sample learning references;
- points to the canonical L/C/B/S Sample Guide and Sample Explorer;
- points directly to the completed Practical API Reference;
- presents Mapping, Preflight & Automation as an optional integration workflow,
  not a fourth authoring model;
- removes milestone-era Writer User Field terminology;
- removes the open-ended development-priorities section that no longer belongs
  in the 1.0 project entrance;
- links project support consistently to the existing
  `https://odt.walter-dietz.de/#support` destination without redesigning the
  support presentation reserved for F4.

## Scope boundaries

This slice changes documentation only. It does not change runtime behavior,
public API semantics, samples, the project website, or the Sample Explorer.

F4 remains responsible for auditing the actual website/Sample Explorer
presentation and for project-support visibility across public surfaces.

## F3 completion gate

With F3.1–F3.3 already complete, F3 closes when this README slice passes normal
documentation/CI validation and is integrated into `develop`.

At that point README, documentation navigation, guides, API Reference, and
canonical sample terminology tell the same 1.0 product story.

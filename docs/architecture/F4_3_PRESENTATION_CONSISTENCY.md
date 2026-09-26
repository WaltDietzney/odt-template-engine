# F4.3 — Presentation Consistency

**Status:** COMPLETE — bounded FINALIZATION-01 F4.3 consistency slice

## Objective

Reconcile the public project surfaces after F4.1/F4.2 so that the website,
README, documentation, Sample Explorer, and canonical sample registry tell the
same 1.0 product story.

## Surfaces reviewed

- public repository README;
- Zensical navigation and documentation landing page;
- Getting Started and Practical API Reference entry points;
- canonical L/C/B/S Sample Guide and sample registry;
- Sample Explorer presentation, Template Contract inspection, project links,
  documentation links, and project-support destinations;
- professional S01b CV documentation.

## Consistency result

The public surfaces now agree on the three complementary ownership models:

1. Simple Template Processing;
2. Structured ODT Construction;
3. Writer-native Document Model.

Mapping, concrete preflight, and automation remain an optional integration
workflow rather than a fourth authoring model.

The canonical sample inventory remains registry-driven and uses the L/C/B/S
taxonomy. The Sample Explorer consumes that registry and presents source-oriented
Template Contract information rather than the historical variable-only view.

Documentation destinations remain production-oriented under `/docs/`, matching
the Zensical `site_url` and deployment contract. A repository-root PHP
development server does not by itself emulate the production `site/` →
`/docs/` mapping; this is a local serving concern, not a reason to change the
public URLs.

Project support remains voluntary and secondary: navigation and the compact
discovery callout point to the full project-support section, which retains
GitHub Star, PayPal, and Bitcoin Lightning.

## Reconciliation applied

The consistency pass found two remaining stale presentation paths:

- README Sample Explorer copy still described the explorer as variable-only;
  it now describes semantic Template Contract inspection.
- the S01b CV documentation still contained historical
  `PageLayoutOdtTemplate`, `cv_sidebar` / `cv_content`, and programmatic
  page-margin descriptions. It now reflects the canonical S01b sample:
  `OdtTemplate`, Writer-owned native Sections/bookmarks/frame geometry, and
  bounded PHP-owned sidebar RichText regions.

The complex-document guide was reconciled with the same current S01b ownership
boundary and no longer presents the retired page-layout composition as current.

## Scope boundaries

No engine behavior, public API, sample semantics, support provider, or canonical
sample inventory changed in F4.3.

Historical/internal samples are not promoted back into the public learning path.

## Closeout checks

F4 presentation closeout requires:

- normal CI for this documentation-only slice;
- successful Zensical documentation build;
- link/navigation check for project page → docs, Quick Start, API Reference,
  samples, projects, and support;
- Sample Explorer visual check on desktop and a narrow viewport;
- one representative Generate & Download ODT action;
- QR assets visible in the support section.

The user-facing presentation has already received an iterative desktop visual
review during F4.1/F4.2. Subject to the normal checks above, F4 is complete.

# F4.2 — Project Support Visibility

**Status:** COMPLETE — bounded FINALIZATION-01 F4.2 presentation slice

## Objective

Make the existing voluntary project-support path readily discoverable without
turning the project page into a donation landing page.

“Support” in this block means support for continued open-source project
development, not technical help.

## Existing support surface

Before F4.2 the page already contained:

- a navigation anchor labeled `Support`;
- a substantial support section near the end of the page;
- GitHub starring;
- PayPal support with QR code;
- Bitcoin Lightning support with QR code.

The payment methods were already functionalized by the Sample Explorer
JavaScript. F4.2 therefore does not introduce or replace support methods.

The main presentation defect was discoverability: the detailed section appears
after the long Sample Explorer and real-world project content, so a visitor
could easily leave the page without seeing it.

## Applied reconciliation

F4.2 keeps the detailed support section in its natural secondary position but
adds two restrained discovery cues:

1. the navigation label now says **Support the project**, making its meaning
   unambiguous;
2. a compact callout near the top explains that the project is free,
   open-source, and independently developed and links to the existing support
   section.

The detailed support copy now emphasizes voluntary support for continued
development. GitHub Star, PayPal, and Bitcoin Lightning remain the available
methods.

The callout is intentionally a secondary visual element. The primary hero
actions remain trying the engine and following the Quick Start.

## Scope boundaries

F4.2 does not add payment providers, change donation destinations, introduce
tracking, alter engine/runtime behavior, or redesign the overall page.

Cross-surface wording and destination consistency belong to F4.3.

## Validation

After integration/deployment verify:

- the support callout is visible without dominating the product entrance;
- both support links scroll to `#support`;
- GitHub, PayPal, and Lightning actions still work;
- PayPal and Lightning QR assets render;
- the support callout remains usable on narrow/mobile layouts;
- the primary product actions remain visually dominant.

Subject to normal CI and this live presentation check, F4.2 is complete.

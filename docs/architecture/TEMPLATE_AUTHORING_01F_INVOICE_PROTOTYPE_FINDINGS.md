# TEMPLATE-AUTHORING-01F — B01 Invoice Template Builder Findings

Status: Canonical B01 Builder sample and architecture evidence for the planned S02 professional invoice showcase.

## Purpose

B01 Invoice Template Builder precedes the final LibreOffice-authored S02 invoice. It tests whether existing structured PHP authoring can create a credible professional invoice without invoice-specific engine features, and whether the generated editable ODT can serve as an authoring reference for the later Writer-owned template.

It is architecture/authoring evidence, not an invoice calculation feature and not the final S02 template.

## Proven composition model

The prototype successfully combines:

- `RichText` as structured generated content;
- semantic named `Paragraph` styles through the element-owned style pipeline;
- native ODF tab stops and real `<text:tab/>` elements for one-dimensional alignment;
- `RichTable` where a region is genuinely two-dimensional;
- visible classic template syntax for repeated invoice items;
- document-level scalar placeholders for totals.

Governing lesson:

> Choose the native ODF/Writer construct by the semantics of the document region, not merely by visual similarity.

This continues the S01b authoring rule.

## Semantic paragraph styles

Meaningful roles use semantic names rather than anonymous `para_*` styles. The prototype demonstrates roles such as `InvoiceBrand`, `InvoiceTagline`, `InvoiceContact`, `InvoiceBillToHeading`, `InvoiceBillToName`, `InvoiceBillToAddress`, `InvoiceTitle`, `InvoiceMeta`, `InvoiceSalutation`, `InvoiceBodyText`, `InvoiceItemsHeader`, `InvoiceItem`, `InvoiceItemDescription`, `InvoiceTemplateMarker`, `InvoicePaymentHeading`, `InvoicePaymentText`, `InvoiceSummary`, `InvoiceGrandTotal`, and signature/closing roles where required.

Technical helper styles used by layout infrastructure do not need renaming merely to eliminate all automatic styles. Meaningful authoring roles should remain understandable and reusable.

The preferred current application-facing pattern is the element-owned Paragraph style definition, e.g. `new Paragraph('InvoiceTitle', [...])`, rather than application-level `StyleMapper` registration used as a shortcut around the structured-element pipeline.

## Tab-stop item layout

The accepted invoice item geometry is:

- 10.2 cm, right aligned — price;
- 13.1 cm, centered — quantity;
- 17.0 cm, right aligned — line total.

Alignment uses real ODF tabs, not repeated spaces. Tests with long item names and wrapping descriptions produced a credible LibreOffice result.

This does not make tabs a universal replacement for tables. They fit this design because each item is fundamentally a one-dimensional paragraph row with stable alignment points.

## Template-authoring pattern

The static heading remains outside the repeated unit:

```text
ITEM DESCRIPTION        PRICE        QTY        TOTAL

{{#foreach:items}}
{{name}}                 {{price}}    {{quantity}}    {{total}}
{{description}}
{{#endforeach}}
```

The markers are deliberately visible but unobtrusive so the generated ODT shows both final layout intent and template-control boundaries.

Collection: `items`.

Item-scoped fields: `name`, `description`, `price`, `quantity`, `total`.

The prototype generates this template structure but deliberately does not execute the newly generated foreach in the same generation pass.

## Scope of `total`

Using `{{total}}` inside and outside the loop is intentional evidence:

```text
{{#foreach:items}}
    {{total}}       item-scoped line total
{{#endforeach}}

{{total}}           document/root-scoped invoice total
```

Final S02 should verify/preserve intended scope semantics rather than renaming fields solely to avoid visual duplication.

## No invoice calculation semantics

The engine currently has no invoice calculation feature. The showcase must not imply one.

The summary therefore uses literal binding inputs:

```text
Sub Total       {{subtotal}}
Tax Vat 18%     {{tax}}
GRAND TOTAL     {{total}}
```

No subtotal, tax, or grand-total calculation belongs in the prototype engine logic.

## Region ownership

Visual page position does not determine semantic ownership. Current intended composition:

```text
invoice_header
├── brand/logo
├── sender/contact information
├── BILL TO
├── INVOICE title
├── invoice number
└── invoice date

invoice_body
├── salutation
├── introductory text
├── static item header
├── foreach item template
├── payment information
├── subtotal / tax / grand total
└── signature / account-manager closing

invoice_footer
├── THANK YOU FOR YOUR BUSINESS
└── terms text
```

The payment/summary/signature block remains invoice business content even though it is visually low on the page. The reference design treats `THANK YOU FOR YOUR BUSINESS` plus terms as the closing footer region, so the prototype uses `invoice_footer` for that block.

Authoring lesson:

> "At the bottom of the page" and "document footer" are not equivalent concepts; ownership follows the role of the content.

## Writer User Fields for final S02

The final Writer-authored S02 should evaluate existing Writer User Fields for document-global values. Strong candidates are invoice number, invoice date, and other stable document-global invoice/recipient metadata.

The bounded 1.0 User Field capability is string-valued and document-global/ROOT-scoped. User Fields must not become foreach item-scoped merely because a reference appears in repeated structure. Line items therefore remain a collection-binding problem.

`subtotal`, `tax`, and root-level `total` should be evaluated during final S02 authoring rather than automatically converted to User Fields. Binding mechanism should follow authoring semantics.

## Conditional salutation for final S02

The prototype keeps a representative normal salutation. Final S02 must demonstrate native declarative Writer Sections for conditional salutation where existing semantics fit.

Existing declarative Section controls include `#if`, `#ifnot`, and `#foreach`. Final design must use the actually supported condition grammar and must not invent an unverified native-section `else`.

## RichText as a template-building capability

The prototype exposed an additional useful workflow:

```text
structured PHP prototype
        ↓
credible editable ODT
        ↓
LibreOffice authoring/refinement
        ↓
Writer-owned professional template
        ↓
template-driven rendering
```

This is not a new engine lifecycle and does not replace LibreOffice as visual designer. It is useful evidence for a future “Building Templates” teaching path.

## RichTable versus tabs

Use `RichTable` when a region is genuinely two-dimensional composition, such as payment information left and totals/signature right.

Use Paragraph + native tabs for fundamentally one-dimensional aligned rows such as the accepted invoice item design.

Do not use tables merely as generic geometry containers, and do not emulate alignment with spaces.

## Future capability gaps discovered by the showcase

These are post-1.0 directions unless separately approved.

### Native table-row repeat — required next development round

Support repeating a LibreOffice-authored native table row while preserving authored structure/formatting and item-scoped binding. This is important for business documents whose repeated records semantically require a real table. Exact API/marker semantics remain undecided and require ODF/compatibility investigation first.

### Semantic named elements

Investigate stable semantic identity for generated ODF objects where native semantics support it, including images, tables, and frames. This can underpin future named-object replace/clone/remove operations. `setElement('placeholder', $element)` names an insertion point; it must not be confused with naming the inserted ODF object.

### Programmatic Writer Sections / SectionElement

Current architecture can discover/manipulate/instantiate existing Writer Sections and execute declarative Section controls, but the showcase did not establish a public `addSection()` API for creating native Writer Sections from structured PHP. Investigate a semantics-driven programmatic Section authoring capability. Exact API remains undecided.

### Multi-column Sections and column breaks

The design discussion exposed a possible future need for programmatic multi-column Writer Sections and controlled column breaks. This remains research, not an approved API or current S02 dependency.

## Final S02 direction

S02 should derive from the proven prototype while shifting stable visual ownership to LibreOffice. It should deliberately compose existing mechanisms:

```text
LibreOffice / Writer
    stable layout
    semantic named paragraph styles
    native fields where appropriate
    native conditional Sections
    visual refinement

ODT Template Engine
    document-global User Field values
    scalar data binding
    item collection binding
    declarative conditional execution
    image/resource replacement where appropriate
```

S02 should demonstrate a data-driven business document, complementing S01b's structured personal-document emphasis.

## Validation principle

Valid ODT ZIP/XML, automated tests, and headless processing are necessary but not sufficient for rendering-sensitive authoring decisions. Manual LibreOffice visual inspection remains part of professional-showcase acceptance.

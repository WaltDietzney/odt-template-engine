# TEMPLATE-AUTHORING-01F — S02-A Writer Template Blueprint

Status: APPROVED IMPLEMENTATION BLUEPRINT

## Purpose

This blueprint translates the approved S02 Professional Invoice Change
Contract and the accepted B01 Invoice Template Builder findings into a concrete
LibreOffice Writer authoring plan.

It defines the intended Writer structures, styles, bindings, control
structures, and ownership boundaries before the canonical S02 ODT is authored.

This document does not add engine semantics. If the template cannot be built
within this blueprint using capabilities already established on `develop`,
S02-A must stop and return to architecture review.

## Authoring principle

The S02 ODT is the design source of truth.

Writer owns:

- page geometry and whitespace;
- stable region geometry;
- stable labels and prose;
- paragraph and character styling;
- table geometry where a table is semantically appropriate;
- native tab-stop geometry;
- native conditional Section structure;
- native User Field declarations/references where selected;
- logo geometry.

PHP later supplies data and invokes existing engine behavior. It must not
reconstruct the stable invoice layout with `RichText`, `Paragraph`,
`RichTable`, or programmatic style definitions.

B01 is the visual and semantic design reference, not the XML source for S02.

## Target artifact

The canonical Writer template is planned as:

```text
samples/templates/template_S02_professional_invoice.odt
```

S02-A creates and validates the template. The canonical rendering sample is a
later S02-B artifact.

## Page model

Use an ordinary Writer page as the primary flow container.

The invoice should remain a normal editable Writer document rather than a
canvas of independently positioned drawing objects.

Preferred page behavior:

- one professional invoice page for the representative dataset;
- normal Writer flow when content grows;
- no PHP-controlled pagination;
- no manual spaces for alignment;
- no fixed-position object merely to imitate ordinary flowing text.

A true header/footer is not required for the accepted B01 composition.
Content that merely appears near the top or bottom of the page remains normal
business-document content unless it has real master-page semantics.

## Visual vocabulary

Carry forward the accepted B01 direction:

- primary accent: restrained orange;
- primary text: near-black;
- secondary text: muted gray;
- light-gray invoice metadata surface;
- white text on orange item/grand-total emphasis;
- generous whitespace;
- compact business typography.

The exact Writer color/style values may be refined visually. The template
should preserve the hierarchy rather than reproduce B01's generated style XML
byte-for-byte.

## Structural map

The authored document should read structurally as:

```text
PAGE
│
├── Upper two-column business region
│   ├── Left
│   │   ├── Brand/logo
│   │   ├── Tagline
│   │   └── BILL TO / recipient
│   └── Right
│       ├── Sender contact block
│       ├── INVOICE title
│       └── Invoice metadata
│
├── Conditional salutation Section(s)
├── Introductory paragraph
├── Item heading
├── {{#foreach:items}}
│   ├── Item line: name / price / quantity / line_total
│   └── Item description
├── {{#endforeach}}
│
├── Lower two-column business region
│   ├── Left: payment information
│   └── Right
│       ├── subtotal / tax
│       ├── grand total
│       └── account-manager closing
│
└── Thank-you / terms closing
```

The upper and lower two-column regions are genuine two-dimensional
compositions. A borderless Writer table is therefore the preferred native
container for each of those regions.

The item list is not a table in S02 because native table-row repetition is not
part of the current contract.

## Upper business region

### Container

Author a borderless two-column Writer table.

Starting proportion from B01:

```text
left 55%
right 45%
```

The exact ratio may be visually adjusted in Writer.

Do not use the table as a page-positioning substitute beyond this genuine
two-column relationship.

### Upper-left cell

Author, in order:

1. logo;
2. brand/tagline;
3. spacing owned by paragraph styles;
4. `BILL TO` label;
5. recipient name;
6. recipient address/contact lines.

The B01 logo asset may be used as the initial visual reference. S02-A must
decide whether the final logo is:

- stable template branding; or
- an existing template-authored image position intentionally replaced by
  S02-B.

Default for S02-A: keep the logo as stable Writer-owned branding unless a
real showcase need for image replacement is identified. S02 already combines
enough engine mechanisms and should not add image replacement merely for
feature density.

### Recipient bindings

Use ordinary scalar placeholders for recipient values.

Planned dependency names:

```text
{{customer_name}}
{{customer_address}}
{{customer_city}}
{{customer_phone}}
{{customer_email}}
```

These values are document-global but do not gain useful authoring semantics
from becoming Writer User Fields. Keeping them as visible scalar placeholders
also makes the template easy to understand.

If authoring shows that address composition is clearer with a different
bounded scalar split, record the change before S02-B rather than silently
changing the data contract.

### Upper-right cell

Author, in order:

1. sender location line;
2. sender telephone line;
3. sender email line;
4. sender web line;
5. controlled spacing;
6. large right-aligned `INVOICE` title;
7. invoice metadata block.

Sender contact values should be ordinary scalar placeholders unless they are
deliberately made stable template branding.

Planned dynamic dependencies:

```text
{{sender_address}}
{{sender_phone}}
{{sender_email}}
{{sender_web}}
```

The small contact labels such as `LOC`, `TEL`, `MAIL`, and `WEB` are
stable Writer text.

## Invoice metadata block

Author a visually distinct Writer paragraph/block with:

- light-gray background;
- orange left accent/border;
- compact padding/spacing;
- label/value alignment using a native tab stop.

Stable labels:

```text
Invoice No.
Date
```

### Writer User Fields

S02-A should author two Writer string User Fields:

```text
invoice_number
invoice_date
```

They are selected because both are genuinely document-global values and
native field references are useful wherever the same identity/date may need
to be displayed consistently.

Requirements:

- declarations are native Writer User Field declarations;
- visible values are native User Field references;
- field names are stable and simple;
- no item data uses User Fields;
- cached display text is not treated as the authoritative stored value.

If LibreOffice authoring or current engine inspection reveals that these
fields cannot be represented without violating established L10 semantics,
stop S02-A and report the conflict.

## Salutation

Use native Writer Sections and the established declarative conditional
grammar.

The template should demonstrate two mutually exclusive salutation variants
without inventing `#else`.

Planned input:

```text
gender = "male" | "female"
customer_last_name
```

Author two sibling conditional Sections:

```text
#if:gender=="male"
    Dear Mr {{customer_last_name}},

#if:gender=="female"
    Dear Ms {{customer_last_name}},
```

The exact Section naming syntax must follow the already established Phase-D
grammar as implemented on `develop`; do not approximate it from this
blueprint if Writer/inspection evidence differs.

Both salutation paragraphs use the same semantic Writer paragraph style.

S02 does not need a fallback/neutral branch unless it can be expressed with
the existing grammar without making the showcase less clear. The public
sample data must select one established branch.

## Introductory paragraph

Keep the prose itself Writer-owned:

```text
Thank you for choosing Northstar Studio. Please find below the services
provided for the current project.
```

This is stable showcase prose and does not need to be generated by PHP.

If the company/brand wording is made dynamic during authoring, use a simple
scalar dependency rather than generated RichText.

## Item heading

Author one paragraph outside the repetition block:

```text
ITEM DESCRIPTION    PRICE    QTY    TOTAL
```

Use:

- orange background;
- white compact bold text;
- native Writer tab stops;
- no literal alignment spaces.

The heading and item line should share compatible tab geometry.

Starting geometry from B01:

```text
10.2 cm  right   price
13.1 cm  center  quantity
17.0 cm  right   total
```

Writer may adjust these values during visual authoring.

## Item repetition

Use classic visible template syntax, each marker in its own paragraph:

```text
{{#foreach:items}}
{{name}}    {{price}}    {{quantity}}    {{line_total}}
{{description}}
{{#endforeach}}
```

The apparent spacing above represents native tab characters in the item line,
not spaces.

Required structure between the markers:

1. one primary item paragraph;
2. one description paragraph.

Do not place the item block inside a Writer table row.

Do not convert this block to native Writer `#foreach` Sections.

### Item paragraph

Dependencies:

```text
{{name}}
{{price}}
{{quantity}}
{{line_total}}
```

Use native tabs between the four logical columns.

The paragraph style owns the tab stops. The template must contain real
`text:tab` semantics after LibreOffice saves the document.

### Item description

Dependency:

```text
{{description}}
```

Use a subordinate paragraph style with smaller/muted text and modest left
indentation.

### Marker presentation

The foreach marker paragraphs are authoring controls, not business content.

Use a small unobtrusive semantic paragraph style so they remain understandable
while editing the template. Their visual appearance in the source template
must not rely on hiding or malformed text.

## Lower business region

### Container

Author a second borderless two-column Writer table.

Starting proportion:

```text
left 55%
right 45%
```

This is semantically appropriate because payment information and financial
summary/signature form a genuine side-by-side business region.

### Lower-left cell — payment information

Stable Writer labels:

```text
PAYMENT METHOD
Bank Account
PayPal
```

Planned scalar dependencies:

```text
{{bank_name}}
{{bank_code}}
{{payment_email}}
```

If the final visual template uses different representative payment labels,
record the final dependency names before S02-B.

No banking/accounting validation is implied.

### Lower-right cell — financial summary

Author subtotal and tax as compact label/value lines using a native Writer tab
stop.

Dependencies:

```text
{{subtotal}}
{{tax}}
```

Author grand total as a separate orange emphasis paragraph:

```text
GRAND TOTAL    {{total}}
```

The `{{total}}` here is ROOT-scoped. The original blueprint considered using
the identical `{{total}}` inside `items` as item-scoped, but current classic
rendering characterizes that arrangement as a collision: global scalar
replacement occurs before foreach row cloning, so item values cannot shadow an
already assigned ROOT value.

The compatibility-safe authored form is therefore:

```text
items[].line_total   line/item total
ROOT.total           document-level grand total
```

The S02 implementation slice must use `{{line_total}}` for the repeated item
line. No value is calculated by the engine.

The tax label is stable representative Writer text. If the displayed rate is
`Tax VAT 18%`, it must not imply that S02 derives `{{tax}}` from 18%.

### Account-manager closing

Keep the closing in the lower-right cell beneath the financial summary.

Planned dynamic dependencies:

```text
{{account_manager_name}}
{{account_manager_role}}
```

Any signature ornament/line is Writer-owned.

## Thank-you and terms closing

Below the lower table, author normal Writer paragraphs:

```text
THANK YOU FOR YOUR BUSINESS
Terms: Payment is due within 14 days. Thank you for your continued partnership.
```

These are stable showcase strings.

They are not placed in a Writer page footer merely because they appear near
the bottom of the representative page.

## Planned semantic Writer styles

Prefer a compact semantic style vocabulary derived from B01.

Candidate paragraph styles:

```text
InvoiceBrand
InvoiceTagline
InvoiceContact
InvoiceBillToHeading
InvoiceBillToName
InvoiceBillToAddress
InvoiceTitle
InvoiceMeta
InvoiceSalutation
InvoiceBodyText
InvoiceItemsHeader
InvoiceTemplateMarker
InvoiceItem
InvoiceItemDescription
InvoicePaymentHeading
InvoicePaymentText
InvoiceSummary
InvoiceGrandTotal
InvoiceSignature
InvoiceSignatureRole
InvoiceFooter
InvoiceFooterTerms
```

These names are authoring guidance, not a new public engine API.

Avoid proliferating anonymous `para_*` styles for meaningful authored
regions. LibreOffice-generated automatic styles are normal ODF implementation
detail and are not prohibited.

Where two regions genuinely share the same semantics and formatting, reuse a
style rather than preserving B01's names mechanically.

## Binding inventory

Planned ROOT scalar dependencies:

```text
customer_name
customer_last_name
customer_address
customer_city
customer_phone
customer_email

sender_address
sender_phone
sender_email
sender_web

gender

bank_name
bank_code
payment_email

subtotal
tax
total

account_manager_name
account_manager_role
```

Planned Writer User Fields:

```text
invoice_number
invoice_date
```

Planned collection:

```text
items[]
    name
    description
    price
    quantity
    line_total
```

No value in this inventory is calculated by the engine.

## Mapping decision for S02-A

S02-A does not author around the optional C05 mapping layer.

The template dependency names above are intentionally readable and suitable
for direct sample data. This keeps the professional showcase focused on
Writer/template composition.

S02-B may still use the established mapping layer if a real application-shaped
input model is introduced and the mapping solves an actual boundary. It must
not be added merely to exercise C05.

## Metadata decision for S02-A

Metadata does not affect Writer layout and therefore is not encoded as visible
template structure.

S02-B should add a small meaningful metadata set through the existing metadata
API so S02 absorbs the relevant legacy Sample 04/10 business-document lesson.

This does not change the visible dependency inventory.

## Image decision for S02-A

Default decision: the Northstar logo is stable Writer-owned branding.

Reasons:

- it matches the S02 ownership principle;
- B01 already demonstrates programmatic image construction;
- S02 does not need image replacement to prove professional composition;
- avoiding unnecessary replacement keeps image identity out of S02's critical
  path.

If the final authoring exercise shows a real need for replaceable branding,
stop and document that decision before changing the template contract.

## Native structure inspection checklist

After saving the first Writer-authored template, inspect the actual ODT and
confirm:

- the upper region is a native Writer table;
- the lower region is a native Writer table;
- borders are absent where intended;
- semantic paragraph styles survive in the package;
- item heading/item paragraphs contain native tab-stop definitions;
- item content contains real tab elements rather than alignment spaces;
- foreach marker paragraphs remain intact and unsplit;
- User Field declarations for `invoice_number` and `invoice_date` exist;
- their visible references are native User Field references;
- salutation Sections are native Writer Sections;
- Section names/conditions match established declarative grammar;
- no native `#foreach` Section is used for invoice items;
- no accidental drawing/text-box structure owns ordinary flowing text;
- no new engine-specific XML convention has been introduced.

Inspection is evidence, not visual approval.

## LibreOffice authoring checklist

During S02-A:

1. create the ODT in LibreOffice Writer;
2. establish page margins and base typography;
3. create semantic Writer paragraph styles;
4. build upper two-column table and populate stable/dynamic content;
5. insert the Writer-owned logo;
6. author User Field declarations/references;
7. author conditional salutation Sections;
8. author introduction;
9. author item heading and native tab stops;
10. author classic foreach markers and item paragraphs;
11. build lower two-column table;
12. author totals/signature region;
13. author thank-you/terms closing;
14. save/reopen in LibreOffice;
15. inspect native ODT structure;
16. perform visual review of the template itself.

Do not add PHP rendering code during S02-A merely to compensate for an
authoring problem.

## S02-A acceptance criteria

S02-A reaches GO when:

1. `template_S02_professional_invoice.odt` exists as an editable
   LibreOffice-authored document;
2. its visual hierarchy is recognizably derived from the accepted B01 design;
3. upper/lower two-dimensional regions are native borderless Writer tables;
4. stable layout/styles/text are Writer-owned;
5. invoice number/date are native string User Fields;
6. salutation variants are native conditional Writer Sections using only
   established grammar;
7. item repetition is visibly authored with classic
   `{{#foreach:items}}` / `{{#endforeach}}`;
8. item alignment uses native tabs/tab stops;
9. ROOT and item dependency scopes remain distinguishable as specified;
10. no invoice calculations exist;
11. no new engine capability/API is required;
12. ODT ZIP/XML structure is valid;
13. the document survives LibreOffice save/reopen;
14. manual visual inspection is accepted.

## Stop conditions

Stop S02-A and return to architecture review if:

- LibreOffice cannot author the required conditional Section structure using
  established Phase-D semantics;
- Writer User Fields cannot represent the two selected global values within
  established L10 semantics;
- classic foreach markers cannot coexist safely with the desired authored
  item paragraphs/tab geometry;
- the desired invoice design requires repeated native table rows;
- the design requires a new named-object identity mechanism;
- the design requires PHP-generated stable layout;
- Writer save/reopen destroys required template controls or native geometry;
- achieving the accepted design would require changing existing engine
  semantics.

## Handoff to S02-B

S02-B begins only after the Writer template passes this blueprint's structure
and visual review.

At handoff, record:

- final template path;
- final semantic style names;
- final scalar dependency inventory;
- final User Field names;
- final Section names/conditions;
- final item tab-stop geometry;
- whether logo branding remained static;
- any intentional differences from B01;
- any LibreOffice normalization discovered during authoring.

S02-B then supplies representative data through established APIs. It must
adapt to the approved Writer template rather than moving stable design back
into PHP.

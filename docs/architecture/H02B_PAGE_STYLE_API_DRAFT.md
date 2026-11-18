# H02-B — Page-style API extension (discussion draft)

Status: proposal, not approved Change Contract. Baseline bd47e2d. H02-A core evidence in H02A_PAGE_STYLE_PROPERTY_ANALYSIS.md. No production changes.

## Ownership and compatibility

Retain PageLayoutOdtTemplate facade, PageLayoutManager (geometry) and PageStyleManager (master identity/content). No new public class hierarchy. Keep existing flat geometry keys and method signatures compatible; nested header/footer geometry groups are distinct from OdtElement header/footer content in definePageStyle. Do not silently change legacy unknown-key handling or shared layout mutation. DocumentStyles integration is a separate design choice, not prerequisite.

## Proposed option vocabulary

Page: existing seven keys plus background-color, border and side variants, padding and side variants, columns. Header/footer geometry: height OR min-height, spacing (header bottom/footer top), margin-left/right, dynamic-spacing boolean, background-color, border/padding and side variants, shadow and double-border widths subject to contract. Use existing H01 column vocabulary where page semantics match; no section balancing option on page. Existing StyleMapper vocabulary is useful but does not prove generic mapper reuse is safe.

## Decisions before implementation

1. Fixed/minimum height mutual exclusion, transition removes obsolete attribute, omitted keys preserve authored values. Define clear/reset behavior explicitly.
2. Layout identity vs master identity: existing setPageLayout mutates shared layout. New behavior must not silently clone or change other masters; decide explicit named-layout update and master-specific isolation contract.
3. Solid/transparent fill: synchronize native page-layout and drawing-page representations, avoid unintended mutation of shared drawing-page styles. Header/footer fill is local to header-footer-properties. Determine required native representations through focused Writer verification.
4. Gradient evidence retained; decide whether named gradient creation/reference belongs in C or follow-up. Bitmap fill deferred. No arbitrary XML passthrough.
5. Content update/activation: define semantics for replace/remove header/footer on existing master separately from geometry. Preserve resources and style ownership, atomic failure. Existing definePageStyle remains compatible.
6. Inspection: determine minimum read API needed by future builder; no generic object model rewrite.
7. Columns: reuse RelativeColumnWidthNormalizer, preserve authored representations, define page-specific option validation and native child ordering. Do not change RichTable normalization or H01 behavior.

## Proposed C sequence (not authorized yet)

C1 header/footer geometry + page/region solid fill, borders, padding, shadow/double borders if approved; C2 page columns and separator; C3 bounded existing-master content mutation/inspection if approved. Gradient scope explicitly decided before C. Characterization and validation/rollback tests, save/reopen, repeated save, Writer roundtrip and visual proof. D constructs complex reference document using public API only; concrete gaps drive further changes.

## Owner decisions and fill-resource direction (2026-11-18)

Owner approved nested header/footer geometry groups. Gradients are required for professional templates; owner also requests investigating bitmap backgrounds in H02 rather than assuming deferral. Named fills should be reusable across page, frame and future shape consumers. This is a scope/design direction, not yet approval of concrete signatures.

Verified existing CircularImageElement emits a native ellipse custom-shape with graphic properties draw:fill=bitmap, draw:fill-image-name, width/height 100%, style:repeat=stretch. It declares typed FillImageRequirement before rendering. Existing FillImageRequirementRegistry, Collector and Materializer manage named declarations in styles.xml; OdtPackage owns physical image assets. Reuse these boundaries rather than inventing parallel page-specific bitmap storage. Gradient declarations do not yet have equivalent typed infrastructure. Reference fixture uses draw:gradient in office:styles, plus loext gradient stops; preserve authored extensions. Need bounded standard two-stop gradient contract and Writer evidence before claiming general gradient support. Native background-image child is a separate possible representation from named draw:fill-image; inspect Writer-authored bitmap page/header/footer evidence before choosing mapping. Shared physical package resource does not imply all consumer property mappings are identical. No migration of CircularImageElement or generic fill rewrite implied.

## Bitmap evidence and bounded scope proposal

Supplemental fixture confirms named bitmap declaration plus direct style:background-image child for page/header/footer, and duplicated page drawing-page properties. Reuse existing FillImageRequirement and package resource path. Proposed first bitmap modes repeat and stretch only; no exhaustive tile offsets, scaling or positioning UI. Include gradient and bitmap in H02 contract, subject to explicit API decisions. Test one named image referenced by multiple consumers, manifest and package identity, fill transitions (none/solid/gradient/bitmap), no stale incompatible attributes or background-image child, authored resource collision and rollback, repeated save and Writer roundtrip. Do not change legacy materializer's authored-name precedence implicitly. Public fixture rights gate applies to bitmap original. No additional owner fixture requested.

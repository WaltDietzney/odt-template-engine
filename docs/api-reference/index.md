# API Reference

This reference documents the supported end-programmer API of ODT Template Engine 1.0.

It is organized by **what you want to do**, not by the internal PHP source tree. Each entry explains the intended use before the exact signature, parameters, options, lifecycle behavior, and limitations.

## API classifications

**Recommended** is the preferred supported path for new application code.

**Advanced** is supported public API for specialized lifecycle, Writer/ODF, diagnostics, orchestration, or extension scenarios.

**Compatibility** is retained for existing applications and historical workflows. Prefer the stated Recommended replacement for new code.

Deprecated APIs are shown only where migration information is useful. Internal implementation services are intentionally absent even when PHP visibility is public.

## Choosing an ownership model

The most important API choice is often who owns a piece of document structure:

- **Simple template processing** — Writer supplies visible placeholders and control markers; PHP supplies scalar/repeating data.
- **PHP-owned structured content** — PHP constructs paragraphs, lists, tables, images, or other ODT elements and inserts them into the template.
- **Writer-native objects** — Writer owns named Sections, Bookmarks, tables, frames, or fields; PHP addresses and mutates that existing structure.

These approaches can be combined in one document. Ownership is decided per piece of structure, not once for the entire document.

## CV construction milestones (H00–H03)

- [H00: CV Integration Baseline](h00-baseline.md) — planning, no new API
- [H01: Native Section Authoring](structured-content/section-authoring.md)
- [H02: Multi-page Layouts and Sidebars](template-document/sidebar-composition.md)
- [H03: Native Drawing Shapes](structured-content/native-shapes.md) and [Skill Bars](structured-content/skillbars.md)

## Current reference sections

### Template & Document

- [Lifecycle](template-document/lifecycle.md)
- [Values & Repeating Data](template-document/values-and-repeating.md)
- [Metadata](template-document/metadata.md)
- [Images](template-document/images.md)
- [Styles & Document Defaults](template-document/styles-and-defaults.md)
- [Programmatic Page Styles](template-document/page-styles.md)
- [Multi-page Layouts and Sidebars](template-document/sidebar-composition.md)

### Structured Content

- [Overview & setElement](structured-content/index.md)
- [RichText](structured-content/richtext.md)
- [Paragraphs & Inline Content](structured-content/paragraph.md)
- [Lists](structured-content/lists.md)
- [Tables & Cells](structured-content/tables.md)
- [Images](structured-content/images.md)
- [Frames & Text Boxes](structured-content/frames-text-boxes.md)
- [Native Section Authoring](structured-content/section-authoring.md)
- [Native Drawing Shapes](structured-content/native-shapes.md)
- [Skill Bars](structured-content/skillbars.md)

### Writer-native Objects

- [Overview](writer-native/index.md)
- [Inspection](writer-native/inspection.md)
- [Bookmarks](writer-native/bookmarks.md)
- [Sections](writer-native/sections.md)
- [Tables](writer-native/tables.md)
- [Frames](writer-native/frames.md)
- [User Fields](writer-native/user-fields.md)

### Mapping & Automation

- [Overview](mapping-automation/index.md)
- [Template Contract](mapping-automation/template-contract.md)
- [Mapping](mapping-automation/mapping.md)
- [Concrete Preflight](mapping-automation/preflight.md)
- [Automation](mapping-automation/automation.md)

### Advanced & Compatibility

- [Overview](advanced-compatibility/index.md)
- [Advanced API](advanced-compatibility/advanced.md)
- [Compatibility & Deprecated API](advanced-compatibility/compatibility.md)

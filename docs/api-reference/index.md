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

## Current reference sections

### Template & Document

- [Lifecycle](template-document/lifecycle.md)
- [Values & Repeating Data](template-document/values-and-repeating.md)
- [Metadata](template-document/metadata.md)
- [Images](template-document/images.md)
- [Styles & Document Defaults](template-document/styles-and-defaults.md)

### Structured Content

The structured-content family is documented in the next F2 slice. The core entry point, `setElement()`, is already introduced from the lifecycle reference because it participates in the normal document workflow.

### Writer-native Objects

The Writer-native target reference follows in F2.4.

### Mapping & Automation

The mapping, preflight, and automation reference follows in F2.5.

### Advanced & Compatibility

Dedicated compatibility and advanced reference consolidation follows in F2.6. Compatibility behavior that is essential to understand a Recommended core method is already called out next to that method.

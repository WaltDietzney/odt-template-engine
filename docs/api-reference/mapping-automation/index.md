# Mapping & Automation

Mapping & Automation is an optional template-driven workflow built on the semantic `TemplateContract`.

It connects application data to what a Writer-authored template declares, validates the concrete invocation before mutation, and can then execute the supported bounded operations.

```text
Writer Template
      ↓
Template Contract
      ↓
Mapping
      ↓
Concrete Preflight
      ↓
Automation
      ↓
Working Document
```

This workflow does not replace the normal imperative APIs and is not a mandatory `OdtTemplate` lifecycle.

## Reference

- [Template Contract](template-contract.md) — source-derived semantic contract, dependencies, native-object evidence, coverage, capabilities, diagnostics, and provenance.
- [Mapping](mapping.md) — application-data paths, explicit mappings, scoped same-name resolution, and inspectable resolution.
- [Concrete Preflight](preflight.md) — complete non-mutating validation of one concrete invocation before Automation.
- Automation — bounded mutation, ownership, atomicity, and lifecycle. F2.5.4.

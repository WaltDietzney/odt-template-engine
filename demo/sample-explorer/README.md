# Sample Explorer

This local demo consumes `samples/sample-registry.php` to display numbered
sample metadata, declared templates, and migration status. It can execute
registered, packaged ODT samples and download their declared output path.

## Endpoints

- `index.php` — sample browser and source viewer
- `generate.php` — allow-lists a semantic registry ID and executes only a registered packaged ODT sample
- `download.php` — only serves `.odt` files located directly in `samples/output/`

The previous root-level demo endpoints were intentionally removed so that the repository root represents the Composer library rather than a web application.

This demo is intended for local development or otherwise controlled environments.

Repository-only migration entries are displayed for context but cannot be
generated through the packaged-sample endpoint. In particular, Sample 29 still
depends on a template under `tests/fixtures/`; it is not a self-contained
Composer-distributed example yet.

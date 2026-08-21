# Sample Explorer

This local demo browses the scripts under `samples/`, displays template variables, and can execute a selected sample to generate its ODT output.

## Endpoints

- `index.php` — sample browser and source viewer
- `generate.php` — validates a `sample_*` name and executes the corresponding sample script
- `download.php` — only serves `.odt` files located directly in `samples/output/`

The previous root-level demo endpoints were intentionally removed so that the repository root represents the Composer library rather than a web application.

This demo is intended for local development or otherwise controlled environments.

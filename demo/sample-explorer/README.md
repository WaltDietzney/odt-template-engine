# Sample Explorer

This local demo consumes `samples/sample-registry.php` to display canonical
sample metadata and declared templates. It can execute
registered, packaged ODT samples and download their declared output path.

## Endpoints

- `index.php` — sample browser and source viewer
- `generate.php` — allow-lists a semantic registry ID and executes only a registered packaged ODT sample
- `download.php` — only serves `.odt` files located directly in `samples/output/`

The previous root-level demo endpoints were intentionally removed so that the repository root represents the Composer library rather than a web application.

This demo is intended for local development or otherwise controlled environments.

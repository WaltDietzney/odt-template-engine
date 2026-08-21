# Demo applications

The `demo/` directory contains optional development/demo applications. It is not part of the core Composer library API.

## Sample Explorer

`sample-explorer/` provides a small browser-based interface for inspecting and generating the examples from `samples/`.

Run Composer first from the project root:

```bash
composer install
```

Then serve the repository root with a PHP-capable web server and open:

```text
/demo/sample-explorer/
```

The download endpoint only serves `.odt` files from `samples/output/`, and the generation endpoint only accepts sample names matching the repository's `sample_*.php` convention.

## Security note

The demo is intended for local development and controlled test environments. Do not expose development demos directly to the public internet without adding the normal production controls such as authentication, rate limiting, logging, and web-server hardening.

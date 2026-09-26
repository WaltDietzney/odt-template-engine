# Security Policy

## Supported Versions

The current stable 1.x release line is supported for security fixes.

Security fixes for released versions are applied to the stable `master` line. Unreleased development continues on `develop` and is not a separate supported release line.

## Reporting a Vulnerability

Please report suspected vulnerabilities privately through GitHub's security reporting features when available. Do not open a public issue containing exploit details or sensitive information before the report has been reviewed.

Include a clear description, affected component, reproduction steps, and the potential impact where possible.

## Demo Code

Files under `demo/` are intended for local development and controlled test environments. They are not production-ready web applications and should not be exposed publicly without appropriate authentication, rate limiting, logging, and web-server hardening.

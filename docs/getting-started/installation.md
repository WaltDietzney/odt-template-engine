# Installation

Install the ODT Template Engine with Composer:

```bash
composer require waltdietzney/odt-template-engine
```

## Requirements

The package requires:

- PHP 8.2 or newer
- DOM extension (`ext-dom`)
- ZIP extension (`ext-zip`)

Composer resolves the library and its dependencies through Packagist.

## Next: generate your first document

Installation alone does not require LibreOffice at runtime. To follow the
first-document workflow, create a normal `.odt` template in LibreOffice and
continue with the [Quick Start](quick-start.md).

The Quick Start deliberately uses only the Recommended 1.0 API and produces a
normal editable ODT file. You do not need to inspect or edit ODF XML to get
started.

## Development checkout

If you are contributing to the engine itself, clone the repository and install its development dependencies:

```bash
git clone https://github.com/WaltDietzney/odt-template-engine.git
cd odt-template-engine
composer install
composer test
```

The repository test suite validates the PHP library itself.

## Preview the developer documentation

The developer documentation is built with [Zensical](https://zensical.org/). Install it in a separate Python virtual environment so that documentation dependencies remain isolated from the PHP project.

On Debian or Ubuntu, install Python virtual-environment support if necessary:

```bash
sudo apt install python3-venv python3-pip
```

Then, from the repository root:

```bash
python3 -m venv .venv-docs
source .venv-docs/bin/activate
pip install zensical
zensical serve
```

The local preview is available at `http://localhost:8000/` by default.

To verify that the static documentation builds without warnings, run:

```bash
zensical build --strict
```

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

## Verify the installation

Create a small PHP file in your project:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use OdtTemplateEngine\OdtTemplate;

$template = new OdtTemplate('template.odt');
```

If the class can be instantiated and the template path is valid, the package is available through Composer autoloading.

Continue with the [Quick Start](quick-start.md) to generate a document.

## Development checkout

If you are contributing to the engine itself, clone the repository and install its development dependencies:

```bash
git clone https://github.com/WaltDietzney/odt-template-engine.git
cd odt-template-engine
composer install
composer test
```

The repository test suite is separate from the documentation toolchain and validates the PHP library itself.

<?php

declare(strict_types=1);

/**
 * Sample 29: Native Writer User Field Binding
 *
 * Uses a real LibreOffice-authored fixture with one logical string User Field
 * referenced from body and header content. Phase C updates the authoritative
 * declarations and deliberately leaves cached user-field-get display text to
 * Writer reevaluation.
 */

use OdtTemplateEngine\OdtTemplate;

require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

$templatePath = dirname(__DIR__)
    . '/tests/fixtures/libreoffice-reference/odt/'
    . 'TEMPLATE-AUTHORING-01C-user-field-header.odt';

$outputPath = __DIR__ . '/output/output_29_userFieldBinding.odt';

$template = new OdtTemplate($templatePath);

$contract = $template->inspectTemplate();
echo "Before binding\n";
echo "--------------\n";
echo 'native_field_binding: '
    . $contract->capabilities()->readiness('native_field_binding')
    . "\n";

foreach ($contract->dependencies() as $dependency) {
    if ($dependency->name() === 'customer') {
        echo 'dependency: ' . $dependency->path() . "\n";
    }
}

$template->setUserField('customer', 'Maria');
$template->save($outputPath);

echo "\nSaved: {$outputPath}\n";
echo "Open the result in LibreOffice Writer to observe native field reevaluation.\n";

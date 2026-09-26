<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use OdtTemplateEngine\OdtTemplate;

$template = new OdtTemplate(__DIR__ . '/templates/template_L10_writer_user_fields.odt');

// The template owns one document-global Writer string User Field.
$template->setUserField('customer', 'Aurora Studio');
$template->save(__DIR__ . '/output/output_L10_writer_user_fields.odt');

echo "Saved Writer User Field example. Open it in LibreOffice Writer to reevaluate the native field.\n";

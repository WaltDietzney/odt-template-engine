<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
$warnings = [];
set_error_handler(static function (int $severity, string $message) use (&$warnings): bool {
    $warnings[] = $message;
    return true;
});
$t = new OdtTemplateEngine\OdtTemplate(__DIR__ . '/contact-hidden.odt');
$t->setUserField('phone', '+49 5221 987654');
$t->save(__DIR__ . '/contact-hidden-present.odt');
$t->cleanup();
$t = new OdtTemplateEngine\OdtTemplate(__DIR__ . '/contact-design-options.odt');
$t->setUserField('name', 'CROSSPART UPDATED');
$t->save(__DIR__ . '/contact-crosspart-bound.odt');
$t->cleanup();
$t = new OdtTemplateEngine\OdtTemplate(__DIR__ . '/contact-resources.odt');
$t->appendBody(new OdtTemplateEngine\Elements\ImageElement(
    __DIR__ . '/assets/lucide-mail.svg',
    ['width' => '0.7cm', 'height' => '0.7cm', 'text:anchor-type' => 'as-char']
));
$t->save(__DIR__ . '/contact-engine-svg.odt');
$t->save(__DIR__ . '/contact-engine-svg-repeat.odt');
$t->cleanup();
file_put_contents(__DIR__ . '/extra-probes.json', json_encode(['warnings' => $warnings], JSON_PRETTY_PRINT) . "\n");
$names = json_decode(file_get_contents(__DIR__ . '/outputs.json'), true);
$names = array_values(array_unique(array_merge($names, [
    'contact-hidden-present', 'contact-crosspart-bound', 'contact-engine-svg', 'contact-engine-svg-repeat',
])));
file_put_contents(__DIR__ . '/outputs.json', json_encode($names, JSON_PRETTY_PRINT) . "\n");

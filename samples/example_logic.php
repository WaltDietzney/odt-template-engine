<?php

require_once '../vendor/autoload.php';

use OdtTemplateEngine\OdtTemplate;

// Template laden
$template = new OdtTemplate('templates/template_logic.odt');
$template->load();

// Platzhalter mit Filtern
$template->setValues([
    'preis' => 120,
    'is_admin' => true,
    'is_guest' => false
]);

$template->setMeta([
    'title' => 'Mein Dokument',
    'author' => 'Max Mustermann',
    'description' => 'Erstellt mit OdtTemplateEngine',
    'date' => date('c'), // ISO 8601 Format
]);

$template->setMeta([
    'title' => 'Bericht Q2',
    'author' => 'Anna Beispiel',
    'subject' => 'Finanzbericht',
    'keywords' => 'finanzen,bericht,2024',
    'language' => 'de',
    'generator' => 'OdtTemplateEngine v1.0',
    'editing_cycles' => 4,
    'editing_duration' => 'PT15M',
]);

print_r($template->getMeta());

$template->save(__DIR__ . '/output/output_logic.odt');

echo "✅ Dokument erfolgreich erstellt: output_logic.odt\n";

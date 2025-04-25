<?php

require_once '../vendor/autoload.php';

use OdtTemplateEngine\OdtTemplate;

// Template laden
$template = new OdtTemplate('templates/template_filters.odt');
$template->load();

// Platzhalter mit Filtern
$template->setValues([
    'name' => 'Anna Beispiel',
    'email' => 'ANNA@EXAMPLE.COM',
    'kommentar' => "Dies ist Zeile 1\nUnd das ist Zeile 2.",
    'geburtstag' => '1995-08-15',
    'umsatz' => '1345.5',
]);




$template->save(__DIR__ . '/output/output_fiters.odt');

echo "✅ Dokument erfolgreich erstellt: output.odt\n";

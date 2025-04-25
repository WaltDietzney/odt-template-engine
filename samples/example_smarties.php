<?php

require_once '../vendor/autoload.php';

use OdtTemplateEngine\OdtTemplate;

$template = new OdtTemplate('templates/template_smarties.odt');
$template->load();

$template->setValues([
    'name' => 'Anna',
    'vname' => 'Walter',
    'umbruch' => 'Das ist Zeile1 \n und das ist Zeile2'
]);
$template->setValues([
    'kommentar' => "    Das ist ein Kommentar ",
    'trimtag' => ' das ist ein Trimtag    '
]);





$template->save('output/output_smarties.odt');

echo "Dokument erfolgreich erstellt: output_smarties.odt\n";

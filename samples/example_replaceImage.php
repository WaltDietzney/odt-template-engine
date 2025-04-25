<?php

require_once '../vendor/autoload.php';

use OdtTemplateEngine\OdtTemplate;

$template = new OdtTemplate('templates/template_replaceImage.odt');
$template->load();

$template->replaceImageByName('Logo', '../assets/Logo-2.png', [
    'width' => '6cm'
]);

$template->save('output/output_replaceImage.odt');

echo "Die Datei 'output_replaceImage.odt' wurde erfolgreich erstellt.\n";

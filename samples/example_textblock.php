<?php

require_once '../vendor/autoload.php';

use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Elements\RichText;

// Template laden
$template = new OdtTemplate('templates/template_textblock.odt');
$template->load();

$text = (new RichText())
    ->addText("Dies ist ", ['bold' => true])
    ->addText("farbig", ['color' => '#ff0000', 'italic' => true])
    ->addText(" und normal.");

$template->setElement('textblock', $text);



$template->save(__DIR__ . '/output/output_textblock.odt');

echo "✅ Dokument erfolgreich erstellt: output_textblock.odt\n";

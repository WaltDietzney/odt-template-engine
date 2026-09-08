<?php

use OdtTemplateEngine\Elements\OdtElement;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\OdtTemplate;

// Beispielhafte Instanz deines Templates
$template = new OdtTemplate('templates/template_richtextblock.odt');
$template->load();

// RichText Container
$rich = new RichText();

// 1. Absatz ohne Style
$rich->addParagraph("🔹 Absatz ohne Style");

// 2. Absatz mit benanntem Style (nicht registriert)
$rich->addParagraph("🔸 Absatz mit Style-Name, aber ohne Optionen", "NoStyleDefinition");

// 3. Absatz mit Style + Optionen
$rich->addParagraph("✅ Absatz mit definiertem Style", "CustomHeading", [
    'margin-top' => '1cm',
    'margin-bottom' => '0.5cm',
    'text-align' => 'center',
    'background-color' => '#d1e0ff',
    'text-indent' => '1cm'
]);

// 4. Formatierter Text innerhalb eines Absatzes
$rich->addText("Fetter Text", ['bold' => true]);
$rich->addText(", kursiv", ['italic' => true]);
$rich->addText(", und farbig", ['color' => '#ff0000']);

// 5. Absatz mit Paragraph- und Inline-Styles
$rich->addParagraph("🎯 Absatz mit Absatz- und Textstil", "StyledPara", [
    'text-align' => 'right',
    'margin-left' => '2cm',
]);
$rich->addText("Rechter Text", ['bold' => true, 'color' => '#008000']);

// ⬇️ RichText ins Dokument einfügen
$template->setElement('TEXTBLOCK', $rich);

// 💾 Exportieren
$template->save('output/output-richtextblock.odt');

echo "✅ Testdokument 'output-richtextblock.odt' erfolgreich erzeugt.\n";

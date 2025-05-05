<?php
require '../vendor/autoload.php';

use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\Utils\StyleWriter;

// 1) Lade das Template
$tpl = new OdtTemplate('templates/test_fonts.odt');

// 2) Erstelle zwei Absätze mit je eigenem Inline-Style
$para1 = (new Paragraph())
    ->addText('This is Arial text.', [
        'font-family' => 'Arial',
        'font-size'   => '12pt',
    ]);

$para2 = (new Paragraph())
    ->addLineBreak()
    ->addText('This is Ubuntu text.', [
        'font-family' => 'Ubuntu',
        'font-size'   => '12pt',
    ]);

// 3) Kombiniere die beiden in ein RichText-Objekt
$rich = (new RichText())
    ->addParagraph($para1)
    ->addParagraphBreak(1)
    ->addParagraph($para2);

// 4) Setze das Element (Platzhalter {{content}})
$tpl->setElement('content', $rich);

// 5) Speichere – dabei werden Styles & Fonts automatisch geschrieben
//    Wichtig: Hier in save() sind writeAllStyles für styles.xml und content.xml integriert
$tpl->save('output/test_fonts_output.odt');

echo "✅ Test document generated: output/test_fonts_output.odt\n";

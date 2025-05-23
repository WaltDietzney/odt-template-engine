<?php
require '../vendor/autoload.php';

use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\AbstractOdtTemplate;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\Utils\StyleWriter;

$tpl = new OdtTemplate('templates/sample_textfeld.odt');


$tpl->enableDebugMode();
//1) Lade das Template
// 2) Erstelle zwei Absätze mit je eigenem Inline-Style
$para1 = (new Paragraph('test',['background-color'=>'#eb4034']))
    ->addText('This is Arial text.', [
        'font-family' => 'Arial',
        'font-size'   => '16pt',
    ]);

$para2 = (new Paragraph())
    ->addText('This is Ubuntu text.', [
        'font-family' => 'Ubuntu',
        'font-size'   => '12pt',
        'color' => '#eb4034',
    ]);

// 3) Kombiniere die beiden in ein RichText-Objekt
$rich = (new RichText())
    ->addParagraph($para1)
    ->addParagraphBreak(1)
    ->addParagraph($para2);

// 4) Setze das Element (Platzhalter {{content}})
//$tpl->assign(['test1'=>'Mein Wert 1']);
$tpl->assign(['test2'=>'Mein Wert 2']);
//$tpl->setElement('test1', $para1);
$tpl->setElement('test1', $rich);
$tpl->render();

// 5) Speichere – dabei werden Styles & Fonts automatisch geschrieben
//    Wichtig: Hier in save() sind writeAllStyles für styles.xml und content.xml integriert
$tpl->save('output/test_fonts_output.odt');

echo "✅ Test document generated: output/test_textfeld_output.odt\n";

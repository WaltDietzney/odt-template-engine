<?php

//require_once 'vendor/autoload.php'; // Dein Autoloader

use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\Elements\DrawTextBox;

// 1. Lade dein ODT Template
$tpl = new OdtTemplate('samples/templates/template_17_textfield.odt');


// Ein rechts liegender Rahmen, der den umliegenden Text links umfließt
$textbox = (new DrawTextBox('Box1', [
    'width'            => '6cm',
    'height'           => '4cm',
    'horizontal-pos'   => '100%',      // 100% der Seite → ganz rechts
    'horizontal-rel'   => 'page',
    'wrap-influence'   => 'none',      // Textfluss beeinflusst vom Rahmen
    'background-color' => '#e0f7fa',
    'border'           => '0.04cm solid #00796b',
    'padding'          => '0.2cm',
]))
->addElement(
    (new Paragraph())
        ->addText('Rechts schweben:', ['bold' => true])
        ->addLineBreak()
        ->addText('Hier umfließt der Fließtext den Rahmen auf der linken Seite.', ['italic' => true])
);

$tpl->setElement('FLOAT_RIGHT_BOX', $textbox);

// In derselben Datei weiter unten…
// In derselben Datei weiter unten…

$textbox2 = (new DrawTextBox('Box2', [
    'width'            => '5cm',
    'height'           => '6cm',
    'horizontal-pos'   => '50%',       // Mitte der Seite
    'horizontal-rel'   => 'page',
    'vertical-pos'     => '50%',       // vertikale Mitte
    'vertical-rel'     => 'page',
    'wrap-influence' => 'once-concurrent',
    'background-color' => '#fff3e0',
    'border'           => '0.02cm dashed #e65100',
    'padding'          => '0.3cm',
    'rx'               => '0.5cm',     // abgerundete Ecken horizontal
    'ry'               => '0.5cm',     // abgerundete Ecken vertikal
]))
->addElement(
    (new Paragraph())
        ->addText('Vertikal zentriert', ['underline' => true])
)
->addElement(
    (new Paragraph())
        ->addText('Dieser Rahmen steht genau in der Mitte der Seite,', ['italic' => true])
        ->addLineBreak()
        ->addText('mit oben und unten umfließendem Text.')
);

$tpl->setElement('CENTER_BOX', $textbox2);

// Im Fließtext eingebettet…
$inlineBox = (new DrawTextBox('Inline', [
    'width'            => '4cm',
    'height'           => '3cm',
    'anchor'           => 'as-char',     // verhält sich wie ein Zeichen
    'background-color' => '#ede7f6',
    'border'           => '0.03cm solid #5e35b1',
    'padding'          => '0.1cm',
]))
->addElement(
    (new Paragraph())
        ->addText('Inline‑Box', ['bold' => true, 'italic' => true])
);

$para = new Paragraph();
$para->addText('Hier kommt eine Inline-Textbox: ')
     ->addElement($inlineBox)      // direkt in Paragraph einfügen
     ->addText(' und weiter geht’s mit normalem Text.');

$tpl->setElement('INLINE_BOX', $para);
$tpl->save('samples/output/output_17_textfield.odt');

echo "✅ Dokument mit Textbox erstellt!\n";

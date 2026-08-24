<?php

use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Elements\DrawTextBox;
use OdtTemplateEngine\Elements\Paragraph;

require_once dirname(__DIR__) . '/vendor/autoload.php';

// Load the ODT template.
$tpl = new OdtTemplate(__DIR__ . '/templates/template_17_textfield.odt');

// Create a right-aligned floating text box that lets body text flow around it.
$textbox = (new DrawTextBox('Box1', [
    'width' => '6cm',
    'height' => '4cm',
    'horizontal-pos' => '100%',
    'horizontal-rel' => 'page',
    'wrap-influence' => 'none',
    'background-color' => '#e0f7fa',
    'border' => '0.04cm solid #00796b',
    'padding' => '0.2cm',
]))
    ->addElement(
        (new Paragraph())
            ->addText('Rechts schweben:', ['bold' => true])
            ->addLineBreak()
            ->addText('Hier umfließt der Fließtext den Rahmen auf der linken Seite.', ['italic' => true])
    );

$tpl->setElement('FLOAT_RIGHT_BOX', $textbox);

// Create a centered floating text box with rounded corners.
$textbox2 = (new DrawTextBox('Box2', [
    'width' => '5cm',
    'height' => '6cm',
    'horizontal-pos' => '50%',
    'horizontal-rel' => 'page',
    'vertical-pos' => '50%',
    'vertical-rel' => 'page',
    'wrap-influence' => 'once-concurrent',
    'background-color' => '#fff3e0',
    'border' => '0.02cm dashed #e65100',
    'padding' => '0.3cm',
    'rx' => '0.5cm',
    'ry' => '0.5cm',
]))
    ->addElement(
        (new Paragraph())
            ->addText('Vertikal zentriert', ['underline' => true])
    )
    ->addElement(
        (new Paragraph())
            ->addText('Dieser Rahmen steht genau in der Mitte der Seite,', ['bold' => true, 'italic' => true])
            ->addLineBreak()
            ->addText('mit oben und unten umfließendem Text.')
    );

$tpl->setElement('CENTER_BOX', $textbox2);

// Create a text box anchored as a character and insert it directly into a paragraph.
$inlineBox = (new DrawTextBox('Inline', [
    'width' => '4cm',
    'height' => '3cm',
    'anchor' => 'as-char',
    'background-color' => '#ede7f6',
    'border' => '0.03cm solid #5e35b1',
    'padding' => '0.1cm',
]))
    ->addElement(
        (new Paragraph())
            ->addText('Inline‑Box', ['bold' => true, 'italic' => true])
    );

$para = new Paragraph();
$para->addText('Hier kommt eine Inline-Textbox: ')
    ->addElement($inlineBox)
    ->addText(' und weiter geht’s mit normalem Text.', ['bold' => true, 'italic' => true]);

$tpl->setElement('INLINE_BOX', $para);
$tpl->save(__DIR__ . '/output/output_17_textfield.odt');

echo "✅ Text box sample generated successfully.\n";

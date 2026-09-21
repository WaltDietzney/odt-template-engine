<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use OdtTemplateEngine\Elements\DrawTextBox;
use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\OdtTemplate;

$template = new OdtTemplate(__DIR__ . '/templates/template_C03_frame_layout.odt');
$imagePath = dirname(__DIR__) . '/assets/Logo.png';

$callout = (new DrawTextBox('DeliveryCallout', [
    'background-color' => '#e8f2fb',
    'border' => '0.04cm solid #3978a8',
    'padding' => '0.2cm',
]))->setFrameLayout([
    'anchor' => 'paragraph',
    'width' => '6.2cm',
    'height' => '2.4cm',
    'horizontal' => ['alignment' => 'right', 'relative-to' => 'paragraph'],
    'vertical' => ['alignment' => 'top', 'relative-to' => 'paragraph'],
    'wrap' => 'left',
])->addElement(
    (new Paragraph())
        ->addText('DELIVERY NOTE', ['bold' => true])
        ->addLineBreak()
        ->addText('A floating text box keeps this short decision visible beside the report narrative.')
);

$inline = (new ImageElement($imagePath, ['width' => '1.25cm']))
    ->setFrameLayout([
        'anchor' => 'as-char',
        'vertical' => ['alignment' => 'top', 'relative-to' => 'baseline'],
    ]);
$inlineParagraph = (new Paragraph())->addText('An inline brand mark ')->addElement($inline)->addText(' participates in the text line.');

$rightImage = (new ImageElement($imagePath, ['width' => '3.4cm']))
    ->setFrameLayout([
        'anchor' => 'paragraph',
        'horizontal' => ['alignment' => 'right', 'relative-to' => 'paragraph'],
        'vertical' => ['alignment' => 'top', 'relative-to' => 'paragraph'],
        'wrap' => 'left',
    ]);

$offsetBox = (new DrawTextBox('ReviewCallout', [
    'background-color' => '#f4efe2',
    'border' => '0.04cm solid #a88b22',
    'padding' => '0.16cm',
]))->setFrameLayout([
    'anchor' => 'paragraph',
    'width' => '5.2cm',
    'height' => '1.8cm',
    'horizontal' => ['offset' => '1cm', 'relative-to' => 'paragraph'],
    'vertical' => ['offset' => '0.2cm', 'relative-to' => 'paragraph'],
    'wrap' => 'left',
])->addElement((new Paragraph())->addText('Review point: alignment is not an offset.', ['bold' => true]));

$template->setElement('floating_callout', $callout);
$template->setElement('inline_mark', $inlineParagraph);
$template->setElement('right_image', $rightImage);
$template->setElement('offset_callout', $offsetBox);
$template->save(__DIR__ . '/output/output_C03_frame_layout.odt');

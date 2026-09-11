<?php

/**
 * Sample 27: Frame Layout Showcase
 *
 * Demonstrates the FRAME-LAYOUT-01 friendly API for DrawTextBox and
 * ImageElement:
 * - paragraph-relative left / center / right alignment;
 * - vertical top / middle / bottom alignment;
 * - explicit x / y offsets;
 * - independent wrap modes and compatibility layout policies;
 * - as-character text-box and image insertion;
 * - image placement in a page header.
 *
 * Template placeholders:
 * - {{left_box}}
 * - {{center_box}}
 * - {{right_box}}
 * - {{offset_box}}
 * - {{inline_demo}}
 * - {{image_demo}}
 * - {{header_image}}   (place this one in the page header)
 */

use OdtTemplateEngine\Elements\DrawTextBox;
use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\OdtTemplate;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$template = new OdtTemplate(__DIR__ . '/templates/template_27_frameLayout.odt');
$imagePath = dirname(__DIR__) . '/assets/WaltDietzney.png';

/*
 * -------------------------------------------------------------------------
 * Alignment row
 * -------------------------------------------------------------------------
 *
 * Three paragraph-anchored frames use the same geometry but different
 * horizontal and vertical semantic alignment. Their appearance is deliberately
 * distinct so that Writer rendering can be inspected at a glance.
 */

$leftBox = (new DrawTextBox('FrameLayoutLeft', [
    'background-color' => '#e8f2fb',
    'border' => '0.04cm solid #3978a8',
    'padding' => '0.18cm',
]))
    ->setFrameLayout([
        'anchor' => 'paragraph',
        'width' => '4.2cm',
        'height' => '2.2cm',
        'horizontal' => [
            'alignment' => 'left',
            'relative-to' => 'paragraph',
        ],
        'vertical' => [
            'alignment' => 'top',
            'relative-to' => 'paragraph',
        ],
        'wrap' => 'right',
    ])
    ->addElement(
        (new Paragraph())
            ->addText('LEFT / TOP', ['bold' => true])
            ->addLineBreak()
            ->addText('Paragraph-relative frame; text wraps on the right.')
    );

$centerBox = (new DrawTextBox('FrameLayoutCenter', [
    'background-color' => '#edf5e8',
    'border' => '0.04cm solid #5b8748',
    'padding' => '0.18cm',
]))
    ->setFrameLayout([
        'anchor' => 'paragraph',
        'width' => '4.2cm',
        'height' => '2.2cm',
        'horizontal' => [
            'alignment' => 'center',
            'relative-to' => 'paragraph',
        ],
        'vertical' => [
            'alignment' => 'middle',
            'relative-to' => 'paragraph',
        ],
        'wrap' => 'parallel',
    ])
    ->flowWithText(true)
    ->addElement(
        (new Paragraph())
            ->addText('CENTER / MIDDLE', ['bold' => true])
            ->addLineBreak()
            ->addText('Friendly layout plus flowWithText compatibility policy.')
    );

$rightBox = (new DrawTextBox('FrameLayoutRight', [
    'background-color' => '#fcebdc',
    'border' => '0.04cm solid #b36b2c',
    'padding' => '0.18cm',
    'wrap-influence' => 'once-concurrent',
]))
    ->setFrameLayout([
        'anchor' => 'paragraph',
        'width' => '4.2cm',
        'height' => '2.2cm',
        'horizontal' => [
            'alignment' => 'right',
            'relative-to' => 'paragraph',
        ],
        'vertical' => [
            'alignment' => 'bottom',
            'relative-to' => 'paragraph',
        ],
        'wrap' => 'left',
    ])
    ->setAllowOverlap(false)
    ->addElement(
        (new Paragraph())
            ->addText('RIGHT / BOTTOM', ['bold' => true])
            ->addLineBreak()
            ->addText('Wrap influence and overlap remain compatibility policies.')
    );

$template->setElement('left_box', $leftBox);
$template->setElement('center_box', $centerBox);
$template->setElement('right_box', $rightBox);

/*
 * -------------------------------------------------------------------------
 * Explicit offset positioning
 * -------------------------------------------------------------------------
 *
 * Offsets are coordinates on draw:frame. The corresponding from-left /
 * from-top modes and relations are emitted through the graphic style.
 */

$offsetBox = (new DrawTextBox('FrameLayoutOffset', [
    'background-color' => '#f1eafa',
    'border' => '0.04cm solid #7352a3',
    'padding' => '0.18cm',
]))
    ->setFrameLayout([
        'anchor' => 'paragraph',
        'width' => '4.6cm',
        'height' => '2.0cm',
        'horizontal' => [
            'offset' => '2cm',
            'relative-to' => 'page-content',
        ],
        'vertical' => [
            'offset' => '1cm',
            'relative-to' => 'paragraph',
        ],
        'wrap' => 'parallel',
    ])
    ->addElement(
        (new Paragraph())
            ->addText('EXPLICIT X / Y', ['bold' => true])
            ->addLineBreak()
            ->addText('x = 2 cm from page content; y = 1 cm from paragraph.')
    );

$template->setElement('offset_box', $offsetBox);

/*
 * -------------------------------------------------------------------------
 * As-character parity
 * -------------------------------------------------------------------------
 *
 * Both frame-backed element types use the same anchor-sensitive insertion
 * rule. The containing paragraph remains the native text-flow carrier.
 */

$inlineBox = (new DrawTextBox('FrameLayoutInline', [
    'background-color' => '#fff7cc',
    'border' => '0.03cm solid #a88b22',
    'padding' => '0.08cm',
]))
    ->setFrameLayout([
        'anchor' => 'as-char',
        'width' => '2.8cm',
        'height' => '0.9cm',
        'vertical' => [
            'alignment' => 'top',
            'relative-to' => 'baseline',
        ],
    ])
    ->addElement(
        (new Paragraph())->addText('INLINE BOX', ['bold' => true])
    );

$inlineImage = (new ImageElement($imagePath, [
    'width' => '1.1cm',
]))
    ->setFrameLayout([
        'anchor' => 'as-char',
        'vertical' => [
            'alignment' => 'top',
            'relative-to' => 'baseline',
        ],
    ]);

$inlineParagraph = (new Paragraph())
    ->addText('Text before ')
    ->addElement($inlineBox)
    ->addText(' between ')
    ->addElement($inlineImage)
    ->addText(' text after.');

$template->setElement('inline_demo', $inlineParagraph);

/*
 * -------------------------------------------------------------------------
 * Floating image parity
 * -------------------------------------------------------------------------
 */

$floatingImage = (new ImageElement($imagePath, [
    'width' => '2.2cm',
]))
    ->setFrameLayout([
        'anchor' => 'paragraph',
        'horizontal' => [
            'alignment' => 'right',
            'relative-to' => 'paragraph',
        ],
        'vertical' => [
            'alignment' => 'top',
            'relative-to' => 'paragraph',
        ],
        'wrap' => 'left',
    ]);

$template->setElement('image_demo', $floatingImage);

/*
 * -------------------------------------------------------------------------
 * Header image
 * -------------------------------------------------------------------------
 *
 * The template must place {{header_image}} inside a normal paragraph in the
 * page header. Slice 4 preserves that paragraph for the as-char frame.
 */

$headerImage = (new ImageElement($imagePath, [
    'width' => '0.9cm',
]))
    ->setFrameLayout([
        'anchor' => 'as-char',
        'vertical' => [
            'alignment' => 'top',
            'relative-to' => 'baseline',
        ],
    ]);

$template->setElement('header_image', $headerImage);

$template->save(__DIR__ . '/output/output_27_frameLayout.odt');

echo "Frame layout sample generated successfully.\n";

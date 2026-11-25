<?php

declare(strict_types=1);

require __DIR__ . '/../../v04/native/bootstrap.php';

use OdtTemplateEngine\Elements\DrawingGroup;
use OdtTemplateEngine\Elements\DrawingLayout;
use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\ShapeAppearance;
use OdtTemplateEngine\Elements\ShapeElement;
use OdtTemplateEngine\Elements\ShapeGeometry;
use OdtTemplateEngine\Elements\ShapeTextLayout;
use OdtTemplateEngine\PageLayoutOdtTemplate;

function shape(string $color, string $x, int $z, string $layer = 'foreground'): ShapeElement
{
    return new ShapeElement(ShapeGeometry::preset('rectangle'), ShapeAppearance::solid($color),
        DrawingLayout::fromArray([
            'anchor' => 'paragraph', 'width' => '3cm', 'height' => '1.7cm',
            'horizontal' => ['offset' => $x, 'relative-to' => 'paragraph-content'],
            'vertical' => ['offset' => '0cm', 'relative-to' => 'paragraph-content'],
            'wrap' => 'run-through', 'run-through' => $layer, 'z-index' => $z,
        ]));
}
function tag(string $label): ShapeElement
{
    return (new ShapeElement(ShapeGeometry::preset('round-rectangle'), ShapeAppearance::solid('#203A52'),
        DrawingLayout::fromArray(['anchor' => 'as-char', 'width' => '1cm', 'height' => '0.2cm'])))
        ->setTextAutoGrow()
        ->setTextLayout(new ShapeTextLayout('top', ['top' => '0.152cm', 'bottom' => '0.152cm', 'left' => '0.152cm', 'right' => '0.152cm']))
        ->addParagraph((new Paragraph(null, ['fo:margin' => '0cm']))->addText($label, [
            'font-family' => 'Liberation Serif', 'font-size' => '11pt', 'color' => '#FFFFFF',
        ]));
}
foreach (['foreground', 'background'] as $layer) {
    $t = new PageLayoutOdtTemplate(__DIR__ . '/../../flow/seed.odt');
    $t->definePageLayout('SceneLayout', ['page-width' => '21cm', 'page-height' => '29.7cm', 'margin-top' => '2cm', 'margin-bottom' => '2cm']);
    $backing = new ShapeElement(ShapeGeometry::preset('ellipse'), ShapeAppearance::solid('#DCE8F2'),
        DrawingLayout::fromArray(['anchor' => 'paragraph', 'width' => '0.5cm', 'height' => '0.5cm']));
    $icon = (new ImageElement(__DIR__ . '/../../v04/native/assets/lucide-mail.svg'))->setFrameLayout([
        'anchor' => 'paragraph', 'width' => '0.3cm', 'height' => '0.3cm',
        'horizontal' => ['offset' => '0.1cm', 'relative-to' => 'paragraph-content'],
        'vertical' => ['offset' => '0.1cm', 'relative-to' => 'paragraph-content'],
        'wrap' => 'run-through', 'run-through' => 'foreground',
    ]);
    $header = (new Paragraph())->addInlineElement(new DrawingGroup('header-symbol', [$backing, $icon]))
        ->addInlineElement(tag('Header · Serif 11'));
    $footer = (new Paragraph())->addInlineElement(tag('Footer · Serif 11'));
    $t->definePageStyle('ScenePage', 'SceneLayout', ['header' => $header, 'footer' => $footer]);
    $t->assignPageStyle('SceneStart', 'ScenePage');
    $t->appendBody((new Paragraph('SceneStart'))->addText('Drawing contexts · ' . $layer));
    $t->appendBody((new Paragraph(null, ['fo:margin-top' => '1cm', 'fo:margin-bottom' => '1cm']))
        ->addDrawing(shape('#FFCC66', '0cm', 0, $layer))
        ->addText('MMMM', ['font-family' => 'Liberation Sans', 'font-size' => '48pt', 'color' => '#000000']));
    $t->appendBody((new Paragraph())->addText('Group: blue in front of red'));
    $t->appendBody((new Paragraph(null, ['fo:margin-bottom' => '2cm']))->addInlineElement(new DrawingGroup('overlap', [
        shape('#FF0000', '0cm', 99), shape('#0000FF', '1cm', 0),
    ])));
    $t->appendBody((new Paragraph())->addText('Independent objects: red in front of blue'));
    $t->appendBody((new Paragraph(null, ['fo:margin-bottom' => '2cm']))
        ->addDrawing(shape('#FF0000', '0cm', 9))->addDrawing(shape('#0000FF', '1cm', 1)));
    $t->appendBody((new Paragraph())->addInlineElement(tag('Body · Serif 11')));
    $t->save(__DIR__ . '/' . $layer . '.odt');
    $t->save(__DIR__ . '/' . $layer . '-repeat.odt');
    $t->cleanup();
}
file_put_contents(__DIR__ . '/outputs.json', json_encode(['foreground', 'background']));

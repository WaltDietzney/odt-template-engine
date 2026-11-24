<?php

declare(strict_types=1);

require __DIR__ . '/../native/bootstrap.php';
require __DIR__ . '/BuildingBlocks.php';

use H07Research\UserFieldParagraph;
use H07Research\DrawingGroup;
use OdtTemplateEngine\Elements\DrawingLayout;
use OdtTemplateEngine\Elements\ShapeElement;
use OdtTemplateEngine\Elements\ShapeGeometry;
use OdtTemplateEngine\Elements\ShapeAppearance;
use OdtTemplateEngine\Elements\ShapeTextLayout;
use OdtTemplateEngine\Elements\DrawTextBox;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\OdtTemplate;

$data = json_decode(file_get_contents(__DIR__ . '/data.json'), true);
$t = new OdtTemplate(__DIR__ . '/seed.odt');
$layout = DrawingLayout::fromArray([
    'anchor' => 'paragraph', 'width' => '6cm', 'height' => '0.6cm',
    'horizontal' => ['offset' => '0cm', 'relative-to' => 'paragraph'],
    'vertical' => ['offset' => '0cm', 'relative-to' => 'paragraph'],
    'wrap' => 'run-through',
]);
$field = static fn () => new UserFieldParagraph([
    ['name' => 'phone', 'value' => $data['phone']],
], ['font-family' => 'Liberation Sans', 'font-size' => '9pt', 'color' => '#375e80']);
foreach (['shape', 'group-shape', 'group-frame', 'writer-frame'] as $case) {
    $t->appendBody((new Paragraph())->addText($case));
    $shape = new ShapeElement(ShapeGeometry::preset('rectangle'), ShapeAppearance::none('none'), $layout);
    $shape->addParagraph($field());
    $shape->setTextLayout(new ShapeTextLayout('middle', [], 'left'));
    $box = (new DrawTextBox($case, ['width' => '6cm', 'height' => '0.6cm']))
        ->setFrameLayout(['anchor' => 'as-char', 'width' => '6cm', 'height' => '0.6cm'])
        ->addElement($field());
    $element = match ($case) {
        'shape' => $shape,
        'group-shape' => new DrawingGroup($case, [$shape]),
        'group-frame' => new DrawingGroup($case, [$box]),
        'writer-frame' => $box,
    };
    $t->appendBody((new Paragraph())->addElement($element));
    $t->appendBody((new Paragraph())->addText('FOLLOWING'));
}
$t->save(__DIR__ . '/carrier.odt');
$t->cleanup();

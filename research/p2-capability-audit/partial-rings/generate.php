<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/vendor/autoload.php';

use OdtTemplateEngine\Elements\DrawingGroup;
use OdtTemplateEngine\Elements\DrawingLayout;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\ShapeAppearance;
use OdtTemplateEngine\Elements\ShapeElement;
use OdtTemplateEngine\Elements\ShapeGeometry;
use OdtTemplateEngine\PageLayoutOdtTemplate;

/** Consumer example: rating meaning, track and color selection remain outside geometry. */
function ring(string $name, float $percent, float $ratio = 7000 / 10800, string $size = '2cm', bool $stroke = false): DrawingGroup
{
    $layout = DrawingLayout::fromArray([
        'anchor' => 'paragraph', 'width' => $size, 'height' => $size,
        'horizontal' => ['offset' => '0cm', 'relative-to' => 'paragraph-content'],
        'vertical' => ['offset' => '0cm', 'relative-to' => 'paragraph-content'],
        'wrap' => 'run-through', 'run-through' => 'foreground',
    ]);
    $shapes = [new ShapeElement(ShapeGeometry::annularSector(360, $ratio), ShapeAppearance::solid('#DDDDDD'), $layout)];
    $progress = ShapeGeometry::annularSector($percent / 100 * 360, $ratio);
    if ($progress !== null) {
        $shapes[] = new ShapeElement($progress, ShapeAppearance::solid('#337799', $stroke ? 'solid' : 'none'), $layout);
    }
    return new DrawingGroup($name, $shapes, DrawingLayout::fromArray([
        'anchor' => 'as-char', 'margin' => ['left' => '0.1cm', 'right' => '0.15cm', 'top' => '0.1cm', 'bottom' => '0.1cm'],
    ]));
}
function heading(PageLayoutOdtTemplate $t, string $label): void
{
    $t->appendBody((new Paragraph())->addText($label, ['font-family' => 'Liberation Sans', 'font-size' => '12pt', 'bold' => true]));
}
$seed = dirname(__DIR__) . '/flow/seed.odt';
$t = new PageLayoutOdtTemplate($seed);
$t->setPageMargins('1.5cm', '1.5cm', '1.5cm', '1.5cm');
heading($t, 'P2-I05 · Native Teilringe · 0 / 1 / 70 / 99 / 100 %');
$values = [0, 1, 70, 99, 100];
$labels = (new Paragraph());
foreach ($values as $value) {
    $labels->addInlineElement(ring('main-' . $value, $value))->addText(' ' . $value . '%   ', ['font-family' => 'Liberation Sans', 'font-size' => '9pt']);
}
$t->appendBody($labels);
heading($t, 'Freie Endwinkel · keine Rasterung auf 3 Grad');
$p = new Paragraph();
foreach ([0.1, 33.37, 70.1] as $value) {
    $p->addInlineElement(ring('arbitrary-' . $value, $value))->addText(' ' . $value . '%   ');
}
$t->appendBody($p);
heading($t, 'Dünner Ring · eigenständige Größenwahl');
$t->appendBody((new Paragraph())->addInlineElement(ring('thin-small', 70, 0.85, '1.2cm'))->addText(' 70% / 1.2cm ')
    ->addInlineElement(ring('thin-large', 70, 0.85, '2.6cm'))->addText(' 70% / 2.6cm'));
heading($t, 'Native Kontur · ShapeAppearance solid stroke');
$t->appendBody((new Paragraph())->addInlineElement(ring('outline', 70, 7000 / 10800, '2cm', true))->addText(' 70% mit Kontur'));
$t->appendBody((new Paragraph())->addText('Nullwert: nur Grundring · Vollwert: vorhandenes Ring-Preset · native bearbeitbare Zeichengruppen', ['font-size' => '8pt']));
$t->save(__DIR__ . '/showcase.odt');
$t->save(__DIR__ . '/showcase-repeat.odt');
$t->cleanup();
$t = new PageLayoutOdtTemplate(__DIR__ . '/showcase.odt');
$t->save(__DIR__ . '/showcase-reloaded.odt');
$t->cleanup();
$t = new PageLayoutOdtTemplate($seed);
$t->definePageLayout('RingsLayout', ['page-width' => '21cm', 'page-height' => '29.7cm', 'margin-top' => '2.8cm', 'margin-bottom' => '2.8cm']);
$t->definePageStyle('RingsPage', 'RingsLayout', [
    'header' => (new Paragraph())->addInlineElement(ring('header', 70, 7000 / 10800, '1.2cm'))->addText(' Header · 70%'),
    'footer' => (new Paragraph())->addInlineElement(ring('footer', 99, 0.85, '1.2cm'))->addText(' Footer · 99%'),
]);
$t->assignPageStyle('RingsStart', 'RingsPage');
$t->appendBody((new Paragraph('RingsStart'))->addText('P2-I05 · Body / Header / Footer', ['font-size' => '16pt', 'bold' => true]));
$t->appendBody((new Paragraph())->addInlineElement(ring('body', 70))->addText(' Body · 70%'));
$t->save(__DIR__ . '/stories.odt');
$t->save(__DIR__ . '/stories-repeat.odt');
$t->cleanup();
// Calibrated one-ring pages make visible radial probes independent of label/line flow.
foreach ([0, 1, 70, 99, 100, 70.1] as $value) {
    $t = new PageLayoutOdtTemplate($seed);
    $t->setPageMargins('2cm', '2cm', '2cm', '2cm');
    $layout = DrawingLayout::fromArray(['anchor' => 'paragraph', 'width' => '4cm', 'height' => '4cm',
        'horizontal' => ['offset' => '0cm', 'relative-to' => 'page-content'],
        'vertical' => ['offset' => '0cm', 'relative-to' => 'page-content'],
        'wrap' => 'run-through', 'run-through' => 'foreground']);
    $shapes = [new ShapeElement(ShapeGeometry::annularSector(360, 7000 / 10800), ShapeAppearance::solid('#DDDDDD'), $layout)];
    $geometry = ShapeGeometry::annularSector($value / 100 * 360, 7000 / 10800);
    if ($geometry !== null) {
        $shapes[] = new ShapeElement($geometry, ShapeAppearance::solid('#337799'), $layout);
    }
    $groupLayout = DrawingLayout::fromArray(['anchor' => 'paragraph', 'z-index' => 0]);
    $t->appendBody((new Paragraph())->addDrawing(new DrawingGroup('probe-' . $value, $shapes, $groupLayout)));
    $t->appendBody((new Paragraph(null, ['fo:margin-top' => '4.2cm']))->addText($value . '%'));
    $t->save(__DIR__ . '/probe-' . $value . '.odt');
    $t->cleanup();
}
file_put_contents(__DIR__ . '/outputs.json', json_encode([
    'showcase', 'showcase-repeat', 'showcase-reloaded', 'stories', 'stories-repeat',
    'probe-0', 'probe-1', 'probe-70', 'probe-99', 'probe-100', 'probe-70.1',
], JSON_PRETTY_PRINT));

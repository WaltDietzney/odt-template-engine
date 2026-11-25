<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/vendor/autoload.php';

use OdtTemplateEngine\Elements\DrawTextBox;
use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\ShapeAppearance;
use OdtTemplateEngine\Elements\ShapeElement;
use OdtTemplateEngine\Elements\ShapeGeometry;
use OdtTemplateEngine\Elements\DrawingLayout;
use OdtTemplateEngine\PageLayoutOdtTemplate;

foreach (['assets/a', 'assets/b'] as $dir) {
    if (!is_dir(__DIR__ . '/' . $dir)) {
        mkdir(__DIR__ . '/' . $dir, 0777, true);
    }
}
foreach (['a' => '#375E80', 'b' => '#C65D32'] as $dir => $color) {
    file_put_contents(__DIR__ . '/assets/' . $dir . '/panel.svg',
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 40">'
        . '<rect width="80" height="40" fill="' . $color . '"/>'
        . '<circle cx="40" cy="20" r="12" fill="#FFFFFF"/>'
        . '<path d="M33 20L38 25L48 14" fill="none" stroke="' . $color . '" stroke-width="3"/></svg>');
}
$old = __DIR__ . '/assets/a/panel.svg';
$new = __DIR__ . '/assets/b/panel.svg';
$seed = dirname(__DIR__) . '/flow/seed.odt';
$t = new PageLayoutOdtTemplate($seed);
$t->setPageMargins('1.5cm', '1.5cm', '1.5cm', '1.5cm');
$t->appendBody((new Paragraph())->addText('P2-I04 · Native SVG resources', ['font-size' => '16pt', 'bold' => true]));
foreach (['Lucide' => 'lucide-mail.svg', 'Tabler' => 'tabler-mail.svg', 'Custom' => 'custom-mail.svg'] as $family => $file) {
    $source = dirname(__DIR__) . '/v04/native/assets/' . $file;
    $dom = new DOMDocument();
    $dom->load($source, LIBXML_NONET);
    $dom->documentElement->setAttribute('stroke', '#375E80');
    $variant = __DIR__ . '/assets/' . $file;
    $dom->save($variant);
    $p = (new Paragraph())->addText($family . ' · ');
    foreach (['0.5cm', '1cm', '1.5cm'] as $size) {
        $p->addInlineElement((new ImageElement($variant, ['width' => $size]))->setFrameLayout([
            'anchor' => 'as-char', 'width' => $size, 'height' => $size,
            'wrap' => 'run-through', 'run-through' => 'foreground',
        ]))->addText('  ');
    }
    $t->appendBody($p);
}
$t->appendBody((new Paragraph())->addText('Non-square viewBox · proportional width-only 40mm'));
$t->appendBody((new Paragraph())->addElement(new ImageElement($old, ['width' => '40mm', 'anchor' => 'as-char'])));
$t->appendBody((new Paragraph())->addText('Two explicit dimensions remain explicit · 3cm × 1.5cm'));
$t->appendBody((new Paragraph())->addElement(new ImageElement($old, ['width' => '3cm', 'height' => '1.5cm', 'anchor' => 'as-char'])));
$t->appendBody((new Paragraph())->addText('Native shape with SVG bitmap fill'));
$t->appendBody((new Paragraph())->addInlineElement(new ShapeElement(
    ShapeGeometry::preset('round-rectangle'), ShapeAppearance::bitmap($old),
    DrawingLayout::fromArray(['anchor' => 'as-char', 'width' => '4cm', 'height' => '2cm'])
)));
$t->save(__DIR__ . '/svg-gallery.odt');
$t->save(__DIR__ . '/svg-gallery-repeat.odt');
$t->cleanup();
$t = new PageLayoutOdtTemplate($seed);
$t->styles()->defineFillImage('SharedSvg', $old);
function box(string $name): DrawTextBox
{
    return (new DrawTextBox($name, ['fo:border' => 'none', 'fo:padding' => '0cm']))
        ->setFrameLayout(['anchor' => 'as-char', 'width' => '4cm', 'height' => '2cm'])
        ->setBackground(['type' => 'bitmap', 'name' => 'SharedSvg', 'repeat' => 'stretch']);
}
$t->definePageLayout('ResourceLayout', ['margin-top' => '2.5cm', 'margin-bottom' => '2cm']);
$t->definePageStyle('ResourcePage', 'ResourceLayout', ['header' => (new Paragraph())->addInlineElement(box('HeaderFill'))]);
$t->assignPageStyle('ResourceStart', 'ResourcePage');
$t->appendBody((new Paragraph('ResourceStart'))->addText('P2-I04 · Isolated resource replacement', ['font-size' => '16pt', 'bold' => true]));
$t->appendBody((new Paragraph())->addText('Fill A: changes · Fill B: stays blue'));
$t->appendBody((new Paragraph())->addInlineElement(box('FillA'))->addText('  ')->addInlineElement(box('FillB')));
$t->appendBody((new Paragraph())->addText('Direct A: changes · Direct B: stays blue'));
$t->appendBody((new Paragraph())->addText('{{DirectA}}'));
$t->appendBody((new Paragraph())->addText('{{DirectB}}'));
$t->save(__DIR__ . '/slots.odt');
$t->cleanup();
$t = new PageLayoutOdtTemplate(__DIR__ . '/slots.odt');
$t->setImage('DirectA', $old, ['width' => '4cm', 'height' => '2cm', 'anchor' => 'as-char']);
$t->setImage('DirectB', $old, ['width' => '4cm', 'height' => '2cm', 'anchor' => 'as-char']);
$t->save(__DIR__ . '/shared-before.odt');
$t->replaceBitmapFillByName('FillA', $new);
$t->replaceBitmapFillByName('HeaderFill', $new);
$t->replaceImageByName('DirectA', $new, ['width' => '4cm', 'height' => '2cm']);
$t->save(__DIR__ . '/isolated-after.odt');
$t->save(__DIR__ . '/isolated-after-repeat.odt');
$t->cleanup();
$t = new PageLayoutOdtTemplate(__DIR__ . '/isolated-after.odt');
$t->render();
$t->save(__DIR__ . '/isolated-after-reloaded.odt');
$t->cleanup();
file_put_contents(__DIR__ . '/outputs.json', json_encode([
    'svg-gallery', 'svg-gallery-repeat', 'shared-before', 'isolated-after', 'isolated-after-repeat', 'isolated-after-reloaded',
], JSON_PRETTY_PRINT));

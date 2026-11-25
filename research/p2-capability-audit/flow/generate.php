<?php

declare(strict_types=1);

require __DIR__ . '/../v04/native/bootstrap.php';

use OdtTemplateEngine\Elements\DrawTextBox;
use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\PageLayoutOdtTemplate;

function doc(string $title): PageLayoutOdtTemplate
{
    $t = new PageLayoutOdtTemplate(__DIR__ . '/seed.odt');
    $t->definePageLayout('FlowLayout', ['page-width' => '12cm', 'page-height' => '15cm',
        'margin-top' => '1cm', 'margin-bottom' => '1cm', 'margin-left' => '1cm', 'margin-right' => '1cm']);
    $t->definePageStyle('FlowPage', 'FlowLayout', ['next-style-name' => 'FlowPage']);
    $t->assignPageStyle('FlowStart', 'FlowPage');
    $t->appendBody((new Paragraph('FlowStart'))->addText($title, ['bold' => true]));
    return $t;
}
function saveDoc(PageLayoutOdtTemplate $t, string $name): void
{
    try {
        $t->render();
        $t->save(__DIR__ . '/' . $name . '.odt');
        $t->save(__DIR__ . '/' . $name . '-repeat.odt');
    } finally {
        $t->cleanup();
    }
}
function box(string $name, string $text): DrawTextBox
{
    return (new DrawTextBox($name, ['background-color' => '#DCE8F2', 'border' => 'none']))
        ->setFrameLayout(['anchor' => 'as-char', 'width' => '1cm', 'height' => '0.2cm',
            'vertical' => ['alignment' => 'middle', 'relative-to' => 'baseline']])
        ->setTextBoxSizing(['min-width' => '1cm', 'min-height' => '0.2cm',
            'auto-grow-width' => true, 'auto-grow-height' => true])
        ->setFrameSpacing(['padding-left' => '0.152cm', 'padding-right' => '0.152cm',
            'padding-top' => '0.152cm', 'padding-bottom' => '0.152cm',
            'margin-left' => '0.08cm', 'margin-right' => '0.08cm',
            'margin-top' => '0.06cm', 'margin-bottom' => '0.06cm'])
        ->addElement((new Paragraph(null, ['font-size' => '9pt', 'fo:margin' => '0cm']))->addText($text));
}
$t = doc('Geordnete native Inline-Inhalte');
$t->declareUserField('value', 'FIELD');
$t->appendBody((new Paragraph())->setOutlineLevel(2)->addText('A ')
    ->addInlineElement(new ImageElement(dirname(__DIR__, 3) . '/assets/banner.png', ['anchor' => 'as-char', 'width' => '1cm']))
    ->addText(' B ')->addInlineElement(box('inline-tag', 'TAG'))->addText(' C ')->addUserField('value')->addText(' D'));
$t->definePageStyle('OrderedPage', 'FlowLayout', ['next-style-name' => 'OrderedPage',
    'header' => (new Paragraph())->addText('H ')->addInlineElement(box('header-tag', 'HEADER'))->addText(' I'),
    'footer' => (new Paragraph())->addText('J ')->addInlineElement(box('footer-tag', 'FOOTER'))->addText(' K'),
]);
$t->assignPageStyle('FlowStart', 'OrderedPage');
saveDoc($t, 'ordered');
$values=['short' => 'Am Park 7, 32049 Herford',
    'long' => 'An der alten naturwissenschaftlichen Forschungsstation 128 a, 32049 Bad Oeynhausen mit weiterem Ortsteil und langem Zusatz, Deutschland'];
foreach ($values as $kind => $value) {
    $t = doc('Native Kontakt-Zeilenhoehe');
    $t->declareUserField('address', $value);
    $frame = (new DrawTextBox('address-frame', ['background-color' => '#DCE8F2']))
        ->setFrameLayout(['anchor' => 'as-char', 'width' => '4cm', 'height' => '0.1cm'])
        ->setTextBoxSizing(['min-height' => '0.1cm', 'auto-grow-height' => true, 'auto-grow-width' => false])
        ->setFrameSpacing(['padding' => '0.15cm'])
        ->addElement((new Paragraph(null, ['font-size' => '10pt', 'fo:margin' => '0cm']))->addUserField('address'));
    $t->appendBody((new Paragraph())->addInlineElement($frame));
    $t->appendBody((new Paragraph())->addText('FOLLOWING'));
    saveDoc($t, 'flow-' . $kind);
}
$t = doc('Rechteckige native Tags');
$p = new Paragraph(null, ['font-size' => '9pt', 'fo:margin' => '0cm']);
$labels=[];
for ($i=0; $i<90; ++$i) {
    $label='Kenntnis ' . str_pad((string) $i, 3, '0', STR_PAD_LEFT);
    $labels[]=$label;
    $p->addInlineElement(box('tag-' . $i, $label))->addText(' ');
}
$t->appendBody($p);
saveDoc($t, 'tags-multipage');
file_put_contents(__DIR__ . '/tag-labels.json', json_encode($labels, JSON_PRETTY_PRINT) . "\n");
$t = doc('Breiter Inhalt: expliziter Umbruch');
$long='Sehr langer Kenntnistext mit mehreren Worten und vollstaendig sichtbarem Inhalt ohne Verkleinerung oder Abschneiden.';
$f=box('wide-fallback', $long)->setFrameLayout(['anchor' => 'as-char', 'width' => '4cm', 'height' => '0.2cm'])
    ->setTextBoxSizing(['min-height' => '0.2cm', 'auto-grow-width' => false, 'auto-grow-height' => true]);
$t->appendBody((new Paragraph())->addInlineElement($f));
$t->appendBody((new Paragraph())->addText('AFTERWIDE'));
saveDoc($t,'wide-fallback');
file_put_contents(__DIR__ . '/data.json', json_encode(['addresses'=>$values,'wide'=>$long],JSON_PRETTY_PRINT) . "\n");
file_put_contents(__DIR__ . '/outputs.json',json_encode(['ordered','flow-short','flow-long','tags-multipage','wide-fallback'],JSON_PRETTY_PRINT) . "\n");

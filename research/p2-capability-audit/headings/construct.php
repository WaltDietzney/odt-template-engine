<?php

declare(strict_types=1);

require __DIR__ . '/../v04/native/bootstrap.php';

use OdtTemplateEngine\Elements\OdtElement;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\OdtTemplate;

/** Research input adapter only: reuse exact authored decoration until CC04 integration. */
final class AcceptedHeadingDecoration extends OdtElement
{
    public function __construct(private readonly string $source)
    {
    }

    public function toDomNode(DOMDocument $dom): DOMNode
    {
        $source = new DOMDocument();
        $source->load($this->source, LIBXML_NONET);
        return $dom->importNode($source->documentElement, true);
    }
}

$labels = json_decode(file_get_contents(__DIR__ . '/labels.json'), true);
$t = new OdtTemplate(__DIR__ . '/decorated-seed.odt');
try {
    foreach ($labels as $i => $label) {
        $heading = (new Paragraph('SemanticHeading'))->setOutlineLevel(1)->addText($label);
        $heading->addElement(new AcceptedHeadingDecoration(__DIR__ . '/decoration-input/' . $i . '.xml'));
        $t->setElement('heading_' . $i, $heading);
    }
    $t->render();
    $t->save(__DIR__ . '/decorated.odt');
    $t->render();
    $t->save(__DIR__ . '/decorated-repeat.odt');
} finally {
    $t->cleanup();
}
$t = new OdtTemplate(__DIR__ . '/decorated.odt');
try {
    $t->save(__DIR__ . '/decorated-reloaded.odt');
} finally {
    $t->cleanup();
}
$t = new OdtTemplate(__DIR__ . '/plain-seed.odt');
try {
    $t->declareUserField('heading.subject', 'Initial');
    $titles = ['Ebene 1', 'Ebene 2', 'Ebene 3', 'Ebene 10', 'Feld: Native Überschrift'];
    foreach ([1, 2, 3, 10] as $i => $level) {
        $t->appendBody((new Paragraph(null, ['font-size' => '13pt', 'bold' => true]))
            ->setOutlineLevel($level)->addText($titles[$i]));
        $t->appendBody((new Paragraph())->addText('Fließtext ohne Gliederung.'));
    }
    $t->appendBody((new Paragraph())->setOutlineLevel(1)->addText('Feld: ')->addUserField('heading.subject'));
    $t->appendBody((new Paragraph(null, ['font-size' => '16pt', 'bold' => true]))->addText('Nur grafisch formatiert'));
    $t->setUserField('heading.subject', 'Native Überschrift');
    $t->render();
    $t->save(__DIR__ . '/levels.odt');
    $t->save(__DIR__ . '/levels-repeat.odt');
} finally {
    $t->cleanup();
}
file_put_contents(__DIR__ . '/level-titles.json', json_encode($titles, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n");

<?php

declare(strict_types=1);

require __DIR__ . '/../v04/native/bootstrap.php';

use OdtTemplateEngine\Elements\DrawTextBox;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\OdtElement;
use OdtTemplateEngine\Elements\RoundedRectElement;
use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\Utils\StyleMapper;
use OdtTemplateEngine\PageLayoutOdtTemplate;

/** Probe only: ODF-defined text-box radius on the existing native Writer frame. */
final class RadiusFrame extends DrawTextBox
{
    public function toDomNode(DOMDocument $dom): DOMNode
    {
        $node = parent::toDomNode($dom);
        $node->getElementsByTagName('draw:text-box')->item(0)->setAttributeNS(
            'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0', 'draw:corner-radius', '0.15cm'
        );
        return $node;
    }
}
/** Drawing-text scope adapter for the pending CC04 projection gap. */
final class ShapeLabelParagraph extends Paragraph
{
    public function getOwnStyleRequirements(): iterable
    {
        foreach (parent::getOwnStyleRequirements() as $requirement) {
            yield new StyleRequirement($requirement->kind(), StyleRequirement::SCOPE_AUTOMATIC,
                $requirement->family(), StyleRequirement::PART_CONTENT, $requirement->name(),
                $requirement->family() === 'text' ? null : 'Standard', $requirement->propertyGroups());
        }
    }
}
/** Probe only: reuse the existing native draw:rect rather than the failed custom path. */
final class GrowingRectangle extends OdtElement
{
    private OdtElement $rect;
    private Paragraph $text;

    public function __construct(private readonly string $name, string $label, private readonly bool $preset = false, private readonly array $design = [])
    {
        $this->rect = new RoundedRectElement($name, '0cm', '0cm', '1cm', '0.2cm', '0.15cm', '#DCE8F2');
        $this->text = (new ShapeLabelParagraph(null, ['fo:margin' => '0cm']))->addText($label, [
            'font-family' => $this->design['font'] ?? 'Liberation Sans',
            'font-size' => $this->design['size'] ?? '9pt',
            'color' => $this->design['color'] ?? '#24384B',
        ]);
        if ($this->preset) {
            $this->rect = (new \OdtTemplateEngine\Elements\ShapeElement(
                \OdtTemplateEngine\Elements\ShapeGeometry::preset('round-rectangle'),
                \OdtTemplateEngine\Elements\ShapeAppearance::solid('#DCE8F2'),
                \OdtTemplateEngine\Elements\DrawingLayout::fromArray(['anchor'=>'paragraph','width'=>'1cm','height'=>'0.2cm'])
            ))->addParagraph($this->text);
        }

    }
    public function ownedElements(): iterable { yield $this->text; }
    private function props(): array
    {
        return ['draw:fill' => 'solid', 'draw:fill-color' => $this->design['fill'] ?? '#DCE8F2', 'draw:stroke' => 'none',
            'draw:auto-grow-width' => 'true', 'draw:auto-grow-height' => 'true',
            'fo:padding-top' => '0.152cm', 'fo:padding-right' => '0.152cm',
            'fo:padding-bottom' => '0.152cm', 'fo:padding-left' => '0.152cm',
            'fo:margin-left' => '0.08cm', 'fo:margin-right' => '0.08cm',
            'fo:margin-top' => '0.06cm', 'fo:margin-bottom' => '0.06cm',
            'style:vertical-pos' => 'middle', 'style:vertical-rel' => 'char'];
    }
    public function getOwnStyleRequirements(): iterable
    {
        yield new StyleRequirement(StyleRequirement::KIND_DEFINITION, StyleRequirement::SCOPE_AUTOMATIC,
            'graphic', StyleRequirement::PART_CONTENT, StyleMapper::generateStyleName($this->props()),
            null, ['style:graphic-properties' => $this->props()]);
    }
    public function toDomNode(DOMDocument $dom): DOMNode
    {
        $node = $this->rect->toDomNode($dom);
        $node->removeAttribute('svg:x'); $node->removeAttribute('svg:y');
        $node->setAttribute('draw:name', $this->name);
        $node->setAttribute('text:anchor-type', 'as-char');
        $node->setAttribute('draw:style-name', StyleMapper::generateStyleName($this->props()));
        if (!$this->preset) $node->replaceChild($this->text->toDomNode($dom), $node->firstChild);
        $node->setAttribute('draw:text-style-name', $node->firstChild->getAttribute('text:style-name'));
        return $node;
    }
}
/** Temporary native shape flow adapter; no public engine API is presumed. */
final class ShapeRow extends Paragraph
{
    public function __construct(private readonly array $shapes) { parent::__construct(); }
    public function ownedElements(): iterable { yield from $this->shapes; }
    public function toDomNode(DOMDocument $dom, bool $insideTextBox = false): DOMNode
    {
        $p = parent::toDomNode($dom, $insideTextBox);
        foreach ($this->shapes as $shape) {
            $p->appendChild($shape->toDomNode($dom));
            $p->appendChild($dom->createTextNode(' '));
        }
        return $p;
    }
}
if (defined('ROUNDED_LIBRARY_ONLY')) { return; }
$labels=['PHP','PostgreSQL','Projektmanagement','Kommunikation','Langer editierbarer Kenntnistext'];
foreach (['control','text-box-radius','native-rectangle','preset-round'] as $mode) {
    $t = new PageLayoutOdtTemplate(__DIR__ . '/../flow/seed.odt');
    $t->setPageMargins('1.5cm','1.5cm','1.5cm','1.5cm');
    $t->appendBody((new Paragraph())->addText('Native rounded-tag control', ['bold' => true]));
    $p = new Paragraph(); $shapes=[];
    foreach ($labels as $i => $label) {
        if (in_array($mode,['native-rectangle','preset-round'],true)) { $shapes[]=new GrowingRectangle('round-' . $i, $label, $mode==='preset-round'); continue; }
        $f = $mode==='control' ? new DrawTextBox('frame-' . $i) : new RadiusFrame('frame-' . $i);
        $f->setBackground('#DCE8F2')->setFrameLayout(['anchor'=>'as-char','width'=>'1cm','height'=>'0.2cm'])
            ->setTextBoxSizing(['min-width'=>'1cm','min-height'=>'0.2cm','auto-grow-width'=>true,'auto-grow-height'=>true])
            ->setFrameSpacing(['padding'=>'0.152cm','margin-left'=>'0.08cm','margin-right'=>'0.08cm','margin-top'=>'0.06cm','margin-bottom'=>'0.06cm'])
            ->addElement((new Paragraph(null,['font-family'=>'Liberation Sans','font-size'=>'9pt','fo:margin'=>'0cm']))->addText($label));
        $p->addInlineElement($f)->addText(' ');
    }
    if ($shapes!==[]) $p=new ShapeRow($shapes);
    $t->appendBody($p);
    $t->appendBody((new Paragraph())->addText('FOLLOWING'));
    $t->save(__DIR__ . '/' . $mode . '.odt'); $t->save(__DIR__ . '/' . $mode . '-repeat.odt'); $t->cleanup();
}
file_put_contents(__DIR__.'/labels.json',json_encode($labels,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)."\n");
file_put_contents(__DIR__.'/outputs.json',json_encode(['control','text-box-radius','native-rectangle','preset-round'],JSON_PRETTY_PRINT)."\n");

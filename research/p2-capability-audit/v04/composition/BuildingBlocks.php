<?php

declare(strict_types=1);

namespace H07Research;

use DOMDocument;
use DOMElement;
use DOMNode;
use OdtTemplateEngine\Elements\OdtElement;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\Document\StructuredInsertionMode;

/** Research adapter for authoring native references; existing engine owns styles. */
final class UserFieldParagraph extends Paragraph
{
    public function __construct(
        private readonly array $tokens,
        array $options = [],
        private readonly array $drawings = []
    ) {
        parent::__construct(null, $options);
    }

    public function ownedElements(): iterable
    {
        yield from parent::ownedElements();
        yield from $this->drawings;
    }

    public function toDomNode(DOMDocument $dom, bool $insideTextBox = false): DOMNode
    {
        $p = parent::toDomNode($dom, $insideTextBox);
        foreach ($this->drawings as $drawingIndex => $drawing) {
            $n = $drawing->toDomNode($dom);
            if ($n instanceof DOMElement && $n->nodeName === 'text:p') {
                while ($n->firstChild !== null) {
                    $p->appendChild($n->firstChild);
                }
            } else {
                if ($n instanceof DOMElement) {
                    $n->setAttribute('draw:z-index', (string) $drawingIndex);
                }
                $p->appendChild($n);
            }
        }
        foreach ($this->tokens as $token) {
            if (is_array($token)) {
                $field = $dom->createElementNS('urn:oasis:names:tc:opendocument:xmlns:text:1.0', 'text:user-field-get');
                $field->setAttribute('text:name', $token['name']);
                // Writer evaluates the authoritative declaration; no cached text is authored.
                $p->appendChild($field);
            } elseif ($token === "\n") {
                $p->appendChild($dom->createElement('text:line-break'));
            } else {
                $p->appendChild($dom->createTextNode($token));
            }
        }
        return $p;
    }
}

/** Research-only use of SkillBarGroup's existing composition/ownership pattern. */
final class DrawingGroup extends OdtElement
{
    public function __construct(private readonly string $name, private readonly array $children, private readonly ?\OdtTemplateEngine\Elements\DrawingLayout $layout = null)
    {
    }

    public function ownedElements(): iterable
    {
        yield from $this->children;
    }

    public function structuredInsertionMode(): StructuredInsertionMode
    {
        return StructuredInsertionMode::PRESERVE_TEXT_CONTAINER;
    }

    public function getOwnStyleRequirements(): iterable
    {
        yield new StyleRequirement(
            StyleRequirement::KIND_DEFINITION,
            StyleRequirement::SCOPE_AUTOMATIC,
            'graphic',
            StyleRequirement::PART_CONTENT,
            $this->groupStyleName(),
            null,
            ['style:graphic-properties' => $this->groupProperties()]
        );
    }

    public function toDomNode(DOMDocument $dom): DOMNode
    {
        $group = $dom->createElement('draw:g');
        $group->setAttribute('draw:name', $this->name);
        $group->setAttribute('draw:style-name', $this->groupStyleName());
        $group->setAttribute('text:anchor-type', 'as-char');
        if ($this->layout !== null) {
            foreach ((new \OdtTemplateEngine\Elements\DrawingLayoutProjector())->objectAttributes($this->layout) as $key => $value) {
                $group->setAttribute($key, $value);
            }
        }
        foreach ($this->children as $index => $child) {
            $n = $child->toDomNode($dom);
            if ($n instanceof DOMElement && $n->nodeName === 'text:p') {
                $n = $n->firstChild;
            }
            if ($n instanceof DOMElement) {
                $n->removeAttribute('text:anchor-type');
                $n->setAttribute('draw:z-index', (string) $index);
            }
            $group->appendChild($n);
        }
        return $group;
    }

    private function groupProperties(): array
    {
        return array_merge(['style:wrap' => 'run-through', 'style:run-through' => 'foreground', 'style:vertical-pos' => 'middle', 'style:vertical-rel' => 'char'],
            $this->layout === null ? [] : (new \OdtTemplateEngine\Elements\DrawingLayoutProjector())->graphicLayoutProperties($this->layout));
    }

    private function groupStyleName(): string
    {
        return \OdtTemplateEngine\Utils\StyleMapper::generateStyleName($this->groupProperties());
    }
}

/** Bounded adapter: existing DrawTextBox plus native Writer minimum height. */
class FlowTextBox extends \OdtTemplateEngine\Elements\DrawTextBox
{
    public function toDomNode(DOMDocument $dom): DOMNode
    {
        $node = parent::toDomNode($dom);
        if ($node instanceof DOMElement) {
            $box = $node->getElementsByTagName('draw:text-box')->item(0);
            if ($box instanceof DOMElement) {
                $box->setAttributeNS('urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0', 'fo:min-height', '0.1cm');
            }
        }
        return $node;
    }
}

/** Supply the missing image foreground property through existing style collection. */
final class ForegroundImage extends \OdtTemplateEngine\Elements\ImageElement
{
    public function getOwnStyleRequirements(): iterable
    {
        foreach (parent::getOwnStyleRequirements() as $requirement) {
            $groups = $requirement->propertyGroups();
            $groups['style:graphic-properties']['style:run-through'] = 'foreground';
            yield new StyleRequirement(
                $requirement->kind(), StyleRequirement::SCOPE_AUTOMATIC, $requirement->family(),
                StyleRequirement::PART_CONTENT, $requirement->name(),
                'Graphics', $groups
            );
        }
    }
}

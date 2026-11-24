<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Elements;

use DOMDocument;
use DOMNode;
use InvalidArgumentException;
use OdtTemplateEngine\Document\FillImageRequirement;
use OdtTemplateEngine\Document\StructuredInsertionMode;
use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\Utils\StyleMapper;

/**
 * Native, Writer-editable drawing shape with semantic style and resource requirements.
 */
final class ShapeElement extends OdtElement
{
    private DrawingLayoutProjector $projector;

    /** @var list<Paragraph> */
    private array $paragraphs = [];

    private ?ShapeTextLayout $textLayout = null;

    public function __construct(
        private readonly ShapeGeometry $geometry,
        private readonly ShapeAppearance $appearance,
        private readonly DrawingLayout $layout
    ) {
        if ($layout->anchor() !== 'paragraph' || $layout->width() === null || $layout->height() === null) {
            throw new InvalidArgumentException('Initial native shapes require paragraph anchor, width and height.');
        }
        $this->projector = new DrawingLayoutProjector();
    }

    /**
     * Adds editable text inside the native shape.
     * Text formatting and paragraph alignment are owned by Paragraph.
     */
    public function addParagraph(Paragraph $paragraph): self
    {
        $this->paragraphs[] = $paragraph;
        return $this;
    }

    /**
     * Configures text placement without changing the native shape geometry.
     */
    public function setTextLayout(ShapeTextLayout $layout): self
    {
        $this->textLayout = $layout;
        return $this;
    }

    public function ownedElements(): iterable
    {
        return $this->paragraphs;
    }

    public function toDomNode(DOMDocument $dom): DOMNode
    {
        $shape = $dom->createElement('draw:custom-shape');
        foreach ($this->projector->objectAttributes($this->layout) as $name => $value) {
            $shape->setAttribute($name, $value);
        }
        $shape->setAttribute('draw:style-name', $this->styleName());
        foreach ($this->paragraphs as $index => $paragraph) {
            $paragraphNode = $paragraph->toDomNode($dom);
            if ($index === 0 && $paragraphNode->attributes?->getNamedItem('text:style-name') !== null) {
                $shape->setAttribute(
                    'draw:text-style-name',
                    $paragraphNode->attributes->getNamedItem('text:style-name')->nodeValue
                );
            }
            $shape->appendChild($paragraphNode);
        }

        $geometry = $dom->createElement('draw:enhanced-geometry');
        $geometry->setAttribute('svg:viewBox', $this->geometry->viewBox());
        $geometry->setAttribute('draw:type', $this->geometry->type());
        $geometry->setAttribute('draw:enhanced-path', $this->geometry->path());
        $shape->appendChild($geometry);

        return $shape;
    }

    public function structuredInsertionMode(): StructuredInsertionMode
    {
        return StructuredInsertionMode::PRESERVE_TEXT_CONTAINER;
    }

    public function getOwnStyleRequirements(): iterable
    {
        return [new StyleRequirement(
            StyleRequirement::KIND_DEFINITION,
            StyleRequirement::SCOPE_AUTOMATIC,
            'graphic',
            StyleRequirement::PART_CONTENT,
            $this->styleName(),
            null,
            ['style:graphic-properties' => $this->graphicProperties()]
        )];
    }

    public function getOwnFillImageDependencies(): iterable
    {
        $path = $this->appearance->imagePath();
        if ($path === null) {
            return [];
        }

        return [new FillImageRequirement(
            FillImageRequirement::PART_STYLES,
            (string) $this->appearance->fillImageName(),
            'Pictures/' . basename($path)
        )];
    }

    public function getOwnImageAssets(): array
    {
        $path = $this->appearance->imagePath();

        return $path === null ? [] : [['id' => basename($path), 'path' => $path]];
    }

    private function graphicProperties(): array
    {
        return array_merge(
            $this->appearance->graphicProperties(),
            $this->projector->graphicLayoutProperties($this->layout),
            $this->layout->wrap() === 'run-through' ? ['style:run-through' => 'foreground'] : [],
            $this->textLayout?->graphicProperties() ?? []
        );
    }

    private function styleName(): string
    {
        return StyleMapper::generateStyleName($this->graphicProperties());
    }
}

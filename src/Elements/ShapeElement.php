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
    private ?array $textAutoGrow = null;

    public function __construct(
        private readonly ShapeGeometry $geometry,
        private readonly ShapeAppearance $appearance,
        private readonly DrawingLayout $layout
    ) {
        if (!in_array($layout->anchor(), ['paragraph', 'as-char'], true) || $layout->width() === null || $layout->height() === null) {
            throw new InvalidArgumentException('Native shapes require paragraph/as-char anchor, width and height.');
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

    /** Explicit drawing-text Auto-Grow; unrelated to Writer-frame native minima. */
    public function setTextAutoGrow(bool $width = true, bool $height = true): self
    {
        $this->textAutoGrow = [
            'draw:auto-grow-width' => $width ? 'true' : 'false',
            'draw:auto-grow-height' => $height ? 'true' : 'false',
        ];
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
        if ($this->paragraphs === []) {
            // Preserve Writer-compatible empty text content for shapes without labels.
            $shape->appendChild($dom->createElement('text:p'));
        }
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

        if ($shape->getElementsByTagName('text:user-field-get')->length > 0
            || $shape->getElementsByTagNameNS('urn:oasis:names:tc:opendocument:xmlns:text:1.0', 'user-field-get')->length > 0) {
            throw new InvalidArgumentException('Writer User Fields require native Writer text, not drawing shape text.');
        }

        $geometry = $dom->createElement('draw:enhanced-geometry');
        $geometry->setAttribute('svg:viewBox', $this->geometry->viewBox());
        $geometry->setAttribute('draw:type', $this->geometry->type());
        $geometry->setAttribute('draw:enhanced-path', $this->geometry->path());
        foreach ($this->geometry->additionalAttributes() as $name => $value) {
            $geometry->setAttribute('draw:' . $name, $value);
        }
        foreach ($this->geometry->equations() as $equation) {
            $node = $dom->createElement('draw:equation');
            foreach ($equation as $name => $value) {
                $node->setAttribute('draw:' . $name, $value);
            }
            $geometry->appendChild($node);
        }
        foreach ($this->geometry->handles() as $handle) {
            $node = $dom->createElement('draw:handle');
            foreach ($handle as $name => $value) {
                $node->setAttribute('draw:' . $name, $value);
            }
            $geometry->appendChild($node);
        }
        $shape->appendChild($geometry);

        return $shape;
    }

    public function structuredInsertionMode(): StructuredInsertionMode
    {
        return $this->layout->anchor() === 'as-char'
            ? StructuredInsertionMode::INLINE_TEXT_FLOW
            : StructuredInsertionMode::PRESERVE_TEXT_CONTAINER;
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
            $this->layout->wrap() === 'run-through' && $this->layout->runThrough() === null ? ['style:run-through' => 'foreground'] : [],
            $this->layout->anchor() === 'as-char' && $this->layout->verticalMode() === null
                ? ['style:vertical-pos' => 'middle', 'style:vertical-rel' => 'char'] : [],
            $this->textLayout?->graphicProperties() ?? [],
            $this->textAutoGrow ?? []
        );
    }

    private function styleName(): string
    {
        return StyleMapper::generateStyleName($this->graphicProperties());
    }
}

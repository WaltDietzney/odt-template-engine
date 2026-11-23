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

    public function toDomNode(DOMDocument $dom): DOMNode
    {
        $shape = $dom->createElement('draw:custom-shape');
        foreach ($this->projector->objectAttributes($this->layout) as $name => $value) {
            $shape->setAttribute($name, $value);
        }
        $shape->setAttribute('draw:style-name', $this->styleName());
        // Writer-authored custom shapes contain an empty text paragraph before
        // the enhanced geometry. Keep this native ODF child in the emitted
        // object so text-container and roundtrip semantics remain explicit.
        $shape->appendChild($dom->createElement('text:p'));
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
            $this->projector->graphicLayoutProperties($this->layout)
        );
    }

    private function styleName(): string
    {
        return StyleMapper::generateStyleName($this->graphicProperties());
    }
}

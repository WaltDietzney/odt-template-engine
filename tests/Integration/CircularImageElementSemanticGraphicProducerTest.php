<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMElement;
use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\Elements\CircularImageElement;
use PHPUnit\Framework\TestCase;

final class CircularImageElementSemanticGraphicProducerTest extends TestCase
{
    public function testProducerExposesBitmapGraphicDefinitionBeforeDomMaterialization(): void
    {
        $image = new CircularImageElement($this->imagePath(), [
            'width' => '3cm',
            'height' => '3cm',
            'anchor' => 'as-char',
        ]);

        $requirements = iterator_to_array($image->getOwnStyleRequirements());

        self::assertCount(1, $requirements);
        $requirement = $requirements[0];
        self::assertInstanceOf(StyleRequirement::class, $requirement);
        self::assertSame(StyleRequirement::KIND_DEFINITION, $requirement->kind());
        self::assertSame(StyleRequirement::SCOPE_COMMON, $requirement->scope());
        self::assertSame('graphic', $requirement->family());
        self::assertSame(StyleRequirement::PART_STYLES, $requirement->documentPart());
        self::assertSame('Frame', $requirement->parentStyleName());
        self::assertSame([
            'style:graphic-properties' => [
                'draw:fill' => 'bitmap',
                'draw:fill-image-name' => $this->fillImageName(),
                'draw:fill-image-width' => '100%',
                'draw:fill-image-height' => '100%',
                'style:repeat' => 'stretch',
                'draw:stroke' => 'none',
            ],
        ], $requirement->propertyGroups());
    }

    public function testSemanticIdentityIgnoresShapeGeometryAndPlacement(): void
    {
        $first = new CircularImageElement($this->imagePath(), [
            'width' => '3cm',
            'height' => '3cm',
            'anchor' => 'as-char',
        ]);
        $second = new CircularImageElement($this->imagePath(), [
            'width' => '6cm',
            'height' => '4cm',
            'anchor' => 'paragraph',
            'align' => 'right',
        ]);

        $firstRequirement = iterator_to_array($first->getOwnStyleRequirements())[0];
        $secondRequirement = iterator_to_array($second->getOwnStyleRequirements())[0];

        self::assertSame($firstRequirement->name(), $secondRequirement->name());
        self::assertSame($firstRequirement->propertyGroups(), $secondRequirement->propertyGroups());
    }

    public function testSemanticRequirementIsStableAcrossLegacyDomLifecycle(): void
    {
        $image = new CircularImageElement($this->imagePath(), [
            'width' => '3cm',
            'height' => '3cm',
        ]);

        $before = iterator_to_array($image->getOwnStyleRequirements())[0];
        self::assertSame([], $image->getImageStyleRequirements());
        self::assertSame([], $image->getFillImageRequirements());

        $dom = new DOMDocument('1.0', 'UTF-8');
        $shape = $image->toDomNode($dom);

        self::assertInstanceOf(DOMElement::class, $shape);
        self::assertSame('3cm', $shape->getAttribute('svg:width'));
        self::assertSame('3cm', $shape->getAttribute('svg:height'));
        self::assertSame('ellipse', $shape->getElementsByTagName('draw:enhanced-geometry')->item(0)?->getAttribute('draw:type'));

        $after = iterator_to_array($image->getOwnStyleRequirements())[0];
        self::assertSame($before->name(), $after->name());
        self::assertSame($before->propertyGroups(), $after->propertyGroups());
        self::assertSame($before->name(), $shape->getAttribute('draw:style-name'));

        $legacyStyles = $image->getImageStyleRequirements();
        self::assertSame([$before->name() => $before->propertyGroups()['style:graphic-properties']], $legacyStyles);
        self::assertSame([
            $this->fillImageName() => [
                'name' => $this->fillImageName(),
                'path' => $this->imagePath(),
                'filename' => basename($this->imagePath()),
            ],
        ], $image->getFillImageRequirements());
    }

    private function imagePath(): string
    {
        return dirname(__DIR__, 2) . '/assets/WaltDietzney.png';
    }

    private function fillImageName(): string
    {
        return 'cv_photo_' . pathinfo($this->imagePath(), PATHINFO_FILENAME);
    }
}

<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use OdtTemplateEngine\Elements\ImageElement;
use PHPUnit\Framework\TestCase;

final class ImageElementSemanticGraphicProducerTest extends TestCase
{
    public function testCurrentImageOptionsDoNotProduceOwnedSemanticGraphicDefinition(): void
    {
        $image = new ImageElement($this->imagePath(), [
            'width' => '4cm',
            'height' => '2cm',
            'anchor' => 'as-char',
            'wrap' => 'left',
            'align' => 'right',
            'horizontal-pos' => 'right',
            'horizontal-rel' => 'paragraph',
            'vertical-pos' => 'top',
            'vertical-rel' => 'page',
        ]);

        self::assertSame([], iterator_to_array($image->getOwnStyleRequirements()));
    }

    public function testLegacyImageStyleIdentityRemainsAvailableWithoutSemanticGraphicDefinition(): void
    {
        $first = new ImageElement($this->imagePath(), [
            'width' => '4cm',
            'height' => '2cm',
        ]);
        $second = new ImageElement($this->imagePath(), [
            'width' => '5cm',
            'height' => '2cm',
        ]);

        $firstLegacy = $first->getOwnImageStyleRequirements();
        $secondLegacy = $second->getOwnImageStyleRequirements();

        self::assertCount(1, $firstLegacy);
        self::assertCount(1, $secondLegacy);
        self::assertNotSame(array_key_first($firstLegacy), array_key_first($secondLegacy));
        self::assertSame([], iterator_to_array($first->getOwnStyleRequirements()));
        self::assertSame([], iterator_to_array($second->getOwnStyleRequirements()));
    }

    public function testResolvedAlignmentMutationDoesNotCreateSemanticGraphicRequirement(): void
    {
        $image = new ImageElement($this->imagePath(), [
            'width' => '2cm',
            'align' => 'right',
        ]);

        self::assertSame([], iterator_to_array($image->getOwnStyleRequirements()));

        $dom = new DOMDocument('1.0', 'UTF-8');
        $image->toDomNode($dom);

        $options = $image->getImageOptions();
        self::assertSame('left', $options['style:wrap']);
        self::assertSame('right', $options['style:horizontal-pos']);
        self::assertSame('paragraph', $options['style:horizontal-rel']);
        self::assertSame([], iterator_to_array($image->getOwnStyleRequirements()));
    }

    private function imagePath(): string
    {
        return dirname(__DIR__, 2) . '/assets/WaltDietzney.png';
    }
}

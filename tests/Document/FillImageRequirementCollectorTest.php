<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Document;

use DOMDocument;
use OdtTemplateEngine\Document\FillImageRequirement;
use OdtTemplateEngine\Document\FillImageRequirementCollector;
use OdtTemplateEngine\Elements\CircularImageElement;
use OdtTemplateEngine\Elements\DrawTextBox;
use OdtTemplateEngine\Elements\ImageElement;
use PHPUnit\Framework\TestCase;

final class FillImageRequirementCollectorTest extends TestCase
{
    public function testCircularImageProducesSemanticDependencyBeforeDomMaterialization(): void
    {
        $image = $this->circularImage();

        self::assertSame([], $image->getOwnFillImageRequirements());

        $requirements = iterator_to_array($image->getOwnFillImageDependencies(), false);

        self::assertCount(1, $requirements);
        self::assertInstanceOf(FillImageRequirement::class, $requirements[0]);
        self::assertSame(FillImageRequirement::PART_STYLES, $requirements[0]->documentPart());
        self::assertSame($this->fillImageName(), $requirements[0]->name());
        self::assertSame('Pictures/' . basename($this->imagePath()), $requirements[0]->href());
        self::assertSame([], $image->getOwnFillImageRequirements());
    }

    public function testCollectorTraversesOwnedElementsTransitively(): void
    {
        $image = $this->circularImage();
        $inner = (new DrawTextBox('inner'))->addElement($image);
        $outer = (new DrawTextBox('outer'))->addElement($inner);

        $requirements = iterator_to_array((new FillImageRequirementCollector())->collect($outer), false);

        self::assertCount(1, $requirements);
        self::assertSame($this->fillImageName(), $requirements[0]->name());
        self::assertSame('Pictures/' . basename($this->imagePath()), $requirements[0]->href());
    }

    public function testNormalImageProducesNoFillImageDependency(): void
    {
        $image = new ImageElement($this->imagePath(), [
            'width' => '3cm',
            'height' => '3cm',
        ]);

        self::assertSame(
            [],
            iterator_to_array((new FillImageRequirementCollector())->collect($image), false)
        );
    }

    public function testCollectionDoesNotTriggerLegacyFillImageMutation(): void
    {
        $image = $this->circularImage();
        $collector = new FillImageRequirementCollector();

        iterator_to_array($collector->collect($image), false);
        iterator_to_array($collector->collect($image), false);

        self::assertSame([], $image->getOwnFillImageRequirements());

        $image->toDomNode(new DOMDocument('1.0', 'UTF-8'));

        self::assertArrayHasKey($this->fillImageName(), $image->getOwnFillImageRequirements());
    }

    public function testEquivalentDependenciesRemainVisibleForDocumentLocalRegistry(): void
    {
        $root = (new DrawTextBox('root'))
            ->addElement($this->circularImage())
            ->addElement($this->circularImage());

        $requirements = iterator_to_array((new FillImageRequirementCollector())->collect($root), false);

        self::assertCount(2, $requirements);
        self::assertTrue($requirements[0]->equals($requirements[1]));
    }

    private function circularImage(): CircularImageElement
    {
        return new CircularImageElement($this->imagePath(), [
            'width' => '3cm',
            'height' => '3cm',
        ]);
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

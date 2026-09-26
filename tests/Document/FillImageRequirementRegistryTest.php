<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Document;

use DOMDocument;
use InvalidArgumentException;
use OdtTemplateEngine\Document\FillImageRequirement;
use OdtTemplateEngine\Document\FillImageRequirementConflictException;
use OdtTemplateEngine\Document\FillImageRequirementRegistry;
use OdtTemplateEngine\OdtDocumentContext;
use PHPUnit\Framework\TestCase;

final class FillImageRequirementRegistryTest extends TestCase
{
    public function testRequirementExposesDeclarationSemanticsOnly(): void
    {
        $requirement = new FillImageRequirement(
            FillImageRequirement::PART_STYLES,
            'ProfilePhoto',
            'Pictures/profile.png'
        );

        self::assertSame('styles.xml', $requirement->documentPart());
        self::assertSame('ProfilePhoto', $requirement->name());
        self::assertSame('Pictures/profile.png', $requirement->href());
    }

    public function testRequirementRejectsUnsupportedDocumentPart(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported fill-image requirement document part "content.xml".');

        new FillImageRequirement('content.xml', 'ProfilePhoto', 'Pictures/profile.png');
    }

    public function testRequirementRejectsEmptyIdentityAndHref(): void
    {
        try {
            new FillImageRequirement(FillImageRequirement::PART_STYLES, ' ', 'Pictures/profile.png');
            self::fail('Expected empty identity to be rejected.');
        } catch (InvalidArgumentException $exception) {
            self::assertSame('Fill-image requirement identity must not be empty.', $exception->getMessage());
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Fill-image requirement href must not be empty.');

        new FillImageRequirement(FillImageRequirement::PART_STYLES, 'ProfilePhoto', ' ');
    }

    public function testEquivalentRegistrationIsIdempotent(): void
    {
        $registry = new FillImageRequirementRegistry();
        $first = new FillImageRequirement(
            FillImageRequirement::PART_STYLES,
            'ProfilePhoto',
            'Pictures/profile.png'
        );
        $equivalent = new FillImageRequirement(
            FillImageRequirement::PART_STYLES,
            'ProfilePhoto',
            'Pictures/profile.png'
        );

        $registry->register($first);
        $registry->register($equivalent);

        self::assertSame([$first], $registry->requirements());
    }

    public function testSameIdentityWithDifferentHrefConflictsDeterministically(): void
    {
        $registry = new FillImageRequirementRegistry();
        $registry->register(new FillImageRequirement(
            FillImageRequirement::PART_STYLES,
            'ProfilePhoto',
            'Pictures/first.png'
        ));

        $this->expectException(FillImageRequirementConflictException::class);
        $this->expectExceptionMessage(
            'Fill-image identity "ProfilePhoto" in styles.xml is already registered with a different href.'
        );

        $registry->register(new FillImageRequirement(
            FillImageRequirement::PART_STYLES,
            'ProfilePhoto',
            'Pictures/second.png'
        ));
    }

    public function testDocumentContextOwnsAndResetsFillImageRequirements(): void
    {
        $context = new OdtDocumentContext($this->dom(), $this->dom(), $this->dom());
        $requirement = new FillImageRequirement(
            FillImageRequirement::PART_STYLES,
            'ProfilePhoto',
            'Pictures/profile.png'
        );

        $context->registerFillImageRequirement($requirement);
        self::assertSame([$requirement], $context->fillImageRequirements()->requirements());

        $context->replaceCoreDocuments($this->dom(), $this->dom(), $this->dom());

        self::assertSame([], $context->fillImageRequirements()->requirements());
    }

    private function dom(): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->appendChild($dom->createElement('root'));

        return $dom;
    }
}

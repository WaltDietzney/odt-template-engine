<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Document;

use InvalidArgumentException;
use OdtTemplateEngine\Document\StyleRequirement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StyleRequirementTest extends TestCase
{
    public function testCommonParagraphDefinitionPreservesSemanticDimensions(): void
    {
        $requirement = new StyleRequirement(
            StyleRequirement::KIND_DEFINITION,
            StyleRequirement::SCOPE_COMMON,
            'paragraph',
            StyleRequirement::PART_STYLES,
            'CVMainHeading',
            'Standard',
            [
                'style:paragraph-properties' => [
                    'fo:margin-bottom' => '0.5cm',
                ],
            ]
        );

        self::assertSame(StyleRequirement::KIND_DEFINITION, $requirement->kind());
        self::assertSame(StyleRequirement::SCOPE_COMMON, $requirement->scope());
        self::assertSame('paragraph', $requirement->family());
        self::assertSame(StyleRequirement::PART_STYLES, $requirement->documentPart());
        self::assertSame('CVMainHeading', $requirement->name());
        self::assertSame('Standard', $requirement->parentStyleName());
        self::assertSame(
            ['style:paragraph-properties' => ['fo:margin-bottom' => '0.5cm']],
            $requirement->propertyGroups()
        );
    }

    public function testAutomaticTextDefinitionCanBeOwnedByContentPart(): void
    {
        $requirement = new StyleRequirement(
            StyleRequirement::KIND_DEFINITION,
            StyleRequirement::SCOPE_AUTOMATIC,
            'text',
            StyleRequirement::PART_CONTENT,
            'T3',
            null,
            ['style:text-properties' => ['fo:color' => '#cc0000']]
        );

        self::assertSame(StyleRequirement::SCOPE_AUTOMATIC, $requirement->scope());
        self::assertSame(StyleRequirement::PART_CONTENT, $requirement->documentPart());
        self::assertSame(['style:text-properties' => ['fo:color' => '#cc0000']], $requirement->propertyGroups());
    }

    public function testStyle05TopologyPreservesParentAndLocalTextOverride(): void
    {
        $requirement = new StyleRequirement(
            StyleRequirement::KIND_DEFINITION,
            StyleRequirement::SCOPE_AUTOMATIC,
            'paragraph',
            StyleRequirement::PART_CONTENT,
            'P1',
            'RefOverrideBase',
            ['style:text-properties' => ['fo:color' => '#cc0000']]
        );

        self::assertSame('RefOverrideBase', $requirement->parentStyleName());
        self::assertSame(
            ['style:text-properties' => ['fo:color' => '#cc0000']],
            $requirement->propertyGroups()
        );
    }

    public function testParagraphDefinitionPreservesMultipleTypedPropertyGroups(): void
    {
        $requirement = new StyleRequirement(
            StyleRequirement::KIND_DEFINITION,
            StyleRequirement::SCOPE_AUTOMATIC,
            'paragraph',
            StyleRequirement::PART_CONTENT,
            'P1',
            null,
            [
                'style:paragraph-properties' => ['fo:text-align' => 'center'],
                'style:text-properties' => [
                    'fo:color' => '#cc0000',
                    'fo:font-weight' => 'bold',
                ],
            ]
        );

        self::assertSame(
            [
                'style:paragraph-properties' => ['fo:text-align' => 'center'],
                'style:text-properties' => [
                    'fo:color' => '#cc0000',
                    'fo:font-weight' => 'bold',
                ],
            ],
            $requirement->propertyGroups()
        );
    }

    public function testReferenceOnlyRequirementHasNoDefinitionData(): void
    {
        $requirement = new StyleRequirement(
            StyleRequirement::KIND_REFERENCE,
            StyleRequirement::SCOPE_COMMON,
            'paragraph',
            StyleRequirement::PART_STYLES,
            'CVMainHeading'
        );

        self::assertSame(StyleRequirement::KIND_REFERENCE, $requirement->kind());
        self::assertNull($requirement->parentStyleName());
        self::assertSame([], $requirement->propertyGroups());
    }

    #[DataProvider('invalidRequirementProvider')]
    public function testInvalidRequirementsAreRejected(callable $factory, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        $factory();
    }

    /**
     * @return iterable<string, array{callable, string}>
     */
    public static function invalidRequirementProvider(): iterable
    {
        $base = [
            StyleRequirement::KIND_DEFINITION,
            StyleRequirement::SCOPE_COMMON,
            'paragraph',
            StyleRequirement::PART_STYLES,
            'Name',
        ];

        yield 'kind' => [
            static fn (): StyleRequirement => new StyleRequirement('other', ...array_slice($base, 1)),
            'Unsupported style requirement kind',
        ];
        yield 'scope' => [
            static fn (): StyleRequirement => new StyleRequirement($base[0], 'master', ...array_slice($base, 2)),
            'Unsupported style requirement scope',
        ];
        yield 'family' => [
            static fn (): StyleRequirement => new StyleRequirement($base[0], $base[1], '', ...array_slice($base, 3)),
            'family must not be empty',
        ];
        yield 'document part' => [
            static fn (): StyleRequirement => new StyleRequirement($base[0], $base[1], $base[2], 'meta.xml', $base[4]),
            'Unsupported style requirement document part',
        ];
        yield 'name' => [
            static fn (): StyleRequirement => new StyleRequirement($base[0], $base[1], $base[2], $base[3], ''),
            'name must not be empty',
        ];
        yield 'reference properties' => [
            static fn (): StyleRequirement => new StyleRequirement(
                StyleRequirement::KIND_REFERENCE,
                StyleRequirement::SCOPE_COMMON,
                'paragraph',
                StyleRequirement::PART_STYLES,
                'Name',
                null,
                ['style:text-properties' => ['fo:color' => '#fff']]
            ),
            'must not contain property groups',
        ];
        yield 'empty group name' => [
            static fn (): StyleRequirement => new StyleRequirement(
                ...$base,
                propertyGroups: ['' => ['fo:color' => '#fff']]
            ),
            'property group names must not be empty',
        ];
    }
}

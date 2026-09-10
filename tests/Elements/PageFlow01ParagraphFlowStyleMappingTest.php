<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Elements;

use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Utils\StyleMapper;
use PHPUnit\Framework\TestCase;

final class PageFlow01ParagraphFlowStyleMappingTest extends TestCase
{
    public function testMapsRequiredParagraphFlowOptionsToNativeOdfProperties(): void
    {
        self::assertSame(
            [
                'fo:keep-with-next' => 'always',
                'fo:keep-together' => 'always',
                'fo:widows' => 2,
                'fo:orphans' => 3,
                'fo:break-before' => 'page',
                'fo:break-after' => 'page',
            ],
            StyleMapper::mapParagraphStyle([
                'keep-with-next' => 'always',
                'keep-together' => 'always',
                'widows' => 2,
                'orphans' => 3,
                'break-before' => 'page',
                'break-after' => 'page',
            ])
        );
    }

    public function testPreservesNativeFlowValuesWithoutPaginationInterpretation(): void
    {
        self::assertSame(
            [
                'fo:keep-together' => 'auto',
                'fo:widows' => 0,
                'fo:orphans' => 4,
            ],
            StyleMapper::mapParagraphStyle([
                'keep-together' => 'auto',
                'widows' => 0,
                'orphans' => 4,
            ])
        );
    }

    public function testParagraphExposesAllFlowSemanticsThroughSemanticStyleRequirement(): void
    {
        $paragraph = new Paragraph('PageFlowEntry', [
            'keep-with-next' => 'always',
            'keep-together' => 'always',
            'widows' => 2,
            'orphans' => 2,
            'break-before' => 'page',
            'break-after' => 'page',
        ]);

        $requirements = iterator_to_array($paragraph->getOwnStyleRequirements(), false);

        self::assertCount(1, $requirements);
        $requirement = $requirements[0];

        self::assertSame(StyleRequirement::KIND_DEFINITION, $requirement->kind());
        self::assertSame(StyleRequirement::SCOPE_COMMON, $requirement->scope());
        self::assertSame('paragraph', $requirement->family());
        self::assertSame(StyleRequirement::PART_STYLES, $requirement->documentPart());
        self::assertSame('PageFlowEntry', $requirement->name());
        self::assertSame('Standard', $requirement->parentStyleName());
        self::assertSame(
            [
                'style:paragraph-properties' => [
                    'fo:keep-with-next' => 'always',
                    'fo:keep-together' => 'always',
                    'fo:widows' => 2,
                    'fo:orphans' => 2,
                    'fo:break-before' => 'page',
                    'fo:break-after' => 'page',
                ],
            ],
            $requirement->propertyGroups()
        );
    }

    public function testRawNativeFlowPropertiesRemainSupportedAsAdvancedEscapeHatch(): void
    {
        $paragraph = new Paragraph('NativeFlow', [
            'fo:keep-together' => 'always',
            'fo:widows' => '3',
            'fo:orphans' => '2',
        ]);

        $requirements = iterator_to_array($paragraph->getOwnStyleRequirements(), false);

        self::assertSame(
            [
                'style:paragraph-properties' => [
                    'fo:keep-together' => 'always',
                    'fo:widows' => '3',
                    'fo:orphans' => '2',
                ],
            ],
            $requirements[0]->propertyGroups()
        );
    }
}
